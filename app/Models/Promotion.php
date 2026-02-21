<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Promotion extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'audio_id',
        'network_id',
        'mformat',
        'channel_id',
        'priority',
        'version',
        'visible',
        'start_at',
        'end_at',
        'created_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'visible' => 'bool',
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'created_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Relationship: a promotion belongs to an audio.
     */
    public function audio(): BelongsTo
    {
        return $this->belongsTo(Audio::class);
    }

    /**
     * Scope a query to only include visible promotions.
     */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('visible', true);
    }

    /**
     * Scope a query to only include promotions active at a given moment.
     */
    public function scopeActiveAt(Builder $query, CarbonInterface $moment): Builder
    {
        return $query
            ->whereNull('deleted_at')
            ->where('start_at', '<=', $moment)
            ->where('end_at', '>=', $moment);
    }

    /**
     * Scope a query to only include promotions for given audio IDs.
     */
    public function scopeForAudioIds(Builder $query, array $audioIds): Builder
    {
        return $query->whereIn('audio_id', $audioIds);
    }
}
