<?php

namespace dft\SiteBundle\Controller;

class ForgotPasswordController extends BaseController
{
    public function indexAction()
    {
        // TODO: Implement.
        return $this->render('dftSiteBundle:ForgotPassword:forgot-password.html.twig', array(
                "shopping_cart_item_count" => $this->getItemCount()
            )
        );
    }
}
