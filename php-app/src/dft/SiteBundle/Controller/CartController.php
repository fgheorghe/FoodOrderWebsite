<?php

namespace dft\SiteBundle\Controller;

use dft\SiteBundle\Services\BarclaysPayment;

class CartController extends BaseController
{
    public function indexAction()
    {
        // _GET values.
        $query = $this->container->get("request")->query;

        // Load the shopping cart. Items can be added through this page.
        $shoppingCartService = $this->getShoppingCartService();

        // Check if we should remove an item.
        $cartRemoveItemId = $query->get('cart_remove_item', false);
        if ($cartRemoveItemId) {
            if ($cartRemoveItemId) {
                $shoppingCartService->removeItem($cartRemoveItemId);
            }

            // Once an item is removed, redirect the user back to this page, excluding
            // the cart_remove_item parameter.
            $response = $this->redirect($this->generateUrl('dft_site_cart'));

            return $response;
        }

        // Prepare items to display.
        $shoppingCartItems = $shoppingCartService->mapCartItemsToMenuItems(
            $shoppingCartService->getItems(),
            // TODO: Optimise this bit.
            $this->getApiClientService()->getCategoryMenuItems(null)
        );

        // Prepare payment parameters.
        $paymentParameters = array(
            // TODO: Add order unique identifier!
            "ORDERID" => microtime(true),
            "AMOUNT" => $this->getShoppingCartService()->getTotal($shoppingCartItems) * 100
        );

        // Create hash.
        $shaSignature = $this->getBarclaysPaymentService()->generateSignature($paymentParameters);

        return $this->render('dftSiteBundle:Cart:cart.html.twig',
            array(
                // Shopping cart items.
                "shopping_cart_items" => $shoppingCartItems,
                "shasign" => $shaSignature,
                "orderid" => $paymentParameters["ORDERID"],
                "amount" => $paymentParameters["AMOUNT"],
                "currency" => BarclaysPayment::DEFAULT_CURRENCY,
                "language" => BarclaysPayment::DEFAULT_LANGUAGE,
                "pspid" => $this->getBarclaysPaymentService()->getPSPID(),
                "accepturl" => $this->getBarclaysPaymentService()->getPaymentReturnUrl(),
                "declineurl" => $this->getBarclaysPaymentService()->getPaymentReturnUrl(),
                "exceptionurl" => $this->getBarclaysPaymentService()->getPaymentReturnUrl(),
                "cancelurl" => $this->getBarclaysPaymentService()->getPaymentReturnUrl(),
                "backurl" => $this->getBarclaysPaymentService()->getPaymentReturnUrl()
            )
        );
    }
}
