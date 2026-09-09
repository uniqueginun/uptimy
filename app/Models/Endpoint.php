<?php

namespace App\Models;

use Database\Factories\EndpointFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

/**
 * @property int|null $checks_count
 * @property int|null $success_checks_count
 * @property int|null $checks_24h_count
 * @property int|null $success_checks_24h_count
 */
class Endpoint extends Model
{
    /** @use HasFactory<EndpointFactory> */
    use HasFactory;

    protected $guarded = [];

    protected $with = ['lastCheck'];

    /**
     * Interval values (in minutes) the "add an endpoint" form allows.
     *
     * @var array<int, int>
     */
    public const array INTERVAL_OPTIONS = [1, 15, 30, 60];

    /**
     * @return BelongsTo<Site, $this>
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * @return HasMany<EndpointCheck, $this>
     */
    public function checks(): HasMany
    {
        return $this->hasMany(EndpointCheck::class);
    }

    /**
     * @return HasMany<EndpointCheck, $this>
     */
    public function successChecks(): HasMany
    {
        return $this->hasMany(EndpointCheck::class)->successful();
    }

    /**
     * @return HasOne<EndpointCheck, $this>
     */
    public function lastCheck(): HasOne
    {
        return $this->hasOne(EndpointCheck::class)->latestOfMany('checked_at', 'checks');
    }

    /**
     * Lifetime uptime percentage, or null when the endpoint has no checks yet.
     *
     * @return Attribute<float|null, never>
     */
    protected function uptimePercentage(): Attribute
    {
        return Attribute::make(get: function () {
            $this->loadMissingChecksCounts();

            return $this->checks_count > 0
                ? round($this->success_checks_count / $this->checks_count * 100, 2)
                : null;
        });
    }

    /**
     * Uptime percentage over the last 24 hours, or null when the endpoint
     * has no checks in that window yet.
     *
     * @return Attribute<float|null, never>
     */
    protected function uptimePercentage24h(): Attribute
    {
        return Attribute::make(get: function () {
            $this->loadMissingChecksCounts();

            return $this->checks_24h_count > 0
                ? round($this->success_checks_24h_count / $this->checks_24h_count * 100, 2)
                : null;
        });
    }

    /**
     * Load the check counts used by the uptime accessors, unless they were
     * already eager-loaded (e.g. via `Endpoint::withCount(...)`) by the
     * caller — avoids an extra pair of count queries per endpoint.
     */
    protected function loadMissingChecksCounts(): void
    {
        $needed = ['checks_count', 'success_checks_count', 'checks_24h_count', 'success_checks_24h_count'];

        if (empty(array_diff($needed, array_keys($this->attributes)))) {
            return;
        }

        $this->loadCount([
            'checks',
            'successChecks',
            'checks as checks_24h_count' => fn (Builder $query) => $query->where('checked_at', '>=', now()->subDay()),
            'successChecks as success_checks_24h_count' => fn (Builder $query) => $query->where('checked_at', '>=', now()->subDay()),
        ]);
    }

    /**
     * @param  Builder<Endpoint>  $query
     * @return Builder<Endpoint>
     */
    #[Scope]
    protected function due(Builder $query): Builder
    {
        return $query->where(function (Builder $query) {
            $query->whereNull('last_run_at');

            foreach (self::INTERVAL_OPTIONS as $minutes) {
                $query->orWhere(function (Builder $query) use ($minutes) {
                    $query->where('interval', $minutes)
                        ->where('last_run_at', '<=', now()->subMinutes($minutes));
                });
            }
        });
    }

    /**
     * Build the absolute URL this endpoint checks, joining the site's base
     * URL and this endpoint's path with exactly one slash.
     */
    public function fullUrl(): string
    {
        $base = Str::of($this->loadMissing('site')->site->url)->rtrim('/');
        $path = Str::of($this->uri)->start('/');

        return $base->append($path)->toString();
    }

    public function registerCheck(int $statusCode, ?string $body): void
    {
        $isUp = $statusCode >= 200 && $statusCode < 300;

        $this->update(['last_run_at' => now()]);

        $this->checks()->create([
            'checked_at' => now(),
            'http_status_code' => $statusCode,
            'status' => $isUp ? 'up' : 'down',
            // Only keep a body for a non-2xx response, where it helps
            // explain what went wrong; a successful response body isn't
            // worth storing forever.
            'raw_response' => $isUp ? null : Str::limit($body ?? '', 2000),
        ]);
    }

    public function registerError(string $message): void
    {
        $this->update(['last_run_at' => now()]);

        $this->checks()->create([
            'checked_at' => now(),
            'http_status_code' => null,
            'status' => 'error',
            'error_message' => Str::limit($message, 2000),
        ]);
    }
}
