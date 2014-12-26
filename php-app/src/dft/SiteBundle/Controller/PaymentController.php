<?php

namespace dft\SiteBundle\Controller;

class PaymentController extends BaseController
{
    public function indexAction()
    {
        // TODO: Implement.
        return $this->render('dftSiteBundle:Payment:payment.html.twig', array(
                "shopping_cart_item_count" => $this->getItemCount()
            )
        );
    }
}