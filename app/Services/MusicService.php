<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class MusicService
{
    protected string $clientId;
    protected string $baseUrl;

    public function __construct()
    {
        $this->clientId = config('services.jamendo.client_id', '703c0591');
        $this->baseUrl = 'https://api.jamendo.com/v3.0';
    }

    /**
     * Search for tracks, albums, and artists by query.
     */
    public function search(string $query)
    {
        $cacheKey = 'jamendo:search:' . md5($query);

        return Cache::remember($cacheKey, now()->addMinutes(60), function () use ($query) {
            try {
                // 1. Search Artists
                $artistsResponse = Http::timeout(10)->get($this->baseUrl . '/artists/', [
                    'client_id' => $this->clientId,
                    'format' => 'json',
                    'namesearch' => $query,
                    'limit' => 5,
                ]);

                $artists = [];
                if ($artistsResponse->successful()) {
                    $results = $artistsResponse->json('results') ?? [];
                    $artists = array_map(function ($artist) {
                        return [
                            'id' => 'artist_' . $artist['id'],
                            'type' => 'artist',
                            'title' => $artist['name'] ?? 'Unknown',
                            'artist' => 'Artist',
                            'thumbnail' => $artist['image'] ?? '',
                            'duration' => '',
                        ];
                    }, $results);
                }

                // 2. Search Albums
                $albumsResponse = Http::timeout(10)->get($this->baseUrl . '/albums/', [
                    'client_id' => $this->clientId,
                    'format' => 'json',
                    'namesearch' => $query,
                    'limit' => 5,
                    'imagesize' => '200',
                ]);

                $albums = [];
                if ($albumsResponse->successful()) {
                    $results = $albumsResponse->json('results') ?? [];
                    $albums = array_map(function ($album) {
                        return [
                            'id' => 'album_' . $album['id'],
                            'type' => 'album',
                            'title' => $album['name'] ?? 'Unknown Album',
                            'artist' => $album['artist_name'] ?? 'Unknown Artist',
                            'thumbnail' => $album['image'] ?? '',
                            'duration' => '',
                        ];
                    }, $results);
                }

                // 3. Search Tracks
                $tracksResponse = Http::timeout(10)->get($this->baseUrl . '/tracks/', [
                    'client_id' => $this->clientId,
                    'format' => 'json',
                    'namesearch' => $query,
                    'limit' => 15,
                    'include' => 'musicinfo',
                    'imagesize' => '200',
                ]);

                $tracks = [];
                if ($tracksResponse->successful()) {
                    $results = $tracksResponse->json('results') ?? [];
                    $tracks = array_map(function ($track) {
                        return [
                            'id' => $track['id'],
                            'type' => 'video',
                            'title' => $track['name'] ?? 'Unknown',
                            'artist' => $track['artist_name'] ?? 'Unknown Artist',
                            'thumbnail' => $track['image'] ?? $track['album_image'] ?? '',
                            'duration' => gmdate("i:s", $track['duration'] ?? 0),
                        ];
                    }, $results);
                }

                return array_merge($artists, $albums, $tracks);
            } catch (\Exception $e) {
                Log::error('Jamendo API Search Error: ' . $e->getMessage());
                return [];
            }
        });
    }

    /**
     * Get top charts or popular items.
     */
    public function getCharts()
    {
        $cacheKey = 'jamendo:charts';

        return Cache::remember($cacheKey, now()->addMinutes(60), function () {
            try {
                $response = Http::timeout(10)->get($this->baseUrl . '/tracks/', [
                    'client_id' => $this->clientId,
                    'format' => 'json',
                    'limit' => 20,
                    'order' => 'popularity_total',
                    'imagesize' => '200',
                ]);

                if ($response->successful()) {
                    $results = $response->json('results') ?? [];
                    return array_map(function ($track) {
                        return [
                            'id' => $track['id'],
                            'title' => $track['name'] ?? 'Unknown',
                            'artist' => $track['artist_name'] ?? 'Unknown Artist',
                            'thumbnail' => $track['image'] ?? $track['album_image'] ?? '',
                        ];
                    }, $results);
                }

                Log::error('Jamendo API Charts Error: ' . $response->body());
                return [];
            } catch (\Exception $e) {
                Log::error('Jamendo API Connection Error: ' . $e->getMessage());
                return [];
            }
        });
    }

    /**
     * Get all home sections.
     */
    public function getHomeSections()
    {
        $cacheKey = 'jamendo:home_sections';

        return Cache::remember($cacheKey, now()->addMinutes(60), function () {
            $sections = [];

            // 1. Popular Hits
            $popular = $this->getCharts();
            if (!empty($popular)) {
                $sections[] = [
                    'title' => 'Popular Hits',
                    'contents' => array_map(function ($track) {
                        return array_merge($track, ['type' => 'video']);
                    }, $popular)
                ];
            }

            // 2. Hot Albums
            $albums = $this->getPopularAlbums(10);
            if (!empty($albums)) {
                $sections[] = [
                    'title' => 'Hot Albums',
                    'contents' => $albums
                ];
            }

            // 3. Genre Rock
            $rock = $this->getGenreTracks('rock', 10);
            if (!empty($rock)) {
                $sections[] = [
                    'title' => 'Rock Anthems',
                    'contents' => $rock
                ];
            }

            // 4. Genre Acoustic
            $acoustic = $this->getGenreTracks('acoustic', 10);
            if (!empty($acoustic)) {
                $sections[] = [
                    'title' => 'Chill Acoustic',
                    'contents' => $acoustic
                ];
            }

            // 5. Genre Electronic
            $electronic = $this->getGenreTracks('electronic', 10);
            if (!empty($electronic)) {
                $sections[] = [
                    'title' => 'Electronic Beats',
                    'contents' => $electronic
                ];
            }

            return $sections;
        });
    }

    /**
     * Helper to get tracks by genre/tag.
     */
    protected function getGenreTracks(string $genre, int $limit = 10)
    {
        try {
            $response = Http::timeout(10)->get($this->baseUrl . '/tracks/', [
                'client_id' => $this->clientId,
                'format' => 'json',
                'limit' => $limit,
                'fuzzytags' => $genre,
                'order' => 'popularity_total',
                'imagesize' => '200',
            ]);

            if ($response->successful()) {
                $results = $response->json('results') ?? [];
                return array_map(function ($track) {
                    return [
                        'id' => $track['id'],
                        'type' => 'video',
                        'title' => $track['name'] ?? 'Unknown',
                        'artist' => $track['artist_name'] ?? 'Unknown Artist',
                        'thumbnail' => $track['image'] ?? $track['album_image'] ?? '',
                    ];
                }, $results);
            }
        } catch (\Exception $e) {
            Log::error("Jamendo Genre {$genre} Error: " . $e->getMessage());
        }

        return [];
    }

    /**
     * Get popular albums.
     */
    public function getPopularAlbums(int $limit = 10)
    {
        try {
            $response = Http::timeout(10)->get($this->baseUrl . '/albums/', [
                'client_id' => $this->clientId,
                'format' => 'json',
                'limit' => $limit,
                'order' => 'popularity_total',
                'imagesize' => '200',
            ]);

            if ($response->successful()) {
                $results = $response->json('results') ?? [];
                return array_map(function ($album) {
                    return [
                        'id' => 'album_' . $album['id'],
                        'type' => 'album',
                        'title' => $album['name'] ?? 'Unknown Album',
                        'artist' => $album['artist_name'] ?? 'Unknown Artist',
                        'thumbnail' => $album['image'] ?? '',
                    ];
                }, $results);
            }
        } catch (\Exception $e) {
            Log::error("Jamendo Popular Albums Error: " . $e->getMessage());
        }

        return [];
    }

    /**
     * Get playlist or album details by ID.
     */
    public function getPlaylistDetails(string $id)
    {
        $cacheKey = "jamendo:album:{$id}";

        return Cache::remember($cacheKey, now()->addMinutes(60), function () use ($id) {
            try {
                $response = Http::timeout(10)->get($this->baseUrl . '/albums/tracks/', [
                    'client_id' => $this->clientId,
                    'format' => 'json',
                    'id' => $id,
                    'imagesize' => '200',
                ]);

                if ($response->successful()) {
                    $results = $response->json('results') ?? [];
                    if (empty($results)) {
                        return null;
                    }
                    $album = $results[0];

                    return [
                        'id' => $album['id'],
                        'title' => $album['name'] ?? 'Unknown Album',
                        'description' => 'Album by ' . ($album['artist_name'] ?? 'Unknown'),
                        'author' => $album['artist_name'] ?? 'Unknown',
                        'trackCount' => count($album['tracks'] ?? []),
                        'thumbnails' => $album['image'] ?? '',
                        'tracks' => array_map(function ($t) use ($album) {
                            return [
                                'id' => $t['id'],
                                'title' => $t['name'] ?? 'Unknown',
                                'artist' => $album['artist_name'] ?? 'Unknown',
                                'thumbnail' => $album['image'] ?? '',
                                'duration' => gmdate("i:s", $t['duration'] ?? 0),
                            ];
                        }, $album['tracks'] ?? []),
                    ];
                }

                Log::error('Jamendo API Album Error: ' . $response->body());
                return null;
            } catch (\Exception $e) {
                Log::error('Jamendo API Connection Error: ' . $e->getMessage());
                return null;
            }
        });
    }

    /**
     * Get track details by ID.
     */
    public function getTrack(string $id)
    {
        $cacheKey = 'jamendo:track:' . $id;

        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        try {
            $response = Http::timeout(10)->get($this->baseUrl . '/tracks/', [
                'client_id' => $this->clientId,
                'format' => 'json',
                'id' => $id,
            ]);

            if ($response->successful()) {
                $results = $response->json('results') ?? [];
                if (empty($results)) {
                    return null;
                }
                $track = $results[0];

                $data = [
                    'id' => $track['id'],
                    'title' => $track['name'] ?? 'Unknown',
                    'author' => $track['artist_name'] ?? 'Unknown',
                    'thumbnail' => $track['image'] ?? $track['album_image'] ?? '',
                    'stream_url' => $track['audio'] ?? '',
                ];

                if (!empty($data['stream_url'])) {
                    Cache::put($cacheKey, $data, now()->addMinutes(60));
                }

                return $data;
            }

            Log::error('Jamendo API Track Error: ' . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error('Jamendo API Connection Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get artist details and tracks by ID.
     */
    public function getArtistDetails(string $id)
    {
        $cacheKey = "jamendo:artist:{$id}";

        return Cache::remember($cacheKey, now()->addMinutes(60), function () use ($id) {
            try {
                // 1. Fetch artist profile
                $artistResponse = Http::timeout(10)->get($this->baseUrl . '/artists/', [
                    'client_id' => $this->clientId,
                    'format' => 'json',
                    'id' => $id,
                ]);

                if (!$artistResponse->successful()) {
                    Log::error('Jamendo API Artist Profile Error: ' . $artistResponse->body());
                    return null;
                }

                $artistResults = $artistResponse->json('results') ?? [];
                if (empty($artistResults)) {
                    return null;
                }
                $artist = $artistResults[0];

                // 2. Fetch artist tracks
                $tracksResponse = Http::timeout(10)->get($this->baseUrl . '/tracks/', [
                    'client_id' => $this->clientId,
                    'format' => 'json',
                    'artist_id' => $id,
                    'limit' => 20,
                    'order' => 'popularity_total',
                    'imagesize' => '200',
                ]);

                $tracks = [];
                if ($tracksResponse->successful()) {
                    $results = $tracksResponse->json('results') ?? [];
                    $tracks = array_map(function ($track) {
                        return [
                            'id' => $track['id'],
                            'title' => $track['name'] ?? 'Unknown',
                            'artist' => $track['artist_name'] ?? 'Unknown Artist',
                            'thumbnail' => $track['image'] ?? $track['album_image'] ?? '',
                            'duration' => gmdate("i:s", $track['duration'] ?? 0),
                        ];
                    }, $results);
                }

                return [
                    'id' => $artist['id'],
                    'title' => $artist['name'] ?? 'Unknown Artist',
                    'description' => 'Artist profile on Jamendo',
                    'author' => 'Jamendo Artist',
                    'trackCount' => count($tracks),
                    'thumbnails' => $artist['image'] ?? '',
                    'tracks' => $tracks,
                ];
            } catch (\Exception $e) {
                Log::error('Jamendo API Artist Connection Error: ' . $e->getMessage());
                return null;
            }
        });
    }

    /**
     * Get recommended tracks based on a track's genres/tags or artist.
     */
    public function getRecommendations(string $trackId)
    {
        $cacheKey = 'jamendo:recommendations:' . $trackId;

        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        try {
            // 1. Fetch current track details including musicinfo for tags
            $response = Http::timeout(10)->get($this->baseUrl . '/tracks/', [
                'client_id' => $this->clientId,
                'format' => 'json',
                'id' => $trackId,
                'include' => 'musicinfo',
            ]);

            if (!$response->successful()) {
                return [];
            }

            $results = $response->json('results') ?? [];
            if (empty($results)) {
                return [];
            }

            $track = $results[0];
            $artistId = $track['artist_id'] ?? null;
            
            // Get genres from musicinfo
            $genres = $track['musicinfo']['tags']['genres'] ?? [];
            
            $params = [
                'client_id' => $this->clientId,
                'format' => 'json',
                'limit' => 10,
                'order' => 'popularity_total',
                'imagesize' => '200',
            ];

            // If genres are available, use fuzzytags for recommendations
            if (!empty($genres)) {
                $params['fuzzytags'] = implode(' ', array_slice($genres, 0, 2));
            } elseif ($artistId) {
                $params['artist_id'] = $artistId;
            } else {
                $params['order'] = 'popularity_total';
            }

            $recResponse = Http::timeout(10)->get($this->baseUrl . '/tracks/', $params);

            if ($recResponse->successful()) {
                $recResults = $recResponse->json('results') ?? [];
                
                // Exclude the current track
                $filtered = array_filter($recResults, function ($t) use ($trackId) {
                    return $t['id'] !== $trackId;
                });

                $data = array_map(function ($t) {
                    return [
                        'id' => $t['id'],
                        'type' => 'video',
                        'title' => $t['name'] ?? 'Unknown',
                        'artist' => $t['artist_name'] ?? 'Unknown Artist',
                        'thumbnail' => $t['image'] ?? $t['album_image'] ?? '',
                        'duration' => gmdate("i:s", $t['duration'] ?? 0),
                    ];
                }, array_slice($filtered, 0, 5));

                if (!empty($data)) {
                    Cache::put($cacheKey, $data, now()->addMinutes(60));
                }

                return $data;
            }

            return [];
        } catch (\Exception $e) {
            Log::error('Jamendo recommendations fetch failed: ' . $e->getMessage());
            return [];
        }
    }
}
