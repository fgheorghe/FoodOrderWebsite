<?php

namespace dft\SiteBundle\Services;

use dft\SiteBundle\Traits\ContainerAware;
use dft\SiteBundle\Traits\Logger;

class ShoppingCart {
    use ContainerAware;
    use Logger;

    /**
     * Adds a menu item to the shopping cart.
     * @param $menuItemId
     */
    public function addItem($menuItemId) {
        // Get the session service.
        $sessionService = $this->getContainer()->get('session');

        // Get the shopping cart items array. If none set, create one.
        $cartItemsArray = $this->getItems();

        // Increase count, if this item is already in cart.
        if (array_key_exists($menuItemId, $cartItemsArray)) {
            $cartItemsArray[$menuItemId]++;
        } else {
            // Otherwise add 1.
            $cartItemsArray[$menuItemId] = 1;
        }

        // Put back in session.
        $sessionService->set('cart_item_ids', $cartItemsArray);
    }

    /**
     * Removes an item from the shopping cart.
     * @param $menuItemId
     */
    public function removeItem($menuItemId) {
        // Get the session service.
        $sessionService = $this->getContainer()->get('session');

        // Get the shopping cart items array. If none set, create one.
        $cartItemsArray = $this->getItems();

        // Remove from the items array.
        if (array_key_exists($menuItemId, $cartItemsArray)) {
            unset($cartItemsArray[$menuItemId]);
        }

        // Put back in session.
        $sessionService->set('cart_item_ids', $cartItemsArray);
    }

    /**
     * Gets all items added to basket.
     * @return Array
     */
    public function getItems() {
        // Get the session service.
        $sessionService = $this->getContainer()->get('session');

        // Get the shopping cart items array. If none set, create one.
        $cartItemsArray = $sessionService->get('cart_item_ids');
        if (!is_array($cartItemsArray)) {
            $cartItemsArray = array();
        }
        return $cartItemsArray;
    }

    /**
     * Maps cart items to 'database' items, adding prices and item names.
     * This is more of a convenience method.
     * @param $cartItems
     * @param $menuItems
     * @return Array
     */
    public function mapCartItemsToMenuItems($cartItems, $menuItems) {
        $response = array();

        // TODO: Optimize.
        foreach ($cartItems as $itemId => $count) {
            foreach ($menuItems->data as $menuItem) {
                if ($menuItem->id == $itemId) {
                    $response[] = (object) array(
                        "id" => $itemId,
                        "price" => $count * $menuItem->price,
                        "name" => $menuItem->item_name,
                        "count" => $count
                    );
                }
            }
        }

        return $response;
    }
}