<?php

namespace Ecchi\Guzzle;

use GuzzleHttp\Event\BeforeEvent;
use GuzzleHttp\Event\SubscriberInterface;

class AlwaysRefer implements SubscriberInterface
{

    private $lastReferrer;

    public function getEvents()
    {
        return [
            'before' => ['onBefore'],
        ];
    }

    public function onBefore(BeforeEvent $event)
    {
        $request = $event->getRequest();

        if ($this->lastReferrer === null) {
            $this->lastReferrer = $request->getUrl();
        }
        $request->setHeader('referer', $this->lastReferrer);
        $this->lastReferrer = $request->getUrl();
    }
}
