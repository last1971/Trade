<?php


namespace App\Services;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Query;
use GuzzleHttp\Psr7\Uri;
use Illuminate\Support\Facades\Cache;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class DigiKeyApiService
{
    /**
     * @var Client
     */
    private Client $client;

    /**
     * @var string
     */
    private string $baseUri;

    /**
     * @var string
     */
    private string $clientId;

    /**
     * @var string
     */
    private string $clientSecret;

    /**
     * @var string
     */
    private string $token;

    /**
     * @var string
     */
    private string $refreshToken;

    /**
     * @var string
     */
    private string $redirectUri;

    /**
     * DigiKeyApiService constructor.
     */
    public function __construct()
    {
        $this->baseUri = env('DIGIKEY_ENDPOINT', 'http://localhost');
        $this->clientId = env('DIGIKEY_CLIENT_ID', '123456789abcdefg');
        $this->clientSecret = env('DIGIKEY_CLIENT_SECRET', '123456789abcdefg');
        $this->redirectUri = env('DIGIKEY_REDIRECT', url()->current());

        $this->token = Cache::get('DIGIKEY_TOKEN', '123456789abcdefg');
        $this->refreshToken = Cache::get('DIGIKEY_REFRESH', '123456789abcdefg');

        $this->client = new Client([
            'base_uri' => $this->baseUri,
            'headers' => [
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    /**
     * @param ResponseInterface $response
     * @throws Throwable
     */
    private function obtainTokens(ResponseInterface $response): void
    {
        throw_if(
            $response->getStatusCode() !== 200,
            new Exception('DigiKey response status code = ' . $response->getStatusCode() !== 200)
        );
        $json = json_decode($response->getBody(), true);
        $this->token = $json['access_token'];
        Cache::put('DIGIKEY_TOKEN', $this->token);
        $this->refreshToken = $json['refresh_token'];
        Cache::put('DIGIKEY_REFRESH', $this->refreshToken);
    }

    /**
     * Getting the Authorization Code Uri
     * @return string
     */
    public function gettingTheAuthorizationCodeUri(): string
    {
        return Uri::composeComponents(
            null,
            null,
            $this->baseUri . 'v1/oauth2/authorize',
            Query::build([
                'response_type' => 'code',
                'client_id' => $this->clientId,
                'redirect_uri' => $this->redirectUri,
            ]),
            null
        );
    }

    /**
     * Getting the Access Token
     * @throws Throwable
     */
    public function gettingTheAccessToken(string $code): void
    {
        $response = $this->client->post(
            'v1/oauth2/token',
            [
                'form_params' => [
                    'code' => $code,
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'redirect_uri' => $this->redirectUri,
                    'grant_type' => 'authorization_code',
                ]
            ]
        );
        $this->obtainTokens($response);
    }

    /**
     * @throws GuzzleException
     * @throws Throwable
     */
    public function usingTheRefreshToken(): void
    {
        $response = $this->client->post(
            'v1/oauth2/token',
            [
                'form_params' => [
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'refresh_token' => $this->refreshToken,
                    'grant_type' => 'refresh_token',
                ]
            ]
        );
        $this->obtainTokens($response);
    }

    /**
     * @param string $search
     * @return array
     * @throws GuzzleException
     */
    public function keywordSearch(string $search): array
    {
        $response = $this->client->post(
            'Search/v3/Products/Keyword',
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->token,
                    'X-DIGIKEY-Client-Id' => $this->clientId,
                ],
                'json' => [
                    'Keywords' => $search,
                    'RecordCount' => 50,
                ],
            ]
        );
        return json_decode($response->getBody(), true);
    }

    /**
     * @param string $search
     * @return array
     * @throws GuzzleException
     * @throws Throwable
     */
    public function keywordSearchWithRefreshToken(string $search): array
    {
        try {
            return $this->keywordSearch($search);
        } catch (GuzzleException $e) {
            if ($e->getCode() === 401) {
                $this->usingTheRefreshToken();
                return $this->keywordSearch($search);
            }
            throw $e;
        }
    }
}
