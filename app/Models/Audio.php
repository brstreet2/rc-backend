<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Audio extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title',
        'network_id',
        'mformat',
        'channel_id',
    ];

    /**
     * Relationship: An audio can have many promotions.
     */
    public function promotions(): HasMany
    {
        return $this->hasMany(Promotion::class);
    }
}
