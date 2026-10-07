<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['booking_id', 'name', 'urdu_name', 'sort'])]
class BookingFood extends Model
{
    // "Food" is uncountable, so the inferred table would be booking_food.
    protected $table = 'booking_foods';

    protected function casts(): array
    {
        return [
            'sort' => 'integer',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
