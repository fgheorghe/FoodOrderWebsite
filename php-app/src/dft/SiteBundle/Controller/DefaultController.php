<?php

namespace dft\SiteBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('dftSiteBundle:Default:index.html.twig', array('name' => $name));
    }
}
