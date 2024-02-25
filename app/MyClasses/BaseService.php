<?php

namespace App\MyClasses;

use Carbon\Carbon;
use Exception;
use GuzzleHttp;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BaseService
{
    private $headers;
    private $client;
    private $withRateLimit;
    private $baseUrl;
    private $source;
    private $rateFileName;
    public const RATE_LIMIT_REQUESTS_COUNT = 100;

    public function __construct($baseUrl, $headers, $withRateLimit, $source, $withHeaders = true)
    {
        $this->baseUrl = $baseUrl;
        if ($withHeaders) {
            $this->headers = $headers;
        }
        $this->withRateLimit = $withRateLimit;
        $this->source = $source;
        $this->rateFileName = $source.'RequestsCount';
        $this->client = new GuzzleHttp\Client();
    }

    public function checkRateLimit() {

        if (!Storage::exists($this->rateFileName)) {
            Storage::put($this->rateFileName, 0);
        }

        if (intval(Storage::get($this->rateFileName))) {
            $requestsCount = intval(Storage::get($this->rateFileName));
            $lastRequestTime = Carbon::now()->format('Y-m-d H:i:s');
            $requestsCount++;
            Storage::put($this->rateFileName, json_encode(['requests_count' => $requestsCount,
                'last_request_time' => $lastRequestTime]));
            if ($requestsCount >= self::RATE_LIMIT_REQUESTS_COUNT) {
                Log::alert('sleep');
                sleep(60);
            }
            return;
        }

        if (Storage::get($this->rateFileName)) {
            $requestData = json_decode(Storage::get($this->rateFileName), true);
            $lastRequestTime = $requestData['last_request_time'];
            $requestsCount = $requestData['requests_count'] + 1;
            $now = Carbon::now();
            if ($now->diffInMinutes($lastRequestTime) > 1) {
                $requestsCount = 0;
                Storage::put($this->rateFileName, json_encode(['requests_count' => $requestsCount,
                    'last_request_time' => $now->format('Y-m-d H:i:s')]));
                return;
            }
            if ($requestsCount >= self::RATE_LIMIT_REQUESTS_COUNT) {
                Log::alert('sleep');
                sleep(60);
                $requestsCount = 0;
                $now = Carbon::now();
            }
            Storage::put($this->rateFileName, json_encode(['requests_count' => $requestsCount,
                'last_request_time' => $now->format('Y-m-d H:i:s')]));
        }
    }

    public function getContents($apiEndpoint) {
        try {
            if ($this->withRateLimit) {
                $this->checkRateLimit();
            }
            $result = $this->client->request('GET', $this->baseUrl . $apiEndpoint, $this->headers);
            return $result->getBody()->getContents();
        } catch (GuzzleHttp\Exception\GuzzleException $e) {
            dd($this->baseUrl);
        }
    }

    public function get($apiEndpoint) {
        if ($this->withRateLimit) {
            $this->checkRateLimit();
        }

        $result = $this->client->request('GET', $this->baseUrl . $apiEndpoint, $this->headers);
        return json_decode($result->getBody(), true);
    }

    public function saveMp3($name) {

        $result = $this->client->request('GET', $this->baseUrl, ['sink' => storage_path('app/'.$name.'.mp3')]);
        return json_decode($result->getBody(), true);
    }

    public function post($apiEndpoint, $postData = [], $useCustomHeaders = true) {
        if ($this->withRateLimit) {
            $this->checkRateLimit();
        }
        if ($useCustomHeaders) {
            $this->headers['headers']['Content-Type'] = 'application/json';
            $this->headers[GuzzleHttp\RequestOptions::JSON] = $postData;
        }
        $result = $this->client->request('POST', $this->baseUrl . $apiEndpoint, $this->headers);
        return json_decode($result->getBody(), true);
    }

    public function put($apiEndpoint, $postData = [], $useCustomHeaders = true) {
        if ($this->withRateLimit) {
            $this->checkRateLimit();
        }
        if ($useCustomHeaders) {
            $this->headers['headers']['Content-Type'] = 'application/json';
            $this->headers[GuzzleHttp\RequestOptions::JSON] = $postData;
        }
        $result = $this->client->request('PUT', $this->baseUrl . $apiEndpoint, $this->headers);
        return json_decode($result->getBody(), true);
    }

    public function delete($apiEndpoint, $postData = [], $useCustomHeaders = true) {
        if ($this->withRateLimit) {
            $this->checkRateLimit();
        }
        if ($useCustomHeaders) {
            $this->headers['headers']['Content-Type'] = 'application/json';
            $this->headers[GuzzleHttp\RequestOptions::JSON] = $postData;
        }
        $result = $this->client->request('DELETE', $this->baseUrl . $apiEndpoint, $this->headers);
        return json_decode($result->getBody(), true);
    }
}
