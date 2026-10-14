<?php

namespace Database\Seeders;

use App\Enums\DomainStatus;
use App\Models\Domain;
use Illuminate\Database\Seeder;

class DomainSeeder extends Seeder
{
    private const COUNT = 200;

    private const SUFFIXES = ['hub', 'pro', 'zone', 'central', 'guide', 'now', 'daily', 'world', 'base', 'spot', 'club', 'lab', 'review', 'expert', 'direct'];

    private const PREFIXES = ['best', 'top', 'my', 'the', 'smart', 'pure', 'real', 'easy', 'prime', 'first'];

    private const TLDS = ['com', 'com', 'com', 'com', 'com', 'net', 'org', 'io', 'co', 'info'];

    /** Status mix: mostly live so the marketplace looks populated. */
    private const STATUSES = [
        'live' => 150, 'pending' => 20, 'draft' => 15, 'sold' => 15,
    ];

    public function run(): void
    {
        mt_srand(2026);
        $niches = require __DIR__.'/data/niches.php';
        $statusPool = collect(self::STATUSES)
            ->flatMap(fn (int $n, string $s) => array_fill(0, $n, $s))
            ->shuffle()
            ->values();

        $names = [];
        $rows = [];

        while (count($rows) < self::COUNT) {
            $niche = array_rand($niches);
            $stem = $niches[$niche][array_rand($niches[$niche])];
            $name = $this->compose($stem);
            $tld = self::TLDS[array_rand(self::TLDS)];
            $full = "$name.$tld";

            if (isset($names[$full])) {
                continue;
            }
            $names[$full] = true;

            $year = random_int(1998, (int) now()->year - 1);
            $age = (int) now()->year - $year;
            $authority = min(96, max(4, random_int(8, 50) + (int) round($age * 1.3) + random_int(-6, 12)));
            $referring = (int) round($authority ** 1.6 * (random_int(30, 140) / 100));
            $backlinks = $referring * random_int(3, 40);
            $price = max(1200, (int) round(($authority ** 2 * 1.6 + $referring * 4 + $age * 35) * (random_int(50, 160) / 100) / 10) * 10);
            $status = $statusPool[count($rows)];

            $rows[] = [
                'name' => $full,
                'tld' => $tld,
                'registered_year' => $year,
                'backlinks' => $backlinks,
                'referring_domains' => $referring,
                'authority_score' => $authority,
                'niche' => $niche,
                'price_cents' => $price * 100,
                'status' => $status,
                'sold_at' => $status === DomainStatus::Sold->value ? now()->subDays(random_int(1, 120)) : null,
                'notes' => $this->notes($niche, $age, $referring),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        foreach (array_chunk($rows, 100) as $chunk) {
            Domain::insert($chunk);
        }

        // Bulk insert bypasses model events, so index explicitly for Scout.
        Domain::query()->searchable();
    }

    private function compose(string $stem): string
    {
        return match (random_int(0, 3)) {
            0 => $stem.self::SUFFIXES[array_rand(self::SUFFIXES)],
            1 => self::PREFIXES[array_rand(self::PREFIXES)].$stem,
            2 => $stem.random_int(1, 99),
            default => self::PREFIXES[array_rand(self::PREFIXES)].$stem.self::SUFFIXES[array_rand(self::SUFFIXES)],
        };
    }

    private function notes(string $niche, int $age, int $referring): ?string
    {
        $options = [
            "Clean {$niche} history, no spam or penalties. {$age} years of continuous registration.",
            "Strong editorial backlink profile from {$referring} referring domains.",
            'Previously a small content site; archive.org snapshots available.',
            "Great fit for a {$niche} brand or affiliate site.",
            null,
        ];

        return $options[array_rand($options)];
    }
}
