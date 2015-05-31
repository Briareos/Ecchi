<?php

namespace Ecchi\Guzzle;

use GuzzleHttp\Event\CompleteEvent;
use GuzzleHttp\Event\RequestEvents;
use GuzzleHttp\Event\SubscriberInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class CloudflareSkipper implements SubscriberInterface
{
    public function getEvents()
    {
        return [
            'complete' => ['onComplete', RequestEvents::VERIFY_RESPONSE + 1]
        ];
    }

    public function onComplete(CompleteEvent $event)
    {
        if (!$event->hasResponse()) {
            return;
        }

        if ($event->getResponse()->getStatusCode() !== 503) {
            return;
        }

        $response = $event->getResponse();

        $crawler = new Crawler((string) $response->getBody(), $response->getEffectiveUrl());

        // Get the challenge, should be a simple arithmetic expression.
        preg_match('/var t,r,a,f, ((\w+)=\{.*;)/', (string) $response->getBody(), $match);
        $row1 = $match[1];
        preg_match('/'.$match[2].'\..*;/', (string) $response->getBody(), $match);
        $row2 = $match[0];
        $js   = <<<JAVASCRIPT
t = 'javjunkies.com';
a = {
  value: ''
};
$row1
$row2
console.log(a.value);
JAVASCRIPT;

        $process = new Process('node', null, null, $js, 5);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $challenge  = trim($process->getOutput());
        $form       = $crawler->filter('#challenge-form')->form(['jschl_answer' => $challenge]);
        $newRequest = $event->getClient()->createRequest($form->getMethod(), $form->getUri());
        $newRequest->setHeader('referer', $response->getEffectiveUrl());

        $newResponse = $event->getClient()->send($newRequest);

        $event->intercept($newResponse);
    }
}
