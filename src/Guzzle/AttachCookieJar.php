<?php

namespace Ecchi\Guzzle;

use GuzzleHttp\Cookie\CookieJarInterface;
use Psr\Http\Message\RequestInterface;

class AttachCookieJar
{
    private $cookieJar;

    private $nextHandler;

    public function __construct(CookieJarInterface $cookieJar, callable $nextHandler)
    {
        $this->cookieJar   = $cookieJar;
        $this->nextHandler = $nextHandler;
    }

    public static function create(CookieJarInterface $cookieJar)
    {
        return function (callable $nextHandler) use ($cookieJar) {
            return new self($cookieJar, $nextHandler);
        };
    }

    function __invoke(RequestInterface $request, array $options)
    {
        $fn = $this->nextHandler;

        $options['cookies'] = $this->cookieJar;

        return $fn($request, $options);
    }
}
