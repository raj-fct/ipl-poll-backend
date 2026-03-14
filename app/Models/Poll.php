<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Poll extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'match_id', 'selected_team',
        'bid_amount', 'status', 'coins_earned',
    ];

    protected $casts = [
        'bid_amount'   => 'integer',
        'coins_earned' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function match()
    {
        return $this->belongsTo(Match::class);
    }

    public function isWinner(string $winningTeam): bool
    {
        return $this->selected_team === $winningTeam;
    }

    public function calculateWinnings(float $multiplier): int
    {
        return (int) floor($this->bid_amount * $multiplier);
    }
}
