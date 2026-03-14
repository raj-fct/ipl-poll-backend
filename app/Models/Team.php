<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    protected $fillable = [
        'espn_id', 'name', 'short_name', 'logo', 'color',
    ];

    public function matchesAsTeamA()
    {
        return $this->hasMany(IplMatch::class, 'team_a_id');
    }

    public function matchesAsTeamB()
    {
        return $this->hasMany(IplMatch::class, 'team_b_id');
    }
}
