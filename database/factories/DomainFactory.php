<?php

namespace Database\Factories;

use App\Enums\DomainStatus;
use App\Models\Domain;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Domain>
 */
class DomainFactory extends Factory
{
    /** Weighted: .com dominates, as in the real aftermarket. */
    private const TLDS = ['com', 'com', 'com', 'com', 'net', 'org', 'io', 'co', 'info', 'biz'];

    private const NICHES = [
        'Fitness', 'Finance', 'Crypto', 'Travel', 'Health', 'Real Estate', 'Technology', 'Gaming',
        'Food', 'Fashion', 'Education', 'Automotive', 'Pets', 'Home & Garden', 'Marketing', 'Legal',
    ];

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $tld = fake()->randomElement(self::TLDS);
        $year = fake()->numberBetween(1998, (int) now()->year - 1);
        $age = (int) now()->year - $year;
        $authority = min(100, max(1, (int) round(fake()->numberBetween(5, 55) + $age * 1.4 + fake()->numberBetween(-8, 15))));
        $referring = (int) round($authority ** 1.6 * fake()->randomFloat(2, 0.3, 1.4));
        $backlinks = (int) round($referring * fake()->randomFloat(2, 3, 40));
        $price = max(1200, (int) round(($authority ** 2 * 1.6 + $referring * 4 + $age * 35) * fake()->randomFloat(2, 0.5, 1.6) / 10) * 10);

        return [
            'name' => fake()->unique()->domainWord().fake()->unique()->numerify('####').'.'.$tld,
            'tld' => $tld,
            'registered_year' => $year,
            'backlinks' => $backlinks,
            'referring_domains' => $referring,
            'authority_score' => $authority,
            'niche' => fake()->randomElement(self::NICHES),
            'price_cents' => $price * 100,
            'status' => DomainStatus::Live,
            'notes' => fake()->boolean(70) ? fake()->sentence(12) : null,
        ];
    }

    public function status(DomainStatus $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }

    public function live(): static
    {
        return $this->status(DomainStatus::Live);
    }

    public function pending(): static
    {
        return $this->status(DomainStatus::Pending);
    }

    public function sold(): static
    {
        return $this->state(fn () => ['status' => DomainStatus::Sold, 'sold_at' => now()]);
    }
}
