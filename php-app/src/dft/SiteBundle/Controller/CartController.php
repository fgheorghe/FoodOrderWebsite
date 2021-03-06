<?php

namespace dft\SiteBundle\Controller;

class CartController extends BaseController
{
    public function indexAction()
    {
        // Check if the user is logged in. If not, then redirect to login page.
        if (!$this->getLoginService()->isAuthenticated()) {
            // Redirect to menu page.
            return $this->redirect(
                $this->generateUrl('dft_site_login') . "?return=cart"
            );
        }

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

        // Get all discounts.
        $discounts = $this->getApiClientService()->getDiscounts();

        // Prepare items to display.
        $shoppingCartItems = $shoppingCartService->mapCartItemsToMenuItems(
            $shoppingCartService->getItems(),
            // TODO: Optimise this bit.
            $this->getApiClientService()->getCategoryMenuItems(null),
            $discounts
        );

        // Create an order id - AKA reference.
        // TODO: Add order unique identifier!
        $orderId = time();

        // Update the 'limbo' mode.
        $shoppingCartService->storeItemsInLimbo($orderId);
        // Prepare a list of generic discounts.
        $genericDiscountsArray = array();
        $optionDiscount = null;
        // TODO: Use constants.
        foreach ($discounts as $discount) {
            if ($discount->discount_type == 0) {
                $genericDiscountsArray[] = $discount;
            }
            if ($discount->id == $shoppingCartService->getOptionDiscountId()) {
                $optionDiscount = $discount;
            }
        }

        // ...same for discounts.
        $shoppingCartService->storeDiscountsInLimbo(
            $orderId,
            $genericDiscountsArray,
            $optionDiscount
        );

        return $this->render('dftSiteBundle:Cart:cart.html.twig',
            array(
                // Shopping cart items.
                "shopping_cart_items" => $shoppingCartItems,
                "order_id" => $orderId,
                "delivery_type" => $shoppingCartService->getDeliveryType()
            )
        );
    }
}