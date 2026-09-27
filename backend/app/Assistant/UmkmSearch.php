<?php

namespace App\Assistant;

use App\Models\Umkm;
use Illuminate\Support\Collection;

/**
 * Runs the assistant's criteria against the real catalogue.
 *
 * Only UMKM the public site shows (approved, not hidden) are ever returned.
 * Category / kecamatan / "sedang buka" are filtered in SQL; the free-text part
 * and the landmark are matched in PHP over name, description, address and
 * product names, where word-by-word scoring is simpler than portable SQL.
 */
final class UmkmSearch
{
    /** Hard cap on rows read per question - the catalogue is a city directory, not a data lake. */
    private const MAX_ROWS = 1000;

    /**
     * @param  array{category: ?string, location: ?string, keyword: ?string, sort: ?string, near: ?string, openOnly: bool}  $criteria
     * @return array{rows: Collection<int, Umkm>, notes: list<string>, keywordMatched: bool, nearMatched: ?string}
     */
    public function run(array $criteria): array
    {
        $notes = [];
        $landmark = $criteria['near'] ? QueryParser::LANDMARKS[$criteria['near']] : null;

        $rows = $this->base($criteria, $criteria['location'])->get();

        // "dekat X": UMKM whose address mentions X, else X's kecamatan.
        $nearMatched = null;
        if ($landmark) {
            $byAddress = $rows->filter(fn (Umkm $u) => $this->mentions($u, $landmark['terms'], withItems: false));
            if ($byAddress->isNotEmpty()) {
                $rows = $byAddress;
                $nearMatched = 'alamat';
            } else {
                $location = $criteria['location'] ?? $landmark['location'];
                $rows = $criteria['location'] ? $rows : $this->base($criteria, $location)->get();
                $nearMatched = 'wilayah';
                $notes[] = "Data UMKM belum menyimpan titik lokasi, jadi saya pakai wilayah {$location} "
                    ."tempat {$landmark['label']} berada.";
            }
        }

        // Specific wish ("kepiting", "kopi susu"): keep the rows that match it,
        // unless none do - then say so and show the broader list.
        $keywordMatched = false;
        $scores = [];
        if ($criteria['keyword']) {
            $terms = array_values(array_filter(explode(' ', $criteria['keyword']), fn ($t) => mb_strlen($t) >= 3));
            foreach ($rows as $u) {
                $scores[$u->id] = $this->score($u, $terms);
            }
            $matching = $rows->filter(fn (Umkm $u) => $scores[$u->id] > 0);
            if ($matching->isNotEmpty()) {
                $rows = $matching;
                $keywordMatched = true;
            } elseif ($rows->isNotEmpty()) {
                $notes[] = "Tidak ada UMKM yang menyebut \"{$criteria['keyword']}\" secara khusus, jadi saya tampilkan yang paling sesuai.";
            }
        }

        return [
            'rows' => $this->sort($rows, $criteria['sort'], $scores)->values(),
            'notes' => $notes,
            'keywordMatched' => $keywordMatched,
            'nearMatched' => $nearMatched,
        ];
    }

    /** Lowest / highest rupiah amount a UMKM advertises (label first, then its products). */
    public static function priceRange(Umkm $u): ?array
    {
        $amounts = PriceParser::amounts($u->price_label);
        if (! $amounts && $u->relationLoaded('items')) {
            foreach ($u->items as $item) {
                array_push($amounts, ...PriceParser::amounts($item->price));
            }
        }

        return $amounts ? [min($amounts), max($amounts)] : null;
    }

    private function base(array $criteria, ?string $location)
    {
        return Umkm::query()->visible()
            ->with(['items', 'photos'])
            ->when($criteria['category'], fn ($q, $c) => $q->where('category', $c))
            ->when($location, fn ($q, $l) => $q->where('location', $l))
            ->when($criteria['openOnly'], fn ($q) => $q->where('status', 'aktif'))
            ->orderByDesc('rating')->orderBy('id')
            ->limit(self::MAX_ROWS);
    }

    /** @param list<string> $terms */
    private function mentions(Umkm $u, array $terms, bool $withItems): bool
    {
        $haystack = $this->haystack($u, $withItems);
        foreach ($terms as $term) {
            if (str_contains($haystack, QueryParser::normalise($term))) {
                return true;
            }
        }

        return false;
    }

    /** @param list<string> $terms */
    private function score(Umkm $u, array $terms): int
    {
        $name = ' '.QueryParser::normalise($u->name).' ';
        $haystack = $this->haystack($u, true);
        $score = 0;
        foreach ($terms as $term) {
            $term = QueryParser::normalise($term);
            if (str_contains($name, $term)) {
                $score += 3;
            } elseif (str_contains($haystack, $term)) {
                $score += 1;
            }
        }

        return $score;
    }

    private function haystack(Umkm $u, bool $withItems): string
    {
        $parts = [$u->name, $u->tag, $u->address, $u->category];
        if ($withItems && $u->relationLoaded('items')) {
            foreach ($u->items as $item) {
                $parts[] = $item->name;
            }
        }

        return ' '.QueryParser::normalise(implode(' ', array_filter($parts))).' ';
    }

    /**
     * @param  Collection<int, Umkm>  $rows
     * @param  array<int, int>  $scores
     * @return Collection<int, Umkm>
     */
    private function sort(Collection $rows, ?string $sort, array $scores): Collection
    {
        $byQuality = fn (Umkm $a, Umkm $b) => [$scores[$b->id] ?? 0, $b->rating, $b->reviews_count, $b->views]
            <=> [$scores[$a->id] ?? 0, $a->rating, $a->reviews_count, $a->views];

        return match ($sort) {
            'price_asc' => $rows->sort(function (Umkm $a, Umkm $b) use ($byQuality) {
                // UMKM without a readable price go last, not first.
                $pa = self::priceRange($a)[0] ?? PHP_INT_MAX;
                $pb = self::priceRange($b)[0] ?? PHP_INT_MAX;

                return $pa <=> $pb ?: $byQuality($a, $b);
            }),
            'price_desc' => $rows->sort(function (Umkm $a, Umkm $b) use ($byQuality) {
                $pa = self::priceRange($a)[1] ?? -1;
                $pb = self::priceRange($b)[1] ?? -1;

                return $pb <=> $pa ?: $byQuality($a, $b);
            }),
            'popular' => $rows->sort(fn (Umkm $a, Umkm $b) => [$b->views, $b->reviews_count] <=> [$a->views, $a->reviews_count] ?: $byQuality($a, $b)),
            default => $rows->sort($byQuality),
        };
    }
}
