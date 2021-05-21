<?php


namespace App\Services;

use GuzzleHttp\Psr7\Query;
use GuzzleHttp\Psr7\Uri;

class DigiKeyApiService
{
    public function gettingTheAuthorizationCodeUri(): string
    {
        return Uri::composeComponents(
            env('DIGIKEY_AUTH_ENDPOINT', 'http://localhost'),
            null,
            'v1/oauth2/authorize',
            Query::build([
                'response_type' => 'code',
                'client_id' => env('DIGIKEY_CLIENT_ID', '123456789abcdefg'),
                'redirect_uri' => env('DIGIKEY_REDIRECT', 'http://localhost')
            ]),
            null
        );
    }

}
