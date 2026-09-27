<?php

namespace App\Assistant;

use App\Models\Umkm;
use Illuminate\Support\Collection;

/**
 * One turn of the chat assistant:
 *
 *   message + previous context → criteria → database search → answer
 *
 * The conversation state lives in `context`, which the client sends back
 * with every message (category, kecamatan, keyword, sort, landmark, page and
 * the ids of the last results). That is what lets "yang murah" and
 * "yang dekat kampus" refine "carikan UMKM makanan" instead of starting over,
 * and what "detail nomor 2" refers to. The server trusts none of it blindly:
 * every field is re-validated before it touches a query.
 */
final class Assistant
{
    /** Results listed per answer. */
    public const PAGE_SIZE = 5;

    private const SORT_LABELS = [
        'price_asc' => 'diurutkan dari harga termurah',
        'price_desc' => 'diurutkan dari harga tertinggi',
        'rating' => 'diurutkan dari rating tertinggi',
        'popular' => 'diurutkan dari yang paling banyak dilihat',
    ];

    public function __construct(
        private readonly UmkmSearch $search,
        private readonly ClaudeNarrator $narrator,
    ) {}

    /**
     * @param  array<string, mixed>  $context
     * @param  list<array{role: string, text: string}>  $history
     * @return array{reply: string, context: array<string, mixed>, results: Collection<int, Umkm>, total: int, detail: ?Umkm, source: string}
     */
    public function answer(string $message, array $context, array $history): array
    {
        $previous = $this->cleanContext($context);
        $parsed = QueryParser::parse($message);

        // "detail nomor 2" → one UMKM from the previous answer.
        if ($parsed['index'] !== null && $previous['lastIds'] && ! $parsed['category']) {
            return $this->detailByPosition($parsed['index'], $previous, $message, $history);
        }

        // "jam buka Kopi Saluang?" → that UMKM, found by its full name. Checked
        // before categories: "kopi" alone would otherwise start a food search.
        $named = $this->findByName($parsed['text']);
        if ($named) {
            return $this->detail($named, $previous, $message, $history);
        }

        $understood = $parsed['category'] || $parsed['location'] || $parsed['keyword'] || $parsed['sort']
            || $parsed['near'] || $parsed['nearMe'] || $parsed['openOnly'] || $parsed['more'] || $parsed['allLocations'];

        if ($parsed['reset'] && ! ($parsed['category'] || $parsed['keyword'] || $parsed['location'])) {
            return $this->plain(
                'Baik, pencarian saya mulai dari awal. Mau cari UMKM apa? Misalnya: "carikan UMKM makanan di Balikpapan Selatan".',
                $this->emptyContext(),
            );
        }

        if (! $understood) {
            $hasSearch = $previous['category'] || $previous['keyword'] || $previous['location'];
            $intro = $parsed['greeting'] ? 'Halo! Saya Asisten NearBy. ' : '';

            return $this->plain(
                $intro.($hasSearch && ! $parsed['greeting'] && ! $parsed['help']
                    ? 'Maaf, saya belum paham maksudnya. Coba perjelas, misalnya "yang murah", "yang dekat kampus", "di Balikpapan Utara", atau "detail nomor 1".'
                    : 'Saya bisa mencarikan UMKM di Balikpapan dari data NearBy. Contoh: "carikan UMKM makanan di Balikpapan", '
                        .'lalu lanjutkan dengan "yang murah", "yang dekat kampus", atau "detail nomor 1".'),
                $parsed['reset'] ? $this->emptyContext() : $previous,
            );
        }

        $criteria = $this->merge($previous, $parsed);
        $found = $this->search->run($criteria);

        // A place carried over from earlier turns ("dekat bandara") must not
        // sink a new question ("ada kepiting?"): widen to all of Balikpapan
        // and say so, rather than answering "nothing".
        $widened = null;
        if ($found['rows']->isEmpty() && ($criteria['near'] || $criteria['location']) && ! $parsed['near'] && ! $parsed['location']) {
            $wider = [...$criteria, 'near' => null, 'location' => null];
            $retry = $this->search->run($wider);
            if ($retry['rows']->isNotEmpty()) {
                $widened = $criteria['near']
                    ? 'dekat '.QueryParser::LANDMARKS[$criteria['near']]['label']
                    : 'di '.$criteria['location'];
                $criteria = $wider;
                $found = $retry;
            }
        }

        /** @var Collection<int, Umkm> $rows */
        $rows = $found['rows'];
        $total = $rows->count();

        // A word nothing matched ("asdfgh") is dropped from the heading and
        // from the context; the note already says the list is the broader one.
        if ($criteria['keyword'] && ! $found['keywordMatched'] && $total > 0) {
            $criteria['keyword'] = null;
        }

        $page = $criteria['page'];
        if ($page * self::PAGE_SIZE >= $total && $page > 0) {
            $page = 0; // "yang lain" past the end wraps to the top again
        }
        $shown = $rows->slice($page * self::PAGE_SIZE, self::PAGE_SIZE)->values();

        $notes = $found['notes'];
        if ($widened) {
            array_unshift($notes, "Tidak ada yang cocok {$widened}, jadi saya cari di seluruh Balikpapan.");
        }
        if ($parsed['nearMe']) {
            $notes[] = 'Saya belum bisa membaca lokasimu. Sebutkan kecamatan atau patokan, misalnya "dekat kampus" atau "di Balikpapan Selatan".';
        }
        if ($criteria['openOnly']) {
            $notes[] = 'Status "buka" mengikuti status yang diisi pemilik UMKM, bukan jam saat ini.';
        }

        $newContext = [
            ...$criteria,
            'page' => $page,
            'lastIds' => $shown->pluck('id')->all(),
        ];

        $rulesReply = $this->listReply($criteria, $shown, $total, $page, $notes);
        $source = 'rules';
        $reply = $rulesReply;

        $ai = $this->narrator->narrate($message, $history, [
            'dipahami' => $this->summary($criteria),
            'total_ditemukan' => $total,
            'ditampilkan' => $shown->count(),
            'catatan' => $notes,
            'hasil' => $shown->map(fn (Umkm $u, int $i) => $this->facts($u, $page * self::PAGE_SIZE + $i + 1))->all(),
        ]);
        if ($ai !== null) {
            $reply = $ai;
            $source = 'ai';
        }

        return [
            'reply' => $reply,
            'context' => $newContext,
            'results' => $shown,
            'total' => $total,
            'detail' => null,
            'source' => $source,
        ];
    }

