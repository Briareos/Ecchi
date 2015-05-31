<?php

namespace Ecchi\MainBundle\Controller;

use GuzzleHttp\Subscriber\Cookie;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class DefaultController extends Controller
{
    /**
     * @Route("")
     * @Template()
     */
    public function indexAction()
    {
        return [];
    }
}
