<?php

namespace Webbesoft\Doorman\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webbesoft\Doorman\Models\UserAnalytic;

class UserAnalyticFactory extends Factory
{
    protected $model = UserAnalytic::class;

    public function definition(): array
    {
        return [
            'identifier' => $this->faker->uuid,
            'identifier_type' => $this->faker->randomElement(['user', 'session', 'ip']),
            'date' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'page' => $this->faker->randomElement(['/', '/about', '/contact', '/products']),
            'ref' => $this->faker->optional()->url,
            'country' => $this->faker->countryCode,
        ];
    }

    public function user(): static
    {
        return $this->state(fn (array $attributes) => [
            'identifier' => (string) $this->faker->numberBetween(1, 1000),
            'identifier_type' => 'user',
        ]);
    }

    public function session(): static
    {
        return $this->state(fn (array $attributes) => [
            'identifier' => $this->faker->sha256,
            'identifier_type' => 'session',
        ]);
    }

    public function ip(): static
    {
        return $this->state(fn (array $attributes) => [
            'identifier' => hash('sha256', $this->faker->ipv4),
            'identifier_type' => 'ip',
        ]);
    }
}
