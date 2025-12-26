<?php
class KinopoiskService {
    private $apiKey;
    private $baseUrl = 'https://api.kinopoisk.dev/v1.4/';
    
    public function __construct($apiKey) {
        $this->apiKey = $apiKey;
    }
    
    public function searchMovie($title) {
        $url = $this->baseUrl . 'movie/search?page=1&limit=1&query=' . urlencode($title);
        return $this->makeRequest($url);
    }
    
    public function getMovieById($id) {
        $url = $this->baseUrl . 'movie/' . $id;
        return $this->makeRequest($url);
    }
    
    public function getMovieReviews($movieId, $page = 1, $limit = 10) {
        $url = $this->baseUrl . 'review?page=' . $page . '&limit=' . $limit . '&movieId=' . $movieId;
        return $this->makeRequest($url);
    }
    
    public function getMovieImages($movieId, $type = 'cover', $page = 1, $limit = 10) {
        $url = $this->baseUrl . 'image?page=' . $page . '&limit=' . $limit . '&movieId=' . $movieId . '&type=' . $type;
        return $this->makeRequest($url);
    }
    
    public function getMovieByExternalId($externalId, $source) {
        $url = $this->baseUrl . 'movie?externalId=' . $externalId . '&externalSource=' . $source;
        return $this->makeRequest($url);
    }
    
    private function makeRequest($url) {
        $ch = curl_init();
        
        $headers = [
            'X-API-KEY: ' . $this->apiKey,
            'Accept: application/json',
            'Content-Type: application/json',
            'User-Agent: PHP-Kinopoisk-Client/1.0'
        ];
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HEADER => false,
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $effectiveUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        error_log("Kinopoisk API Request: " . $url);
        error_log("Kinopoisk API Effective URL: " . $effectiveUrl);
        error_log("Kinopoisk API Response Code: " . $httpCode);
        error_log("Kinopoisk API Response (first 500 chars): " . substr($response, 0, 500));
        
        if ($error) {
            throw new Exception('Kinopoisk API request failed: ' . $error, 500);
        }
        
        if ($httpCode >= 300 && $httpCode < 400) {
            throw new Exception('Kinopoisk API returned redirect (HTTP ' . $httpCode . '). URL may have changed.', $httpCode);
        }
        
        if ($httpCode !== 200) {
            $decodedResponse = json_decode($response, true);
            $message = $decodedResponse['message'] ?? 'Unknown error (HTTP ' . $httpCode . ')';
            
            if ($httpCode === 401) {
                $message = 'Invalid API key or unauthorized access';
            } elseif ($httpCode === 404) {
                $message = 'Resource not found';
            } elseif ($httpCode === 429) {
                $message = 'Rate limit exceeded';
            } elseif ($httpCode === 500) {
                $message = 'Kinopoisk API server error';
            }
            
            throw new Exception('Kinopoisk API error: ' . $message, $httpCode);
        }
        
        $decoded = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON response from Kinopoisk API: ' . json_last_error_msg(), 500);
        }
        
        if (isset($decoded['error']) || isset($decoded['message'])) {
            $errorMsg = $decoded['message'] ?? $decoded['error'] ?? 'Unknown API error';
            throw new Exception('Kinopoisk API error in response: ' . $errorMsg, 500);
        }
        
        return $decoded;
    }
    
    public function getApiKeyInfo() {
        return [
            'has_key' => !empty($this->apiKey),
            'key_prefix' => substr($this->apiKey, 0, 8) . '...'
        ];
    }
}
?>