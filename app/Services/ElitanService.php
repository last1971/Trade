<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use stdClass;

class ElitanService
{
    public function search(string $name): stdClass
    {
        $client = new Client();

        $jar = CookieJar::fromArray(
            [
                'email_aut' => env('ELITAN_EMAIL_AUT'),
                'mail_hash' => env('ELITAN_MAIL_HASH'),
            ],
            'www.elitan.ru'
        );

        $response = $client->get(
            env('ELITAN_URI'),
            [
                'query' => ['find' => str_replace(' ', '', $name)],
                'cookies' => $jar,
            ],
        );

        return json_decode($response->getBody()->getContents());
    }
}
