<?php

namespace Tests\Feature;

use App\Assistant\PriceParser;
use App\Models\Umkm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * The chat assistant answers from the database and keeps the conversation.
 */
class AssistantTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Deterministic answers: no wording layer in tests.
        config(['services.anthropic.key' => '']);

        $rows = [
            ['Kepiting Mahal', 'Kuliner', 'Balikpapan Timur', 'Rp80–150rb', 'Jl. Mulawarman'],
            ['Nasi Kuning Murah', 'Kuliner', 'Balikpapan Selatan', 'Rp10–20rb', 'Jl. Sepinggan Baru'],
            ['Kopi Kampus', 'Kuliner', 'Balikpapan Utara', 'Rp15–30rb', 'Jl. Soekarno Hatta Km 15, dekat kampus ITK'],
            ['Warung Sedang', 'Kuliner', 'Balikpapan Kota', 'Rp25–45rb', 'Jl. Ahmad Yani'],
            ['Hotel Teluk', 'Penginapan', 'Balikpapan Selatan', 'Rp300rb', 'Jl. Sepinggan'],
        ];
        foreach ($rows as [$name, $cat, $loc, $price, $address]) {
            Umkm::create([
                'name' => $name, 'category' => $cat, 'location' => $loc, 'price_label' => $price,
                'address' => $address, 'verification' => 'disetujui',
            ]);
        }
        // Must never show up: not approved.
        Umkm::create(['name' => 'Warung Rahasia', 'category' => 'Kuliner', 'location' => 'Balikpapan Kota', 'verification' => 'menunggu']);
    }

    private function ask(string $message, array $context = [], array $history = []): TestResponse
    {
        return $this->postJson('/api/assistant/chat', compact('message', 'context', 'history'))->assertOk();
    }

    public function test_a_food_search_returns_only_real_published_umkm(): void
    {
        $res = $this->ask('Carikan UMKM makanan di Balikpapan');

        $names = collect($res->json('results'))->pluck('name');
        $this->assertCount(4, $names);
        $this->assertNotContains('Warung Rahasia', $names);
        $this->assertNotContains('Hotel Teluk', $names);
        $this->assertSame(4, $res->json('total'));
        $this->assertSame('Kuliner', $res->json('context.category'));
        $this->assertStringContainsString('Saya menemukan 4 UMKM kuliner di Balikpapan', $res->json('reply'));
        $this->assertSame('rules', $res->json('source'));
    }

    public function test_follow_ups_keep_the_context(): void
    {
        $first = $this->ask('Carikan UMKM makanan.');

        // "Yang murah." - still food, now cheapest first.
        $cheap = $this->ask('Yang murah.', $first->json('context'));
        $this->assertSame('Kuliner', $cheap->json('context.category'));
        $this->assertSame('price_asc', $cheap->json('context.sort'));
        $this->assertSame(
            ['Nasi Kuning Murah', 'Kopi Kampus', 'Warung Sedang', 'Kepiting Mahal'],
            collect($cheap->json('results'))->pluck('name')->all(),
        );

        // "Yang dekat kampus." - still food and still cheap, now near campus.
        $campus = $this->ask('Yang dekat kampus.', $cheap->json('context'));
        $this->assertSame('Kuliner', $campus->json('context.category'));
        $this->assertSame('price_asc', $campus->json('context.sort'));
        $this->assertSame('kampus', $campus->json('context.near'));
        $this->assertSame(['Kopi Kampus'], collect($campus->json('results'))->pluck('name')->all());

        // "Detail nomor 1" refers to the last list.
        $detail = $this->ask('detail nomor 1', $campus->json('context'));
        $this->assertSame('Kopi Kampus', $detail->json('results.0.name'));
        $this->assertStringContainsString('Jl. Soekarno Hatta Km 15', $detail->json('reply'));
    }

    public function test_a_landmark_without_address_match_falls_back_to_its_kecamatan_and_says_so(): void
    {
        $res = $this->ask('penginapan dekat bandara');

        $this->assertSame(['Hotel Teluk'], collect($res->json('results'))->pluck('name')->all());
        $this->assertStringContainsString('Hotel Teluk', $res->json('reply'));
    }

    public function test_nothing_found_is_said_plainly_instead_of_inventing_results(): void
    {
        $res = $this->ask('carikan batik di balikpapan barat');

        $this->assertSame(0, $res->json('total'));
        $this->assertSame([], $res->json('results'));
        $this->assertStringContainsString('Maaf, belum ada', $res->json('reply'));
    }

    public function test_tampered_context_is_ignored(): void
    {
        $res = $this->ask('yang murah', ['category' => "Kuliner' OR 1=1", 'location' => 'Mars', 'lastIds' => ['x']]);

        $this->assertNull($res->json('context.category'));
        $this->assertNull($res->json('context.location'));
        $this->assertSame(5, $res->json('total'));
    }

    public function test_greeting_and_empty_input(): void
    {
        $this->assertStringContainsString('Asisten NearBy', $this->ask('halo')->json('reply'));
        $this->postJson('/api/assistant/chat', ['message' => ''])->assertUnprocessable();
    }

    public function test_price_labels_are_read_as_rupiah(): void
    {
        $this->assertSame([25000, 75000], PriceParser::amounts('Rp25–75rb'));
        $this->assertSame([30000], PriceParser::amounts('Mulai Rp30rb'));
        $this->assertSame([7000], PriceParser::amounts('Rp7rb/kg'));
        $this->assertSame([15000], PriceParser::amounts('Rp15.000'));
        $this->assertSame([1500000], PriceParser::amounts('1,5jt'));
        $this->assertSame([], PriceParser::amounts(null));
    }
}
