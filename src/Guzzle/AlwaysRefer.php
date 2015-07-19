<?php

namespace Ecchi\Guzzle;

use Psr\Http\Message\RequestInterface;

class AlwaysRefer
{
    private $nextHandler;

    private $lastReferrer;

    public function __construct(callable $nextHandler)
    {
        $this->nextHandler = $nextHandler;
    }

    public static function create()
    {
        return function (callable $nextHandler) {
            return new self($nextHandler);
        };
    }

    public function __invoke(RequestInterface $request, array $options)
    {
        $fn                 = $this->nextHandler;
        $referrer           = $this->lastReferrer;
        $this->lastReferrer = $request->getUri()->__toString();

        return $fn($request->withHeader('referer', $referrer), $options);
    }
}
