<?php

namespace dft\SiteBundle\Controller;

class DefaultController extends BaseController
{
    public function indexAction()
    {
        return $this->render('dftSiteBundle:Default:index.html.twig', array(
                "shopping_cart_item_count" => $this->getItemCount()
            )
        );
    }
}
