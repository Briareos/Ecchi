<?php

namespace Ecchi\Guzzle;

use GuzzleHttp\Event\BeforeEvent;
use GuzzleHttp\Event\SubscriberInterface;

/**
 * Simulates regular browser by sending some additional headers.
 */
class ShadowBrowser implements SubscriberInterface
{

    protected static $defaultHeaders = [
        'Connection'      => 'keep-alive',
        'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
        'Accept-Encoding' => 'gzip,deflate,sdch',
        'Accept-Language' => 'en-US,en;q=0.8,sr;q=0.6,hr;q=0.4,ja;q=0.2',
        'User-agent'      => 'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/38.0.2125.111 Safari/537.36',
    ];

    private $headers = [];

    public function __construct(array $headers = null)
    {
        if ($headers === null) {
            $this->headers = static::$defaultHeaders;

            return;
        }

        $this->headers = $headers;
    }

    public function getEvents()
    {
        return [
            'before' => ['onBefore'],
        ];
    }

    public function onBefore(BeforeEvent $event)
    {
        $request = $event->getRequest();
        foreach ($this->headers as $headerName => $headerValue) {
            $request->setHeader($headerName, $headerValue);
        }
    }
}
