<?php

namespace dft\SiteBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class ContactController extends Controller
{
    public function indexAction()
    {
        // TODO: Implement.
        return $this->render('dftSiteBundle:Contact:contact.html.twig');
    }
}
