<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CricketApiService
{
    /**
     * ESPN open API — no key required, returns full match data.
     * League ID 8048 = Indian Premier League.
     */
    protected string $baseUrl = 'https://site.api.espn.com/apis/site/v2/sports/cricket/8048';

    // Full team names for each short code
    protected array $teamFullNames = [
        'CSK'  => 'Chennai Super Kings',
        'MI'   => 'Mumbai Indians',
        'RCB'  => 'Royal Challengers Bengaluru',
        'KKR'  => 'Kolkata Knight Riders',
        'DC'   => 'Delhi Capitals',
        'PBKS' => 'Punjab Kings',
        'RR'   => 'Rajasthan Royals',
        'SRH'  => 'Sunrisers Hyderabad',
        'GT'   => 'Gujarat Titans',
        'LSG'  => 'Lucknow Super Giants',
    ];

    /**
     * Fetch all IPL matches for a given season year.
     *
     * Returns parsed array of matches with:
     *   espn_id, match_number, team_a, team_b, team_a_short, team_b_short,
     *   team_a_logo, team_b_logo, match_date, venue, status, winning_team,
     *   score_a, score_b, summary
     */
    public function fetchMatches(int $seasonYear): array
    {
        $response = $this->request('scoreboard', [
            'season' => $seasonYear,
            'limit'  => 100,
        ]);

        if (!$response) {
            return [];
        }

        $events = $response['events'] ?? [];
        $matches = [];

        foreach ($events as $index => $event) {
            $parsed = $this->parseEvent($event, $index + 1);
            if ($parsed) {
                $matches[] = $parsed;
            }
        }

        return $matches;
    }

    /**
     * Fetch a single match by ESPN event ID.
     */
    public function fetchMatch(string $espnId): ?array
    {
        // Use the summary endpoint for detailed match info
        $response = $this->request("summary", ['event' => $espnId]);

        if (!$response) {
            return null;
        }

        $event = $response['header']['competitions'][0] ?? null;
        if (!$event) {
            return null;
        }

        // Build a compatible event structure
        return $this->parseSummaryEvent($response, $espnId);
    }

    /**
     * Parse a full event object from the scoreboard API.
     */
    protected function parseEvent(array $event, int $fallbackNumber = 0): ?array
    {
        $competition = $event['competitions'][0] ?? null;
        if (!$competition || count($competition['competitors'] ?? []) < 2) {
            return null;
        }

        $comp1 = $competition['competitors'][0];
        $comp2 = $competition['competitors'][1];
        $team1 = $comp1['team'];
        $team2 = $comp2['team'];

        // Parse match number from description like "1st Match", "23rd Match"
        $desc = $competition['description'] ?? $competition['shortDescription'] ?? '';
        $matchNumber = $fallbackNumber;
        if (preg_match('/^(\d+)/', $desc, $m)) {
            $matchNumber = (int) $m[1];
        }

        // Determine status
        $statusType = $event['status']['type'] ?? [];
        $state = $statusType['state'] ?? 'pre';  // pre, in, post
        $status = match ($state) {
            'post' => 'completed',
            'in'   => 'live',
            default => 'upcoming',
        };

        // Determine winner
        $winningTeam = null;
        if ($status === 'completed') {
            if ($comp1['winner'] === 'true' || $comp1['winner'] === true) {
                $winningTeam = $team1['abbreviation'];
            } elseif ($comp2['winner'] === 'true' || $comp2['winner'] === true) {
                $winningTeam = $team2['abbreviation'];
            }
        }

        // Parse date
        $matchDate = null;
        try {
            $matchDate = Carbon::parse($event['date'])->setTimezone('Asia/Kolkata');
        } catch (\Exception $e) {
            $matchDate = now();
        }

        // Venue
        $venue = $competition['venue']['fullName'] ?? null;

        // Summary (e.g., "RCB won by 7 wkts (22b rem)")
        $summary = $event['status']['summary'] ?? $statusType['detail'] ?? null;

        return [
            'espn_id'        => (string) $event['id'],
            'match_number'   => $matchNumber,
            'description'    => $desc,
            'team_a'         => $team1['displayName'] ?? $team1['name'],
            'team_b'         => $team2['displayName'] ?? $team2['name'],
            'team_a_short'   => $team1['abbreviation'],
            'team_b_short'   => $team2['abbreviation'],
            'team_a_logo'    => $team1['logo'] ?? null,
            'team_b_logo'    => $team2['logo'] ?? null,
            'team_a_color'   => $team1['color'] ?? null,
            'team_b_color'   => $team2['color'] ?? null,
            'team_a_id'      => $team1['id'] ?? null,
            'team_b_id'      => $team2['id'] ?? null,
            'match_date'     => $matchDate,
            'venue'          => $venue,
            'status'         => $status,
            'winning_team'   => $winningTeam,
            'score_a'        => $comp1['score'] ?? null,
            'score_b'        => $comp2['score'] ?? null,
            'summary'        => $summary,
            'linescores_a'   => $comp1['linescores'] ?? [],
            'linescores_b'   => $comp2['linescores'] ?? [],
        ];
    }

    /**
     * Parse the summary endpoint response.
     */
    protected function parseSummaryEvent(array $response, string $espnId): ?array
    {
        $header = $response['header'] ?? [];
        $competition = $header['competitions'][0] ?? null;

        if (!$competition || count($competition['competitors'] ?? []) < 2) {
            return null;
        }

        $comp1 = $competition['competitors'][0];
        $comp2 = $competition['competitors'][1];
        $team1 = $comp1['team'] ?? [];
        $team2 = $comp2['team'] ?? [];

        $statusType = $competition['status']['type'] ?? [];
        $state = $statusType['state'] ?? 'pre';
        $status = match ($state) {
            'post' => 'completed',
            'in'   => 'live',
            default => 'upcoming',
        };

        $winningTeam = null;
        if ($status === 'completed') {
            if (($comp1['winner'] ?? false) === true || ($comp1['winner'] ?? '') === 'true') {
                $winningTeam = $team1['abbreviation'] ?? null;
            } elseif (($comp2['winner'] ?? false) === true || ($comp2['winner'] ?? '') === 'true') {
                $winningTeam = $team2['abbreviation'] ?? null;
            }
        }

        $desc = $competition['description'] ?? '';
        $matchNumber = 0;
        if (preg_match('/^(\d+)/', $desc, $m)) {
            $matchNumber = (int) $m[1];
        }

        return [
            'espn_id'      => $espnId,
            'match_number' => $matchNumber,
            'description'  => $desc,
            'team_a'       => $team1['displayName'] ?? $team1['name'] ?? '',
            'team_b'       => $team2['displayName'] ?? $team2['name'] ?? '',
            'team_a_short' => $team1['abbreviation'] ?? '',
            'team_b_short' => $team2['abbreviation'] ?? '',
            'team_a_logo'  => $team1['logo'] ?? null,
            'team_b_logo'  => $team2['logo'] ?? null,
            'status'       => $status,
            'winning_team' => $winningTeam,
            'score_a'      => $comp1['score'] ?? null,
            'score_b'      => $comp2['score'] ?? null,
            'summary'      => $statusType['shortDetail'] ?? null,
        ];
    }

    /**
     * Get full team name from short code.
     */
    public function getTeamFullName(string $shortCode): string
    {
        return $this->teamFullNames[strtoupper($shortCode)] ?? $shortCode;
    }

    /**
     * Make API request to ESPN.
     */
    protected function request(string $endpoint, array $params = []): ?array
    {
        try {
            $url = "{$this->baseUrl}/{$endpoint}";

            $response = Http::timeout(20)
                ->withHeaders([
                    'Accept' => 'application/json',
                ])
                ->get($url, $params);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error("ESPN API failed: {$endpoint} — HTTP {$response->status()}");
            return null;
        } catch (\Exception $e) {
            Log::error("ESPN API error: {$endpoint} — {$e->getMessage()}");
            return null;
        }
    }
}
