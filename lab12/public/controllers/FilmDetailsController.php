<?php
require_once 'models/Film.php';
require_once 'services/KinopoiskService.php';

class FilmDetailsController {
    private $filmModel;
    private $kinopoiskService;
    private $kinopoiskApiKey;
    
    public function __construct($db, $kinopoiskApiKey) {
        error_log("FilmDetailsController: Constructor called");
        error_log("FilmDetailsController: API Key provided: " . (!empty($kinopoiskApiKey) ? 'YES (' . substr($kinopoiskApiKey, 0, 8) . '...)' : 'NO'));
        
        $this->filmModel = new Film($db);
        $this->kinopoiskService = new KinopoiskService($kinopoiskApiKey);
        $this->kinopoiskApiKey = $kinopoiskApiKey;
        
        error_log("FilmDetailsController: Service created: " . (($this->kinopoiskService !== null) ? 'YES' : 'NO'));
    }
    
    public function getFilmDetails($filmId, $fields = ['details']) {
        try {
            error_log("==============================================");
            error_log("FilmDetailsController::getFilmDetails called");
            error_log("Film ID: " . $filmId);
            error_log("Fields requested: " . implode(', ', $fields));
            
            $filmData = $this->filmModel->getById($filmId);
            
            error_log("Film found in DB: " . ($filmData ? 'YES' : 'NO'));
            if ($filmData) {
                error_log("Film title: " . $filmData['title']);
                error_log("Film ID: " . $filmData['film_id']);
            }
            
            $kinopoiskData = null;
            $kinopoiskMovieId = null;
            
            try {
                error_log("Searching Kinopoisk for: '" . $filmData['title'] . "'");
                
                $searchResult = $this->kinopoiskService->searchMovie($filmData['title']);
                
                error_log("Kinopoisk search completed");
                error_log("Has docs: " . (isset($searchResult['docs']) ? 'YES' : 'NO'));
                error_log("Docs count: " . (isset($searchResult['docs']) ? count($searchResult['docs']) : 0));
                
                if (isset($searchResult['docs']) && count($searchResult['docs']) > 0) {
                    $kinopoiskData = $searchResult['docs'][0];
                    $kinopoiskMovieId = $kinopoiskData['id'] ?? null;
                    
                    error_log("Found in Kinopoisk! ID: " . $kinopoiskMovieId);
                    error_log("Kinopoisk name: " . ($kinopoiskData['name'] ?? 'N/A'));
                    error_log("Kinopoisk year: " . ($kinopoiskData['year'] ?? 'N/A'));
                } else {
                    error_log("No movies found in Kinopoisk");
                    if (isset($searchResult)) {
                        error_log("Search result: " . json_encode($searchResult));
                    }
                }
            } catch (Exception $e) {
                error_log('KINOPOISK SEARCH ERROR: ' . $e->getMessage());
                error_log('Error code: ' . $e->getCode());
                error_log('Error trace: ' . $e->getTraceAsString());
            }
            
            $response = [
                'success' => true,
                'data' => [],
                'debug' => [
                    'film_id' => $filmId,
                    'kinopoisk_found' => ($kinopoiskMovieId !== null),
                    'kinopoisk_movie_id' => $kinopoiskMovieId,
                    'api_key_set' => !empty($this->kinopoiskApiKey)
                ]
            ];
            
            $response['data']['film'] = [
                'film_id' => $filmData['film_id'],
                'title' => $filmData['title'],
                'rental_duration' => $filmData['rental_duration'],
                'rental_rate' => $filmData['rental_rate'],
                'replacement_cost' => $filmData['replacement_cost'],
                'description' => $filmData['description'],
                'release_year' => $filmData['release_year'],
                'rating' => $filmData['rating']
            ];
            
            if ($kinopoiskMovieId) {
                error_log("Processing fields for Kinopoisk ID: " . $kinopoiskMovieId);
                
                foreach ($fields as $field) {
                    error_log("Processing field: " . $field);
                    switch ($field) {
                        case 'details':
                            $response['data']['kinopoisk_details'] = $kinopoiskData;
                            break;
                            
                        case 'rating':
                            $response['data']['rating'] = $kinopoiskData['rating'] ?? ['kp' => null, 'imdb' => null];
                            break;
                            
                        case 'persons':
                            $response['data']['persons'] = $kinopoiskData['persons'] ?? [];
                            break;
                            
                        case 'similar':
                            $response['data']['similar'] = $kinopoiskData['similarMovies'] ?? [];
                            break;
                            
                        case 'reviews':
                            try {
                                error_log("Fetching reviews...");
                                $reviews = $this->kinopoiskService->getMovieReviews($kinopoiskMovieId);
                                $response['data']['reviews'] = $reviews['docs'] ?? [];
                                error_log("Found " . count($response['data']['reviews']) . " reviews");
                            } catch (Exception $e) {
                                error_log('Failed to fetch reviews: ' . $e->getMessage());
                                $response['data']['reviews'] = [
                                    'error' => 'Could not fetch reviews',
                                    'message' => $e->getMessage()
                                ];
                            }
                            break;
                            
                        case 'images':
                            try {
                                error_log("Fetching images...");
                                $images = $this->kinopoiskService->getMovieImages($kinopoiskMovieId, 'cover');
                                $response['data']['images'] = $images['docs'] ?? [];
                                error_log("Found " . count($response['data']['images']) . " images");
                            } catch (Exception $e) {
                                error_log('Failed to fetch images: ' . $e->getMessage());
                                $response['data']['images'] = [
                                    'error' => 'Could not fetch images',
                                    'message' => $e->getMessage()
                                ];
                            }
                            break;
                    }
                }
            } else {
                error_log("Kinopoisk movie not found. Adding error info.");
                $response['data']['kinopoisk_error'] = 'Film not found in Kinopoisk database';
                $response['data']['kinopoisk_search_debug'] = [
                    'searched_title' => $filmData['title'],
                    'api_key_set' => !empty($this->kinopoiskApiKey),
                    'api_key_prefix' => !empty($this->kinopoiskApiKey) ? substr($this->kinopoiskApiKey, 0, 8) . '...' : 'NO KEY'
                ];
                
                foreach ($fields as $field) {
                    switch ($field) {
                        case 'details':
                            $response['data']['kinopoisk_details'] = ['error' => 'Not found in Kinopoisk'];
                            break;
                        case 'rating':
                            $response['data']['rating'] = ['kp' => null, 'imdb' => null];
                            break;
                        case 'persons':
                            $response['data']['persons'] = [];
                            break;
                        case 'similar':
                            $response['data']['similar'] = [];
                            break;
                        case 'reviews':
                            $response['data']['reviews'] = [];
                            break;
                        case 'images':
                            $response['data']['images'] = [];
                            break;
                    }
                }
            }
            
            error_log("Returning response");
            error_log("Response structure: " . json_encode(array_keys($response['data'])));
            error_log("==============================================");
            
            return $response;
            
        } catch (Exception $e) {
            error_log('FilmDetailsController GENERAL ERROR: ' . $e->getMessage());
            error_log('Error trace: ' . $e->getTraceAsString());
            throw $e;
        }
    }
}
?>