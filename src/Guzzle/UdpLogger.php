<?php

namespace Ecchi\Guzzle;

use GuzzleHttp\Event\BeforeEvent;
use GuzzleHttp\Event\CompleteEvent;
use GuzzleHttp\Event\RequestEvents;
use GuzzleHttp\Event\SubscriberInterface;

class UdpLogger implements SubscriberInterface
{

    private $address;

    private $port;

    private $socket;

    function __construct($address, $port)
    {
        $this->address = $address;
        $this->port    = $port;
    }

    public function getEvents()
    {
        return [
            'complete' => ['onComplete', RequestEvents::VERIFY_RESPONSE + 100],
        ];
    }

    public function onComplete(CompleteEvent $event)
    {
        $this->log($event->getRequest()->__toString());

        if (!$event->hasResponse()) {
            return;
        }
        $this->log($event->getResponse()->__toString());
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
