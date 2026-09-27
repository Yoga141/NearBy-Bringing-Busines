<?php

namespace App\Assistant;

use Anthropic\Client;
use Anthropic\Core\Exceptions\APIConnectionException;
use Anthropic\Core\Exceptions\APIException;
use Anthropic\Core\Exceptions\APIStatusException;
use Anthropic\Core\Exceptions\AuthenticationException;
use Anthropic\Core\Exceptions\NotFoundException;
use Anthropic\Core\Exceptions\RateLimitException;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Support\Facades\Log;

/**
 * Optional: lets Claude word the answer more naturally.
 *
 * Claude never chooses the UMKM. It receives the rows the database search
 * already returned and is told to describe only those; the list shown under
 * the answer comes from the same rows. When ANTHROPIC_API_KEY is not set, or
 * the call fails or times out, the assistant keeps working with its own
 * template answer - so a missing key or an outage degrades wording, not
 * correctness.
 */
final class ClaudeNarrator
{
    private const SYSTEM = <<<'TXT'
        Kamu adalah Asisten NearBy, pemandu UMKM di Balikpapan, Kalimantan Timur.
        Tugasmu menjelaskan hasil pencarian UMKM yang SUDAH diambil dari database NearBy.

        Aturan:
        - Sebutkan hanya UMKM yang ada di data HASIL. Jangan menambah UMKM, harga, alamat, jam buka, atau menu yang tidak tertulis di data.
        - Jika sebuah informasi kosong di data, katakan belum tersedia - jangan menebak.
        - Pertahankan urutan dan nomor hasil seperti di data (1, 2, 3 ...), supaya pengguna bisa bilang "detail nomor 2".
        - Sampaikan CATATAN apa adanya bila ada (misalnya bahwa lokasi memakai wilayah kecamatan).
        - Jika HASIL kosong, katakan tidak ditemukan dan sarankan mengubah kategori, wilayah, atau kata kunci.
        - Bahasa Indonesia yang ramah dan ringkas, teks biasa tanpa markdown, maksimal sekitar 120 kata.
        TXT;

    public function enabled(): bool
    {
        return (string) config('services.anthropic.key') !== '';
    }

    /**
     * @param  list<array{role: string, text: string}>  $history
     * @param  array<string, mixed>  $facts  Search summary + rows, already trimmed to what may be said.
     */
    public function narrate(string $message, array $history, array $facts): ?string
    {
        if (! $this->enabled()) {
            return null;
        }

        $timeout = (float) config('services.anthropic.timeout', 20);
        $client = new Client(
            apiKey: (string) config('services.anthropic.key'),
            requestOptions: [
                // The SDK leaves timeouts to the transport, so they are set on Guzzle.
                'transporter' => new GuzzleClient(['timeout' => $timeout, 'connect_timeout' => 5]),
                'maxRetries' => 1,
            ],
        );

        $messages = [];
        foreach ($history as $turn) {
            $messages[] = ['role' => $turn['role'] === 'assistant' ? 'assistant' : 'user', 'content' => $turn['text']];
        }
        // The API wants the conversation to start with the user and alternate.
        while ($messages && $messages[0]['role'] !== 'user') {
            array_shift($messages);
        }
        $messages[] = [
            'role' => 'user',
            'content' => "PERTANYAAN PENGGUNA:\n{$message}\n\nDATA DARI DATABASE NEARBY (JSON):\n"
                .json_encode($facts, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT),
        ];

        try {
            $response = $client->messages->create(
                maxTokens: 1024,
                messages: $this->alternate($messages),
                model: (string) config('services.anthropic.model', 'claude-opus-5'),
                system: self::SYSTEM,
                // A short, grounded answer - low effort keeps the chat snappy.
                outputConfig: ['effort' => 'low'],
            );
        } catch (AuthenticationException $e) {
            Log::error('Asisten AI: ANTHROPIC_API_KEY ditolak.', ['error' => $e->getMessage()]);

            return null;
        } catch (NotFoundException $e) {
            Log::error('Asisten AI: model tidak ditemukan, periksa ANTHROPIC_MODEL.', ['error' => $e->getMessage()]);

            return null;
        } catch (RateLimitException $e) {
            Log::warning('Asisten AI: batas pemakaian API tercapai.', ['error' => $e->getMessage()]);

            return null;
        } catch (APIStatusException $e) {
            Log::warning('Asisten AI: API membalas galat.', ['error' => $e->getMessage()]);

            return null;
        } catch (APIConnectionException $e) {
            Log::warning('Asisten AI: tidak bisa menghubungi API (jaringan/timeout).', ['error' => $e->getMessage()]);

            return null;
        } catch (APIException $e) {
            Log::warning('Asisten AI: galat SDK.', ['error' => $e->getMessage()]);

            return null;
        }

        if ($response->stopReason === 'refusal') {
            return null;
        }

        $text = '';
        foreach ($response->content as $block) {
            if ($block->type === 'text') {
                $text .= $block->text;
            }
        }

        $text = trim($text);

        return $text === '' ? null : $text;
    }

    /**
     * Merge consecutive same-role turns (e.g. two user messages in a row after
     * a failed request), keeping the history valid for the API.
     *
     * @param  list<array{role: string, content: string}>  $messages
     * @return list<array{role: string, content: string}>
     */
    private function alternate(array $messages): array
    {
        $out = [];
        foreach ($messages as $m) {
            $last = array_key_last($out);
            if ($last !== null && $out[$last]['role'] === $m['role']) {
                $out[$last]['content'] .= "\n\n".$m['content'];
            } else {
                $out[] = $m;
            }
        }

        return $out;
    }
}
