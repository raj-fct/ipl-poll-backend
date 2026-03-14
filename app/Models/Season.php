<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Season extends Model
{
    protected $fillable = [
        'year', 'name', 'espn_league_id', 'cricinfo_series_id', 'is_active',
    ];

    protected $casts = [
        'year'      => 'integer',
        'is_active' => 'boolean',
    ];

    public function matches()
    {
        return $this->hasMany(IplMatch::class, 'season_id');
    }

    public static function active(): ?self
    {
        return static::where('is_active', true)->first();
    }
}
