<?php

namespace Ecchi\MainBundle\Controller;

use GuzzleHttp\Message\ResponseInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class JavController extends Controller
{
    /**
     * @Route("/jav-search")
     */
    public function searchAction(Request $request)
    {
        $client = $this->get('client');

        $search = $request->query->get('search');
        $page   = $request->query->getInt('page', 1);
        $url    = sprintf('http://javjunkies.com/main/%s/%s/page/%d/', ($this->isCategory($search) ? 'tag' : 'search'), urlencode(str_replace([' ', '-'], ['_', ''], $search)), $page);

        /** @var ResponseInterface $res */
        $res = $client->get($url, [
            'headers' => [
                'referer' => $url,
            ],
        ]);

        $crawler = new Crawler((string) $res->getBody(), $url);
        try {
            $postList = $crawler->filter('.image');
        } catch (\InvalidArgumentException $err) {
            throw new \Exception('No posts found');
        }

        $posts = [];
        preg_match('{http://www\.javjunkies\.com/main/JO\.php\?kEy=\d+}', $crawler->html(), $match);
        file_put_contents('temp.html', $crawler->html());
        $root = $match[0];
        foreach ($postList as $post) {
            $addPost        = $this->getPostInfo(new Crawler($post), $root);
            $infoResponse    = $client->get($addPost['raw']);
            $addPost['raw'] = sprintf('<img src="data:image/png;base64,%s"/>', base64_encode((string)$infoResponse->getBody()));

            if (parse_url($addPost['image'], PHP_URL_HOST) === 'javjunkies.com') {
                /** @var ResponseInterface $imageResponse */
                $imageResponse = $client->get($addPost['image'], [
                    'allow_redirects' => false,
                    'headers' => [
                        'referer' => $url,
                    ],
                ]);
                $addPost['image'] = $imageResponse->getHeader('location');
            }
            $posts[] = $addPost;
        }

        $older = (bool) $crawler->filter('a:contains(Previous)')->count();
        $newer = (bool) $crawler->filter('a:contains(Next)')->count();

        return new JsonResponse(['posts' => $posts, 'older' => $older, 'newer' => $newer, 'url' => $url]);
    }

    /**
     * @Method("GET")
     * @Route("/jav-download")
     */
    public function downloadAction(Request $request)
    {
        $client = $this->get('client');

        $url     = $request->query->get('download');
        $referer = $request->query->get('referer');
        $name    = $request->query->get('name');

        $req = $client->createRequest('GET', $url, [
            'headers' => [
                'referrer' => $referer,
            ],
        ]);

        return new StreamedResponse(function () use ($client, $req) {
            /** @var ResponseInterface $response */
            $response = $client->send($req);
            print $response->getBody();
        }, Response::HTTP_OK, [
            'content-type'        => 'application/x-bittorrent',
            'content-disposition' => sprintf('attachment; filename="%s"', ($name)),
        ]);
    }

    private function isCategory($term)
    {
        $categories = [
            "big-breast",
            "school-girl",
            "mature",
            "bondage",
            "creampie",
            "swimsuit",
            "rape",
            "wife",
            "torture",
            "ol",
            "group",
            "cosplay",
            "squirt",
            "glasses",
            "nurse",
            "gravure",
            "iv",
            "fellatio",
            "teacher",
            "w.",
            "public",
            "bukkake",
            "grope",
            "amateur",
            "prostitute",
            "lesbian",
            "maid",
            "uncensored",
            "black",
            "anal",
            "shaved",
            "incest",
            "onsen",
            "tights",
            "soapland",
            "jk",
            "onanie",
            "weird",
            "ass",
            "butt",
            "hip",
            "double-penetration",
            "white",
            "see-thru",
            "drugged",
            "train",
            "bus",
            "magnum",
            "she-male",
            "legs",
            "doctor",
            "hentai",
            "cleaning-lady",
            "race-queen",
            "virgin",
            "american",
            "fist",
            "tentacle",
            "cherry-boy",
            "ca"
        ];

        return in_array(strtolower($term), $categories);
    }

    private function getPostInfo(Crawler $crawler, $root)
    {
        $post = [];

        preg_match("{'(.*)'}", $crawler->filter('.iH')->attr('style'), $match);
        if (preg_match('{^https?://}', $match[1])) {
            $post['image'] = $match[1];
        } else {
            $post['image'] = 'http://javjunkies.com'.$match[1];
        }

        preg_match('{"(.*)"}', $crawler->filter('.iH script')->html(), $match);
        $rawInfo = $this->unescape($match[1]);

        preg_match('{i=([^"]+)"}', $rawInfo, $match);

        $post['raw'] = 'http://javjunkies.com/main/ij/i/'.$match[1].'.png';

        try {
            preg_match("{'(.*)'}", $crawler->filter('a:first-child')->attr('onclick'), $match);
        } catch (\InvalidArgumentException $e) {
            preg_match("{'(.*)'}", $crawler->filter('blockquote:first-child>a')->attr('onclick'), $match);
        }
        $post['download'] = $root.$match[1];

        return $post;
    }

    private function unescape($source)
    {

        $decodedStr = "";
        $pos        = 0;
        $len        = strlen($source);
        while ($pos < $len) {
            $charAt = substr($source, $pos, 1);
            if ($charAt == '%') {
                $pos++;
                $charAt = substr($source, $pos, 1);
                if ($charAt == 'u') {
                    // we got a unicode character
                    $pos++;
                    $unicodeHexVal = substr($source, $pos, 4);
                    $unicode       = hexdec($unicodeHexVal);
                    $entity        = "&#".$unicode.';';
                    $decodedStr .= utf8_encode($entity);
                    $pos += 4;
                } else {
                    // we have an escaped ascii character
                    $hexVal  = substr($source, $pos, 2);
                    $unicode = hexdec($hexVal);
                    $entity  = "&#".$unicode.';';
                    $decodedStr .= utf8_encode($entity);
                    $pos += 2;
                }
            } else {
                $decodedStr .= $charAt;
                $pos++;
            }
        }

        return mb_convert_encoding($decodedStr, 'UTF-8', 'HTML-ENTITIES');
    }
}
