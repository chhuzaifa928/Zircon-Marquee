<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'name', 'care_of', 'phone', 'cnic', 'email', 'address'])]
class Customer extends Model
{
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
