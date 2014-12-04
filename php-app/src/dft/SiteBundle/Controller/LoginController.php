<?php

namespace dft\SiteBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class LoginController extends Controller
{
    public function indexAction()
    {
        // TODO: Implement.
        return $this->render('dftSiteBundle:Login:login.html.twig');
    }
}
