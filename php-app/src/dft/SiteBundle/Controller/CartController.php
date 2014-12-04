<?php

namespace dft\SiteBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class CartController extends Controller
{
    public function indexAction()
    {
        // TODO: Implement.
        return $this->render('dftSiteBundle:Cart:cart.html.twig');
    }
}
