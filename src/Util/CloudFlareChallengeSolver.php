<?php

namespace Ecchi\Util;

use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Process\Process;

class CloudFlareChallengeSolver
{
    /**
     * @param UriInterface      $url      Effective URL.
     * @param ResponseInterface $response Raw HTTP response.
     *
     * @return RequestInterface Raw HTTP request.
     */
    public function createRequestFromResponse(UriInterface $url, ResponseInterface $response)
    {
        $body    = $response->getBody()->__toString();
        $crawler = new Crawler($body, $url->__toString());

        // Get the challenge, should be a simple arithmetic expression.
        preg_match('/var t,r,a,f, ((\w+)=\{.*;)/', $body, $match);
        $row1 = $match[1];
        preg_match('/'.$match[2].'\..*;/', $body, $match);
        $row2 = $match[0];
        $js   = sprintf(<<<JAVASCRIPT
t = %s;
a = {
  value: ''
};
$row1
$row2
console.log(a.value);
JAVASCRIPT
            , json_encode($url->getHost()));

        $process = new Process('node', null, null, $js, 5);
        $process->mustRun();

        $challenge = trim($process->getOutput());
        $form      = $crawler->filter('#challenge-form')->form(['jschl_answer' => $challenge]);

        $request = new Request($form->getMethod(), $form->getUri());

        return $request;
    }
}
