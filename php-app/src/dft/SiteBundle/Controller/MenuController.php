<?php

namespace dft\SiteBundle\Controller;

class MenuController extends BaseController
{
    public function indexAction($categoryId = null)
    {
        // _GET values.
        $query = $this->container->get("request")->query;

        // Load the shopping cart. Items can be added through this page.
        $shoppingCartService = $this->getShoppingCartService();

        // Check if we should add or remove an item.
        // TODO: Redundant with shopping cart controller logic.
        $cartAddItemId = $query->get('cart_add_item', false);
        $cartRemoveItemId = $query->get('cart_remove_item', false);
        if ($cartAddItemId
            || $cartRemoveItemId) {
            if ($cartAddItemId) {
                $shoppingCartService->addItem($cartAddItemId);
            }
            if ($cartRemoveItemId) {
                $shoppingCartService->removeItem($cartRemoveItemId);
            }

            // Once an item is added or removed, redirect the user back to this page, excluding
            // the cart_add_item and cart_remove_item parameters.
            if (is_null($categoryId)) {
                $response = $this->redirect($this->generateUrl('dft_site_menu'));
            } else {
                $response = $this->redirect(
                    $this->generateUrl('dft_site_menu_items', array("categoryId" => $categoryId))
                );
            }
            return $response;
        }

        return $this->render(
            'dftSiteBundle:Menu:menu.html.twig',
            array(
                // Shopping cart items.
                "shopping_cart_items" => $shoppingCartService->mapCartItemsToMenuItems(
                        $shoppingCartService->getItems(),
                        // TODO: Optimise this bit.
                        $this->getApiClientService()->getCategoryMenuItems(null)
                ),
                "shopping_cart_item_count" => $this->getItemCount(),
                "selected_category_id" => $categoryId,
                // Get menu item categories.
                "menu_item_categories" => $this->getApiClientService()->getMenuItemCategories(),
                // Get category menu items.
                "menu_items" => $this->getApiClientService()->getCategoryMenuItems(
                        $categoryId
                    )
            )
        );
    }
}
