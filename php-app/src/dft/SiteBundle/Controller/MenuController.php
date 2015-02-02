<?php

namespace dft\SiteBundle\Controller;

class MenuController extends BaseController
{
    private function getRedirectResponseType($categoryId) {
        if (is_null($categoryId)) {
            $response = $this->redirect($this->generateUrl('dft_site_menu'));
        } else {
            $response = $this->redirect(
                $this->generateUrl('dft_site_menu_items', array("categoryId" => $categoryId))
            );
        }
        return $response;
    }

    public function indexAction($categoryId = null)
    {
        // _GET values.
        $query = $this->container->get("request")->query;

        // Load the shopping cart. Items can be added through this page.
        $shoppingCartService = $this->getShoppingCartService();

        // Check if we should add or remove an item, or update the delivery type.
        // TODO: Redundant with shopping cart controller logic.
        $cartAddItemId = $query->get('cart_add_item', false);
        $cartRemoveItemId = $query->get('cart_remove_item', false);
        $deliveryType = $query->get('delivery_type', false);
        $discountId = $query->get('discount_id', false);
        if ($cartAddItemId
            || $cartRemoveItemId
            || $deliveryType) {
            if ($cartAddItemId) {
                $shoppingCartService->addItem($cartAddItemId);
            }
            if ($cartRemoveItemId) {
                $shoppingCartService->removeItem($cartRemoveItemId);
            }
            if ($deliveryType) {
                $shoppingCartService->setDeliveryType($deliveryType);
            }

            // Once an item is added or removed or delivery type changed, redirect the user back to this page, excluding
            // the cart_add_item and cart_remove_item parameters.
            return $this->getRedirectResponseType($categoryId);
        }

        // Check if an 'option' type discount is selected. If so, store in session and do a similar process to above.
        if ($discountId !== false) {
            $shoppingCartService->setOptionDiscountId($discountId);
            return $this->getRedirectResponseType($categoryId);
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
                "selected_category_id" => $categoryId,
                // Get menu item categories.
                "menu_item_categories" => $this->getApiClientService()->getMenuItemCategories(),
                // Get category menu items.
                "menu_items" => $this->getApiClientService()->getCategoryMenuItems(
                        $categoryId
                    ),
                "delivery_type" => $shoppingCartService->getDeliveryType(),
                "option_discount_id" => $shoppingCartService->getOptionDiscountId()
            )
        );
    }
}
