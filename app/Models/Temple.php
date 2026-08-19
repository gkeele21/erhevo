<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Temple extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'address',
        'city',
        'state',
        'country',
        'latitude',
        'longitude',
        'photo_url',
        'dedicated_on',
        'source_url',
    ];

    protected $casts = [
        'dedicated_on' => 'date',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function visits(): HasMany
    {
        return $this->hasMany(TempleVisit::class);
    }
}