    /** @return array<string, mixed> */
    public function emptyContext(): array
    {
        return [
            'category' => null, 'location' => null, 'keyword' => null, 'sort' => null,
            'near' => null, 'openOnly' => false, 'page' => 0, 'lastIds' => [],
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function cleanContext(array $context): array
    {
        $keyword = is_string($context['keyword'] ?? null) ? mb_substr(QueryParser::normalise($context['keyword']), 0, 60) : null;
        $ids = array_values(array_filter(
            is_array($context['lastIds'] ?? null) ? $context['lastIds'] : [],
            fn ($id) => is_int($id) && $id > 0,
        ));

        return [
            'category' => QueryParser::validCategory($context['category'] ?? null),
            'location' => QueryParser::validLocation($context['location'] ?? null),
            'keyword' => $keyword ?: null,
            'sort' => QueryParser::validSort($context['sort'] ?? null),
            'near' => QueryParser::validNear($context['near'] ?? null),
            'openOnly' => ($context['openOnly'] ?? false) === true,
            'page' => is_int($context['page'] ?? null) ? max(0, min(200, $context['page'])) : 0,
            'lastIds' => array_slice($ids, 0, self::PAGE_SIZE),
        ];
    }

    /**
     * Fold this message into the running search.
     *
     * A new category or a new specific wish starts a new topic (the old
     * keyword goes, place and sort order stay). Everything else - "yang murah",
     * "di utara", "yang dekat kampus", "yang buka", "yang lain" - refines what
     * was being looked at.
     *
     * @return array<string, mixed>
     */
    private function merge(array $previous, array $parsed): array
    {
        $c = $parsed['reset'] ? $this->emptyContext() : $previous;

        $newTopic = $parsed['category'] !== null && $parsed['category'] !== $c['category'];
        if ($parsed['category']) {
            $c['category'] = $parsed['category'];
        }
        if ($parsed['keyword']) {
            $c['keyword'] = $parsed['keyword'];
        } elseif ($newTopic) {
            $c['keyword'] = null;
        }

        if ($parsed['location']) {
            $c['location'] = $parsed['location'];
            $c['near'] = null; // an explicit kecamatan replaces a landmark
        } elseif ($parsed['allLocations']) {
            $c['location'] = null;
            $c['near'] = null;
        }
        if ($parsed['near']) {
            $c['near'] = $parsed['near'];
            // The landmark decides the area unless this same message named one.
            if (! $parsed['location']) {
                $c['location'] = null;
            }
        }
        if ($parsed['sort']) {
            $c['sort'] = $parsed['sort'];
        }
        if ($parsed['openOnly']) {
            $c['openOnly'] = true;
        }

        $c['page'] = $parsed['more'] && ! $newTopic && ! $parsed['keyword'] ? $c['page'] + 1 : 0;

        return $c;
    }

    private function findByName(string $text): ?Umkm
    {
        if (mb_strlen($text) < 6) {
            return null;
        }
        $padded = ' '.$text.' ';

        return Umkm::query()->visible()->with(['items', 'photos'])->get()
            ->filter(function (Umkm $u) use ($padded) {
                $name = QueryParser::normalise($u->name);

                // Only distinctive names: a UMKM called just "Kopi" must not
                // hijack every question about coffee.
                return (str_contains($name, ' ') || mb_strlen($name) >= 8)
                    && str_contains($padded, ' '.$name.' ');
            })
            ->sortByDesc(fn (Umkm $u) => mb_strlen($u->name))
            ->first();
    }

    private function detailByPosition(int $position, array $previous, string $message, array $history): array
    {
        // The list the user saw was numbered from offset+1 (e.g. 6-10 after
        // "yang lain"); accept both that numbering and 1-5.
        $offset = $previous['page'] * self::PAGE_SIZE;
        $slot = $position > $offset ? $position - $offset - 1 : $position - 1;
        $id = $previous['lastIds'][$slot] ?? null;
        if ($id === null) {
            $count = count($previous['lastIds']);

            return $this->plain(
                "Nomor {$position} tidak ada di daftar terakhir. Pilih nomor ".($offset + 1).' sampai '.($offset + $count).'.',
                $previous,
            );
        }

        $umkm = Umkm::query()->visible()->with(['items', 'photos'])->find($id);
        if (! $umkm) {
            return $this->plain('UMKM itu sudah tidak tersedia. Coba cari lagi.', $previous);
        }

        return $this->detail($umkm, $previous, $message, $history);
    }

    private function detail(Umkm $u, array $context, string $message, array $history): array
    {
        $lines = ["{$u->name} ({$u->category}, {$u->location})."];
        if ($u->tag) {
            $lines[] = $u->tag;
        }
        $lines[] = $u->reviews_count > 0
            ? 'Rating '.number_format((float) $u->rating, 1, ',', '')." dari {$u->reviews_count} ulasan."
            : 'Belum ada ulasan.';
        $lines[] = 'Harga: '.($u->price_label ?: 'belum diisi pemilik').'.';
        $lines[] = 'Alamat: '.($u->address ?: 'belum diisi').'.';
        $lines[] = 'Jam buka: '.($u->hours ?: 'belum diisi').'.';
        if ($u->phone) {
            $lines[] = "Telepon/WA: {$u->phone}.";
        }
        $lines[] = 'Status: '.['aktif' => 'buka', 'libur' => 'sedang libur', 'tutup' => 'tutup sementara'][$u->status].'.';
        $items = $u->items->where('available', true)->take(4)
            ->map(fn ($i) => $i->price ? "{$i->name} ({$i->price})" : $i->name)->implode(', ');
        if ($items) {
            $lines[] = ($u->list_label ?: 'Produk').": {$items}.";
        }

        $reply = implode("\n", $lines);
        $source = 'rules';
        $ai = $this->narrator->narrate($message, $history, [
            'dipahami' => 'Pengguna meminta detail satu UMKM.',
            'hasil' => [$this->facts($u, 1)],
            'catatan' => [],
        ]);
        if ($ai !== null) {
            $reply = $ai;
            $source = 'ai';
        }

        return [
            'reply' => $reply,
            'context' => $context,
            'results' => collect([$u]),
            'total' => 1,
            'detail' => $u,
            'source' => $source,
        ];
    }

    private function plain(string $reply, array $context): array
    {
        return [
            'reply' => $reply,
            'context' => $context,
            'results' => collect(),
            'total' => 0,
            'detail' => null,
            'source' => 'rules',
        ];
    }

    /**
     * @param  Collection<int, Umkm>  $shown
     * @param  list<string>  $notes
     */
    private function listReply(array $criteria, Collection $shown, int $total, int $page, array $notes): string
    {
        if ($total === 0) {
            $what = $this->summary([...$criteria, 'sort' => null]);
            $tips = [];
            if ($criteria['location'] || $criteria['near']) {
                $tips[] = 'coba wilayah lain atau "semua wilayah"';
            }
            if ($criteria['keyword']) {
                $tips[] = 'pakai kata kunci yang lebih umum';
            }
            if ($criteria['openOnly']) {
                $tips[] = 'hapus syarat "yang buka"';
            }
            $tips[] = 'ketik "mulai baru" untuk mencari dari awal';

            return "Maaf, belum ada {$what} di data NearBy.\nSaran: ".implode(', ', $tips).'.'
                .($notes ? "\n".implode("\n", $notes) : '');
        }

        $what = $this->summary($criteria);
        $from = $page * self::PAGE_SIZE + 1;
        $to = $from + $shown->count() - 1;
        $head = $total > $shown->count()
            ? "Saya menemukan {$total} {$what}. Ini nomor {$from}-{$to}:"
            : "Saya menemukan {$total} {$what}:";

        $lines = [$head];
        foreach ($shown->values() as $i => $u) {
            $n = $from + $i;
            $rating = $u->reviews_count > 0
                ? 'rating '.number_format((float) $u->rating, 1, ',', '')." ({$u->reviews_count} ulasan)"
                : 'belum ada ulasan';
            $price = $u->price_label ? "harga {$u->price_label}" : 'harga belum diisi';
            $lines[] = "{$n}. {$u->name} - {$u->category}, {$u->location}; {$price}; {$rating}.";
        }
        array_push($lines, ...$notes);

        $next = ['"detail nomor 1"'];
        if ($criteria['sort'] !== 'price_asc') {
            $next[] = '"yang murah"';
        }
        if (! $criteria['near']) {
            $next[] = '"yang dekat kampus"';
        }
        if ($total > $to) {
            $next[] = '"yang lain"';
        }
        $lines[] = 'Lanjutkan dengan '.implode(', ', $next).'.';

        return implode("\n", $lines);
    }

    /** "UMKM kuliner "kepiting" dekat kampus (ITK / Poltekba), diurutkan dari harga termurah" */
    private function summary(array $c): string
    {
        $parts = ['UMKM'.($c['category'] ? ' '.mb_strtolower($c['category']) : '')];
        if ($c['keyword']) {
            $parts[] = "\"{$c['keyword']}\"";
        }
        if ($c['near']) {
            $parts[] = 'dekat '.QueryParser::LANDMARKS[$c['near']]['label'];
        } else {
            $parts[] = 'di '.($c['location'] ?? 'Balikpapan');
        }
        if ($c['openOnly']) {
            $parts[] = 'yang sedang buka';
        }
        $text = implode(' ', $parts);

        return $c['sort'] ? $text.', '.self::SORT_LABELS[$c['sort']] : $text;
    }

    /** What the language model may say about one UMKM - nothing more. */
    private function facts(Umkm $u, int $number): array
    {
        return [
            'nomor' => $number,
            'nama' => $u->name,
            'kategori' => $u->category,
            'wilayah' => $u->location,
            'deskripsi' => $u->tag,
            'harga' => $u->price_label,
            'rating' => $u->reviews_count > 0 ? (float) $u->rating : null,
            'jumlah_ulasan' => (int) $u->reviews_count,
            'alamat' => $u->address,
            'jam_buka' => $u->hours,
            'status' => $u->status,
            'produk' => $u->relationLoaded('items')
                ? $u->items->take(5)->map(fn ($i) => ['nama' => $i->name, 'harga' => $i->price, 'tersedia' => (bool) $i->available])->values()->all()
                : [],
        ];
    }
}
