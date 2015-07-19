<?php

namespace Ecchi\MainBundle\Controller;

use GuzzleHttp\Event\EndEvent;
use GuzzleHttp\Pool;
use Psr\Http\Message\ResponseInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ManiaxController extends Controller
{

    /**
     * @Route("/maniax-search")
     */
    public function searchAction(Request $request)
    {
        $search = $request->query->get('search');
        $search = trim($search);

        if (preg_match('{(RJ|RE)(\d{6})}', $search, $match)) {
            $id    = $match[2];
            $items = [$this->getInfoById($id)];
        } elseif (preg_match('{%RJ(\d{6})}', $search, $match)) {
            $id    = $match[1];
            $items = [$this->getInfoById($id)];
        } elseif (preg_match('{^(\d{3})[A-Z]{3}(\d{3})$}', $search, $match)) {
            $id    = $match[1].$match[2];
            $items = [$this->getInfoById($id)];
        } else {
            $items = $this->getSearchInfo($search);
        }

        return new JsonResponse(['items' => $items]);
    }

    private function getInfoById($id)
    {
        $client = $this->get('client');

        $info = $this->infoFactory($id);

        $requests = [
            $client->getAsync($info->urlJp)
                ->then(function (ResponseInterface $response) use ($info) {
                    if (!empty($info->name)) {
                        // Only set the Japanese info if we don't already have English info.
                        return;
                    }
                    $crawler     = new Crawler($response->getBody()->__toString(), $info->urlJp);
                    $data        = $this->getCrawlerInfo($crawler);
                    $info->name  = $data['name'];
                    $info->brand = $data['brand'];
                }, function () use ($info) {
                    $info->urlJp = null;
                }),
            $client->getAsync($info->urlEn)
                ->then(function (ResponseInterface $response) use ($info) {
                    $crawler     = new Crawler($response->getBody()->__toString(), $info->urlEn);
                    $data        = $this->getCrawlerInfo($crawler);
                    $info->name  = $data['name'];
                    $info->brand = $data['brand'];
                }, function () use ($info) {
                    $info->urlEn = null;
                }),
        ];

        \GuzzleHttp\Promise\settle($requests)->wait();

        return $info;
    }

    private function getImageById($id)
    {
        $number = str_replace(['RJ', 'RE'], '', $id);
        $number = ceil($number / 1000) * 1000;

        $container = 'RJ'.str_pad($number, 6, '0', STR_PAD_LEFT);

        return "http://img.dlsite.jp/modpub/images2/work/doujin/{$container}/{$id}_img_main.jpg";
    }

    private function getImagesById($id)
    {
        $number = str_replace(['RJ', 'RE'], '', $id);
        $number = ceil($number / 1000) * 1000;

        $container = 'RJ'.str_pad($number, 6, '0', STR_PAD_LEFT);

        return [
            "http://img.dlsite.jp/modpub/images2/work/doujin/{$container}/{$id}_img_smp1.jpg",
            "http://img.dlsite.jp/modpub/images2/work/doujin/{$container}/{$id}_img_smp2.jpg",
            "http://img.dlsite.jp/modpub/images2/work/doujin/{$container}/{$id}_img_smp3.jpg",
        ];
    }

    private function getCrawlerInfo(Crawler $crawler)
    {
        $brand = trim($crawler->filter('span[itemprop=brand]')->text());
        $name  = trim($crawler->filter('h1[itemprop=name]')->text());

        return [
            'brand' => $brand,
            'name'  => $name,
        ];
    }

    private function getSearchInfo($search)
    {
        $client = $this->get('client');

        $infoList = [];

        $url = sprintf('http://www.dlsite.com/maniax/fsr/=/language/jp/sex_category%%5B0%%5D/male/keyword/%s/per_page/30/from/fs.header', rawurlencode($search));

        $response = $client->get($url);
        $crawler  = new Crawler((string) $response->getBody(), $url);
        $nodes    = $crawler->filter('#search_result_list>table>tr:nth-child(odd)');

        $requests = [];

        foreach ($nodes as $node) {
            $nodeInfo = new Crawler($node, $url);
            // ID looks something like _link_RJ152171
            $id          = substr($nodeInfo->filter('div.work_thumb>a')->attr('id'), 8);
            $info        = $this->infoFactory($id);
            $info->name  = trim($nodeInfo->filter('dt.work_name>a')->text());
            $info->brand = trim($nodeInfo->filter('dd.maker_name>a')->text());
            $infoList[]  = $info;

            $requests[] = $client->getAsync($info->urlEn)
                ->then(function (ResponseInterface $response) use ($info) {
                    $data        = $this->getCrawlerInfo(new Crawler($response->getBody()->__toString(), $info->urlEn));
                    $info->name  = $data['name'];
                    $info->brand = $data['brand'];
                }, function () use ($info) {
                    $info->urlEn = null;
                });
        }

        \GuzzleHttp\Promise\settle($requests)->wait();

        return array_values($infoList);
    }

    private function infoFactory($id)
    {
        $urlJp = sprintf('http://www.dlsite.com/maniax/work/=/product_id/RJ%s.html', $id);
        $urlEn = sprintf('http://www.dlsite.com/ecchi-eng/work/=/product_id/RE%s.html', $id);

        $idJp = 'RJ'.$id;
        $info = (object) [
            'id'     => $id,
            'image'  => $this->getImageById($idJp),
            'images' => $this->getImagesById($idJp),
            'urlEn'  => $urlEn,
            'urlJp'  => $urlJp,
            'brand'  => null,
            'name'   => null,
        ];

        return $info;
    }
}
