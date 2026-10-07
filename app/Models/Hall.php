<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'capacity', 'is_composite', 'is_active'])]
class Hall extends Model
{
    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'is_composite' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Component halls that make up this (composite) hall — e.g. full marquee
     * is composed of Opal + Sapphire.
     */
    public function components(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'hall_components', 'hall_id', 'component_hall_id')
            ->withTimestamps();
    }

    /**
     * Composite halls this hall is a component of — e.g. Opal is part of the
     * full marquee.
     */
    public function compositesContainingThis(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'hall_components', 'component_hall_id', 'hall_id')
            ->withTimestamps();
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
