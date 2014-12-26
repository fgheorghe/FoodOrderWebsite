<?php

namespace dft\SiteBundle\Controller;

class LoginController extends BaseController
{
    public function indexAction()
    {
        // TODO: Implement.
        return $this->render('dftSiteBundle:Login:login.html.twig', array(
                "shopping_cart_item_count" => $this->getItemCount()
            )
        );
    }
}
