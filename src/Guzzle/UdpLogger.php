<?php

namespace Ecchi\Guzzle;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise\RejectedPromise;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class UdpLogger
{

    private $address;

    private $port;

    private $nextHandler;

    private $socket;

    function __construct($address, $port, callable $nextHandler)
    {
        $this->address     = $address;
        $this->port        = $port;
        $this->nextHandler = $nextHandler;
    }

    public static function create($address, $port)
    {
        return function (callable $nextHandler) use ($address, $port) {
            return new self($address, $port, $nextHandler);
        };
    }

    function __invoke(RequestInterface $request, array $options)
    {
        $fn = $this->nextHandler;

        $this->log(\GuzzleHttp\Psr7\str($request));

        return $fn($request, $options)
            ->then(function (ResponseInterface $response) {
                $this->log(\GuzzleHttp\Psr7\str($response));

                return $response;
            }, function ($reason) {
                if ($reason instanceof RequestException && $response = $reason->getResponse()) {
                    $this->log(\GuzzleHttp\Psr7\str($response));
                } elseif ($reason instanceof \Exception) {
                    $this->log($reason->getMessage());
                } else {
                    $this->log('Transfer error');
                }

                return new RejectedPromise($reason);
            });
    }

    private function log($message)
    {
        if ($this->socket === null) {
            $this->socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        }

        $message = substr($message, 0, 1024 * 48);

        socket_sendto($this->socket, $message, strlen($message), 0, $this->address, $this->port);
    }

    function __destruct()
    {
        if ($this->socket !== null) {
            $this->log(str_repeat('=', 80));
            socket_close($this->socket);
        }
    }
}
