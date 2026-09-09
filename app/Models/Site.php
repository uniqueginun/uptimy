<?php

namespace App\Models;

use Database\Factories\SiteFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read string|null $domain
 */
class Site extends Model
{
    /** @use HasFactory<SiteFactory> */
    use HasFactory;

    protected $fillable = [
        'url',
        'user_id',
    ];

    protected $appends = [
        'domain',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Endpoint, $this>
     */
    public function endpoints(): HasMany
    {
        return $this->hasMany(Endpoint::class);
    }

    /**
     * Match sites whose URL's host is exactly the given domain — i.e. the
     * same host `parse_url($url, PHP_URL_HOST)` (the `domain` accessor
     * below) would return. Written as portable `LIKE` matches rather than
     * a `REGEXP` so it also works against the test suite's SQLite
     * connection, not just MySQL.
     *
     * @param  Builder<Site>  $query
     * @return Builder<Site>
     */
    #[Scope]
    protected function byDomain(Builder $query, string $domain): Builder
    {
        $domain = str_replace(['%', '_'], ['\%', '\_'], $domain);

        return $query->where(function (Builder $query) use ($domain) {
            $query->where('url', 'like', "%://{$domain}")
                ->orWhere('url', 'like', "%://{$domain}/%")
                ->orWhere('url', 'like', "%://{$domain}:%")
                ->orWhere('url', 'like', "%://{$domain}?%");
        });
    }

    /**
     * @return Attribute<string|null, never>
     */
    protected function domain(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, $attributes) => (bool) $attributes['url'] ? parse_url($attributes['url'], PHP_URL_HOST) : null,
        );
    }
}
