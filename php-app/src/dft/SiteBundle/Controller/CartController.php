<?php

namespace dft\SiteBundle\Controller;

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

        return $this->render('dftSiteBundle:Cart:cart.html.twig',
            array(
                // Shopping cart items.
                "shopping_cart_items" => $shoppingCartService->mapCartItemsToMenuItems(
                        $shoppingCartService->getItems(),
                        // TODO: Optimise this bit.
                        $this->getApiClientService()->getCategoryMenuItems(null)
                )
            )
        );
    }
}
