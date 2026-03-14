<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Match extends Model
{
    use HasFactory;

    protected $table = 'matches';

    protected $fillable = [
        'team_a', 'team_b', 'team_a_short', 'team_b_short',
        'team_a_logo', 'team_b_logo',
        'match_date', 'venue', 'match_number',
        'season', 'status', 'winning_team', 'win_multiplier', 'notes',
    ];

    protected $casts = [
        'match_date'     => 'datetime',
        'win_multiplier' => 'float',
    ];

    public function polls()
    {
        return $this->hasMany(Poll::class);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('status', 'upcoming')->orderBy('match_date');
    }

    public function isLocked(): bool
    {
        return in_array($this->status, ['live', 'completed', 'cancelled']);
    }

    public function getTeams(): array
    {
        return [$this->team_a_short, $this->team_b_short];
    }
}
