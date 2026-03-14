<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class CricketApiService
{
    protected string $baseUrl = 'https://api.cricapi.com/v1';
    protected string $apiKey;

    // IPL team name → short code mapping
    protected array $teamMap = [
        'Chennai Super Kings'       => 'CSK',
        'Mumbai Indians'            => 'MI',
        'Royal Challengers Bengaluru' => 'RCB',
        'Royal Challengers Bangalore' => 'RCB',
        'Kolkata Knight Riders'     => 'KKR',
        'Delhi Capitals'            => 'DC',
        'Punjab Kings'              => 'PBKS',
        'Kings XI Punjab'           => 'PBKS',
        'Rajasthan Royals'          => 'RR',
        'Sunrisers Hyderabad'       => 'SRH',
        'Gujarat Titans'            => 'GT',
        'Lucknow Super Giants'      => 'LSG',
    ];

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

    public function __construct()
    {
        $this->apiKey = config('services.cricapi.key', '');
    }

    /**
     * Search for IPL series and return the series ID.
     */
    public function findIplSeries(string $season = 'IPL 2025'): ?array
    {
        $searchTerm = str_contains($season, 'IPL') ? 'IPL' : $season;

        $response = $this->request('series', ['search' => $searchTerm]);

        if (!$response || $response['status'] !== 'success') {
            return null;
        }

        // Find the matching series
        foreach ($response['data'] ?? [] as $series) {
            if (stripos($series['name'], $season) !== false ||
                stripos($series['name'], str_replace('IPL ', 'Indian Premier League ', $season)) !== false) {
                return $series;
            }
        }

        return null;
    }

    /**
     * Get all matches for a series.
     */
    public function getSeriesMatches(string $seriesId): array
    {
        $response = $this->request('series_info', ['id' => $seriesId]);

        if (!$response || $response['status'] !== 'success') {
            return [];
        }

        return $response['data']['matchList'] ?? [];
    }

    /**
     * Get detailed info for a specific match.
     */
    public function getMatchInfo(string $matchId): ?array
    {
        $response = $this->request('match_info', ['id' => $matchId]);

        if (!$response || $response['status'] !== 'success') {
            return null;
        }

        return $response['data'] ?? null;
    }

    /**
     * Get current/live matches.
     */
    public function getCurrentMatches(): array
    {
        $response = $this->request('currentMatches');

        if (!$response || $response['status'] !== 'success') {
            return [];
        }

        // Filter for IPL T20 matches only
        return collect($response['data'] ?? [])
            ->filter(fn ($m) => ($m['matchType'] ?? '') === 't20' && $this->isIplMatch($m))
            ->values()
            ->toArray();
    }

    /**
     * Get all matches (paginated).
     */
    public function getAllMatches(int $offset = 0): array
    {
        $response = $this->request('matches', ['offset' => $offset]);

        if (!$response || $response['status'] !== 'success') {
            return [];
        }

        return $response['data'] ?? [];
    }

    /**
     * Resolve team short code from full team name.
     */
    public function getTeamShortCode(string $teamName): ?string
    {
        // Direct match
        if (isset($this->teamMap[$teamName])) {
            return $this->teamMap[$teamName];
        }

        // Fuzzy match
        foreach ($this->teamMap as $name => $code) {
            if (stripos($teamName, $name) !== false || stripos($name, $teamName) !== false) {
                return $code;
            }
        }

        // Try matching by key words
        foreach ($this->teamMap as $name => $code) {
            $words = explode(' ', $name);
            foreach ($words as $word) {
                if (strlen($word) > 3 && stripos($teamName, $word) !== false) {
                    return $code;
                }
            }
        }

        return null;
    }

    /**
     * Get full team name from short code.
     */
    public function getTeamFullName(string $shortCode): string
    {
        return $this->teamFullNames[strtoupper($shortCode)] ?? $shortCode;
    }

    /**
     * Check if a match object is an IPL match.
     */
    public function isIplMatch(array $match): bool
    {
        $name = strtolower($match['name'] ?? '');
        $seriesName = strtolower($match['series'] ?? $match['seriesName'] ?? '');

        return str_contains($name, 'ipl') ||
               str_contains($seriesName, 'indian premier league') ||
               str_contains($seriesName, 'ipl');
    }

    /**
     * Parse match winner from status string.
     * e.g., "Chennai Super Kings won by 6 wickets" → "CSK"
     */
    public function parseWinnerFromStatus(array $match): ?string
    {
        // First check matchWinner field
        if (!empty($match['matchWinner'])) {
            return $this->getTeamShortCode($match['matchWinner']);
        }

        // Parse from status string
        $status = $match['status'] ?? '';
        if (empty($status) || str_contains(strtolower($status), 'no result')) {
            return null;
        }

        // "Team X won by ..."
        if (preg_match('/^(.+?)\s+won\s+by/i', $status, $matches)) {
            return $this->getTeamShortCode(trim($matches[1]));
        }

        return null;
    }

    /**
     * Determine match status from API data.
     */
    public function determineMatchStatus(array $match): string
    {
        $status = strtolower($match['status'] ?? '');
        $matchWinner = $match['matchWinner'] ?? null;

        if ($matchWinner || str_contains($status, 'won') || str_contains($status, 'no result') || str_contains($status, 'tied')) {
            return 'completed';
        }

        if (str_contains($status, 'innings break') || str_contains($status, 'batting') ||
            str_contains($status, 'bowling') || str_contains($status, 'need') ||
            str_contains($status, 'trail') || str_contains($status, 'lead')) {
            return 'live';
        }

        return 'upcoming';
    }

    /**
     * Make API request.
     */
    protected function request(string $endpoint, array $params = []): ?array
    {
        if (empty($this->apiKey)) {
            Log::error('CricketApiService: API key not configured. Set CRICAPI_KEY in .env');
            return null;
        }

        $params['apikey'] = $this->apiKey;

        try {
            $response = Http::timeout(15)
                ->get("{$this->baseUrl}/{$endpoint}", $params);

            if ($response->successful()) {
                $data = $response->json();

                // Log API usage
                $info = $data['info'] ?? [];
                if (isset($info['hitsToday'])) {
                    Log::debug("CricAPI usage: {$info['hitsToday']}/{$info['hitsLimit']} hits today");
                }

                return $data;
            }

            Log::error("CricAPI request failed: {$endpoint} - HTTP {$response->status()}");
            return null;
        } catch (\Exception $e) {
            Log::error("CricAPI request error: {$endpoint} - {$e->getMessage()}");
            return null;
        }
    }
}
