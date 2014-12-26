<?php

namespace dft\SiteBundle\Controller;

class ContactController extends BaseController
{
    public function indexAction()
    {
        // TODO: Implement.
        return $this->render('dftSiteBundle:Contact:contact.html.twig', array(
                "shopping_cart_item_count" => count($this->getShoppingCartService()->getItems())
            )
        );
    }
}
