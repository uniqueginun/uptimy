<?php

namespace Database\Factories;

use App\Enums\CheckStatus;
use App\Models\Endpoint;
use App\Models\EndpointCheck;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EndpointCheck>
 */
class EndpointCheckFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'endpoint_id' => Endpoint::factory(),
            'checked_at' => now(),
            'http_status_code' => 200,
            'status' => CheckStatus::Up,
            'error_message' => null,
            'raw_response' => null,
        ];
    }

    public function down(): static
    {
        return $this->state(fn (array $attributes) => [
            'http_status_code' => 500,
            'status' => CheckStatus::Down,
            'raw_response' => 'Internal Server Error',
        ]);
    }

    public function error(): static
    {
        return $this->state(fn (array $attributes) => [
            'http_status_code' => null,
            'status' => CheckStatus::Error,
            'error_message' => 'Connection timed out.',
        ]);
    }
}
