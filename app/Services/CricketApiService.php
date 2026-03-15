<?php

namespace App\Services;

use App\Models\Season;
use App\Models\Team;
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

    /**
     * Fetch all IPL matches for a given season year.
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
     * Fetch a single match by ESPN event ID (summary endpoint — includes toss).
     */
    public function fetchMatch(string $espnId): ?array
    {
        $response = $this->request("summary", ['event' => $espnId]);

        if (!$response) {
            return null;
        }

        $event = $response['header']['competitions'][0] ?? null;
        if (!$event) {
            return null;
        }

        return $this->parseSummaryEvent($response, $espnId);
    }

    /**
     * Fetch toss info for a match from the summary endpoint.
     * Returns ['toss_winner' => 'CSK', 'toss_decision' => 'bat'] or null.
     */
    public function fetchToss(string $espnId): ?array
    {
        $response = $this->request("summary", ['event' => $espnId]);

        if (!$response) {
            return null;
        }

        return $this->parseToss($response);
    }

    /**
     * Parse toss data from summary response notes.
     * ESPN stores toss as: { "text": "Team Name , elected to bat/field first", "type": "toss" }
     */
    protected function parseToss(array $response): ?array
    {
        $notes = $response['notes'] ?? [];

        foreach ($notes as $note) {
            if (($note['type'] ?? '') === 'toss' && !empty($note['text'])) {
                $text = $note['text'];

                // Parse "Royal Challengers Bengaluru , elected to field first"
                // or "Chennai Super Kings, elected to bat first"
                $tossWinner = null;
                $tossDecision = null;

                if (preg_match('/^(.+?)\s*,\s*elected to (bat|field|bowl)/i', $text, $m)) {
                    $tossWinner = trim($m[1]);
                    $decision = strtolower($m[2]);
                    $tossDecision = ($decision === 'bowl') ? 'field' : $decision;
                }

                return [
                    'toss_winner'   => $tossWinner,
                    'toss_decision' => $tossDecision,
                    'toss_text'     => $text,
                ];
            }
        }

        return null;
    }

    /**
     * Find or create a Team record from ESPN data.
     */
    public function findOrCreateTeam(array $espnTeam): Team
    {
        $team = Team::where('espn_id', (string) $espnTeam['id'])->first();

        if ($team) {
            $team->update(array_filter([
                'name'       => $espnTeam['displayName'] ?? $espnTeam['name'] ?? $team->name,
                'short_name' => $espnTeam['abbreviation'] ?? $team->short_name,
                'logo'       => $espnTeam['logo'] ?? $team->logo,
                'color'      => $espnTeam['color'] ?? $team->color,
            ]));
            return $team;
        }

        return Team::create([
            'espn_id'    => (string) $espnTeam['id'],
            'name'       => $espnTeam['displayName'] ?? $espnTeam['name'] ?? $espnTeam['abbreviation'],
            'short_name' => $espnTeam['abbreviation'] ?? '',
            'logo'       => $espnTeam['logo'] ?? null,
            'color'      => $espnTeam['color'] ?? null,
        ]);
    }

    /**
     * Find or create a Season record.
     */
    public function findOrCreateSeason(int $year): Season
    {
        return Season::firstOrCreate(
            ['year' => $year],
            [
                'name'           => "IPL {$year}",
                'espn_league_id' => '8048',
            ]
        );
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
        $state = $statusType['state'] ?? 'pre';
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

        // Summary
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
            'team_a_espn_id' => (string) ($team1['id'] ?? ''),
            'team_b_espn_id' => (string) ($team2['id'] ?? ''),
            'team_a_raw'     => $team1,
            'team_b_raw'     => $team2,
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

        // Parse toss from notes
        $toss = $this->parseToss($response);

        return [
            'espn_id'        => $espnId,
            'match_number'   => $matchNumber,
            'description'    => $desc,
            'team_a'         => $team1['displayName'] ?? $team1['name'] ?? '',
            'team_b'         => $team2['displayName'] ?? $team2['name'] ?? '',
            'team_a_short'   => $team1['abbreviation'] ?? '',
            'team_b_short'   => $team2['abbreviation'] ?? '',
            'team_a_logo'    => $team1['logo'] ?? null,
            'team_b_logo'    => $team2['logo'] ?? null,
            'team_a_espn_id' => (string) ($team1['id'] ?? ''),
            'team_b_espn_id' => (string) ($team2['id'] ?? ''),
            'team_a_raw'     => $team1,
            'team_b_raw'     => $team2,
            'status'         => $status,
            'winning_team'   => $winningTeam,
            'score_a'        => $comp1['score'] ?? null,
            'score_b'        => $comp2['score'] ?? null,
            'toss_winner'    => $toss['toss_winner'] ?? null,
            'toss_decision'  => $toss['toss_decision'] ?? null,
            'summary'        => $statusType['shortDetail'] ?? null,
        ];
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
