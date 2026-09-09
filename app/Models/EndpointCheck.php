<?php

namespace App\Models;

use App\Enums\CheckStatus;
use Database\Factories\EndpointCheckFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EndpointCheck extends Model
{
    /** @use HasFactory<EndpointCheckFactory> */
    use HasFactory;

    protected $guarded = [];

    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => CheckStatus::class,
            'checked_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Endpoint, $this>
     */
    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(Endpoint::class);
    }

    /**
     * @param  Builder<EndpointCheck>  $query
     * @return Builder<EndpointCheck>
     */
    #[Scope]
    protected function successful(Builder $query): Builder
    {
        return $query->where('status', CheckStatus::Up->value);
    }
}
