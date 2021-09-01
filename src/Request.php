<?php

namespace Balsama;

use GuzzleHttp\Psr7\Request as BaseRequest;

class Request
{
    public static function getRequest($url): BaseRequest
    {
        // The DA has allow-listed a specific user agent string for this bot so it doesn't get blocked by their bot
        // detector. In the interest of not publicizing the magic string, it's hidden behind an env variable.
        $userAgent = getenv('CREDIT_COUNTER_USER_AGENT') ?: null;
        $requestHeaders = ['User-Agent' => $userAgent];
        return new BaseRequest('GET', $url, $requestHeaders);
    }
}
