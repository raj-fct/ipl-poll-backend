<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IplMatch extends Model
{
    use HasFactory;

    protected $table = 'matches';

    protected $fillable = [
        'espn_id', 'season_id', 'team_a_id', 'team_b_id',
        'team_a', 'team_b', 'team_a_short', 'team_b_short',
        'team_a_logo', 'team_b_logo',
        'score_a', 'score_b',
        'match_date', 'venue', 'match_number',
        'season', 'status', 'winning_team',
        'toss_winner', 'toss_decision',
        'win_multiplier', 'notes',
    ];

    protected $casts = [
        'match_date'     => 'datetime',
        'win_multiplier' => 'float',
    ];

    public function polls()
    {
        return $this->hasMany(Poll::class, 'match_id');
    }

    public function seasonRecord()
    {
        return $this->belongsTo(Season::class, 'season_id');
    }

    public function teamA()
    {
        return $this->belongsTo(Team::class, 'team_a_id');
    }

    public function teamB()
    {
        return $this->belongsTo(Team::class, 'team_b_id');
    }

    public function scopeUpcoming($query)
    {
        return $query->where('status', 'upcoming')->orderBy('match_date');
    }

    public function scopeForSeason($query, $seasonId)
    {
        return $query->where('season_id', $seasonId);
    }

    public function isLocked(): bool
    {
        return in_array($this->status, ['live', 'completed', 'cancelled']);
    }

    public function isPollsClosed(): bool
    {
        return $this->isLocked() || now()->gte($this->match_date->subMinutes(30));
    }

    public function getTeams(): array
    {
        return [$this->team_a_short, $this->team_b_short];
    }
}
