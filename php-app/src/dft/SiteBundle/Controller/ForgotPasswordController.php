<?php

namespace dft\SiteBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class ForgotPasswordController extends Controller
{
    public function indexAction()
    {
        // TODO: Implement.
        return $this->render('dftSiteBundle:ForgotPassword:forgot-password.html.twig');
    }
}
