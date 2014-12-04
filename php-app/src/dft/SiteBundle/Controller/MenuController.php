<?php

namespace dft\SiteBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class MenuController extends Controller
{
    public function indexAction()
    {
        // TODO: Implement.
        return $this->render('dftSiteBundle:Menu:menu.html.twig');
    }
}
