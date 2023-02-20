<?php

namespace App\MyClasses;

use Illuminate\Support\Facades\Log;

class Service
{
    private $headers;
    private $source;

    public function __construct() {
        $token = SystemUpwork::value('access_token');
        $this->headers = [
            'headers' => ['Authorization' => 'Bearer '. $token]
        ];
        $this->source = 'Upwork';
    }

    public function getTeams() {
        $baseService = new BaseService(SourceTimeTrackingLog::BASE_URL_UPWORK_API, $this->headers, false,
            $this->source);
        $result = $baseService->get('hr/v2/teams.json');
        return $result['teams'];
    }

    public function getTrackedTime($team, $startDate, $endDate) {
        $baseService = new BaseService(SourceTimeTrackingLog::BASE_URL_UPWORK_TIME_REPORTS, $this->headers,
            false, $this->source);
        $res = $baseService->get("timereports/v1/companies/{$team['parent_team__id']}/agencies/{$team['id']}"
            . "?tq=SELECT worked_on, provider_name, assignment_name, task, provider_id, assignment_ref, memo,"
            . "sum(hours)  WHERE worked_on >= '$startDate' AND worked_on <= '$endDate'");
        if (isset($res['errors'])) {
            Log::error($res['errors'][0]['message']);
        }
        if (isset($res['table'])) {
            return $res['table'];
        }
        return [];
    }

    public function refreshToken()
    {
        $this->headers = [
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Authorization' => 'Basic ' . base64_encode(env('UPWORK_CLIENT_ID').':'
                        .env('UPWORK_CLIENT_SECRET')),
            ],
            'form_params' => [
                'grant_type' => 'refresh_token',
                'client_id' => env('UPWORK_CLIENT_ID'),
                'refresh_token' => SystemUpwork::value('refresh_token'),
                'client_secret' => env('UPWORK_CLIENT_SECRET')
            ]

        ];
        $baseService = new BaseService(SourceTimeTrackingLog::BASE_URL_UPWORK_API, $this->headers, false,
            $this->source);
        $res = $baseService->post('v3/oauth2/token', [], false);
        $tokenId = SystemUpwork::where('refresh_token', SystemUpwork::value('refresh_token'))->value('id');
        $tokenData = SystemUpwork::find($tokenId);
        $tokenData->update([
            'access_token' => $res['access_token'],
            'refresh_token' => $res['refresh_token'],
            'expires' => $res['expires_in'],
        ]);
    }

    public function getAccessToken($code) {
        $this->headers = [
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Authorization' => 'Basic ' . base64_encode(env('UPWORK_CLIENT_ID').':'
                        .env('UPWORK_CLIENT_SECRET'))
            ],
            'form_params' => [
                'grant_type' => 'authorization_code',
                'client_id' => env('UPWORK_CLIENT_ID'),
                'code' => $code,
                'redirect_uri' =>  env('UPWORK_REDIRECT_URI'),
                'client_secret' => env('UPWORK_CLIENT_SECRET')
            ]
        ];
        $baseService = new BaseService(SourceTimeTrackingLog::BASE_URL_UPWORK_API, $this->headers, false,
            $this->source);
        return $baseService->post('v3/oauth2/token', [], false);
    }
}
