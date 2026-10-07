<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Backing store for the atomic, gapless NumberSequenceService.
 */
#[Fillable(['key', 'next'])]
class NumberSequence extends Model
{
    protected function casts(): array
    {
        return [
            'next' => 'integer',
        ];
    }
}
