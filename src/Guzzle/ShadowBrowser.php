<?php

namespace Ecchi\Guzzle;

use Psr\Http\Message\RequestInterface;

/**
 * Simulates regular browser by sending some additional headers.
 */
class ShadowBrowser
{

    protected static $defaultHeaders = [
        'Connection'      => 'keep-alive',
        'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
        'Accept-Encoding' => 'gzip,deflate,sdch',
        'Accept-Language' => 'en-US,en;q=0.8,sr;q=0.6,hr;q=0.4,ja;q=0.2',
        'User-agent'      => 'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/38.0.2125.111 Safari/537.36',
    ];

    private $headers = [];

    private $nextHandler;

    public function __construct(array $headers = null, callable $nextHandler)
    {
        if ($headers === null) {
            $this->headers = static::$defaultHeaders;
        } else {
            $this->headers = $headers;
        }

        $this->nextHandler = $nextHandler;
    }

    public static function create(array $headers = null)
    {
        return function (callable $nextHandler) use ($headers) {
            return new self($headers, $nextHandler);
        };
    }

    public function __invoke(RequestInterface $request, array $options)
    {
        $fn = $this->nextHandler;

        return $fn(\GuzzleHttp\Psr7\modify_request($request, ['set_headers' => $this->headers]), $options);
    }
}
