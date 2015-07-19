<?php

namespace Ecchi\Guzzle;

use Ecchi\Util\CloudFlareChallengeSolver;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class CloudFlareSkipper
{
    private $challengeSolver;

    private $nextHandler;

    public function __construct(CloudFlareChallengeSolver $challengeSolver, callable $nextHandler)
    {
        $this->challengeSolver = $challengeSolver;
        $this->nextHandler     = $nextHandler;
    }

    public static function create(CloudFlareChallengeSolver $challengeSolver)
    {
        return function (callable $nextHandler) use ($challengeSolver) {
            return new self($challengeSolver, $nextHandler);
        };
    }

    public function __invoke(RequestInterface $request, array $options)
    {
        $fn = $this->nextHandler;

        return $fn($request, $options)
            ->then(function (ResponseInterface $response) use ($request, $options) {
                if (empty($options[__CLASS__.'_applied']) && $response->getStatusCode() === 503) {
                    $newRequest = $this->challengeSolver->createRequestFromResponse($request->getUri(), $response);
                    $options[__CLASS__.'_applied'] = true;
                    // CF hardened their protection; you have to wait a bit before sending the request.
                    $options['delay'] = 3000;

                    return $this($newRequest, $options);
                }

                return $response;
            });
    }
}
