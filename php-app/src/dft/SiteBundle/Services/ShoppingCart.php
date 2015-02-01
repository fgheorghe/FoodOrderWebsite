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
     * This function puts items in a 'limbo' state, to ensure cart items do not change
     * when the user has started a payment process. This should be called once the user
     * views the shopping cart, for each order.
     *
     * @param $orderId Integer Which order id the items are stored for.
     */
    public function storeItemsInLimbo($orderId) {
        // Get the session service.
        $sessionService = $this->getContainer()->get('session');

        // Get existing items.
        $limboCartItemsArray = $this->getItemsInLimbo();

        // Update or add items for this order.
        $limboCartItemsArray[$orderId] = $this->getItems();

        // Put back in session.
        $sessionService->set('limbo_cart_item_ids', $limboCartItemsArray);
    }

    /**
     * Fetches items in limbo for all orders.
     * @return array
     */
    public function getItemsInLimbo() {
        // Get the session service.
        $sessionService = $this->getContainer()->get('session');

        // Get the shopping cart items array. If none set, create one.
        $limboCartItemsArray = $sessionService->get('limbo_cart_item_ids');
        if (!is_array($limboCartItemsArray)) {
            $limboCartItemsArray = array();
        }
        return $limboCartItemsArray;
    }

    /**
     * Removed order items from limbo.
     * @param $orderId
     */
    public function removeItemsFromLimbo($orderId) {
        // Get the session service.
        $sessionService = $this->getContainer()->get('session');

        $limboItems = $this->getItemsInLimbo();
        if (array_key_exists($orderId, $limboItems)) {
            unset($limboItems[$orderId]);
        }

        // Put back in session.
        $sessionService->set('limbo_cart_item_ids', $limboItems);
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

        // Remove one from the items array.
        if (array_key_exists($menuItemId, $cartItemsArray)) {
            $cartItemsArray[$menuItemId]--;
            // If cart is now empty, remove item from the array.
            if ($cartItemsArray[$menuItemId] <= 0) {
                unset($cartItemsArray[$menuItemId]);
            }
        }

        // Put back in session.
        $sessionService->set('cart_item_ids', $cartItemsArray);
    }

    /**
     * Gets all items added to basket.
     * @param $limbo Boolean Fetch items from limbo.
     * @return Array
     */
    public function getItems($limbo = false) {
        // Get the session service.
        $sessionService = $this->getContainer()->get('session');

        if ($limbo == true) {
            $cartItemsArray = $this->getItemsInLimbo();
        } else {
            // Get the shopping cart items array. If none set, create one.
            $cartItemsArray = $sessionService->get('cart_item_ids');
            if (!is_array($cartItemsArray)) {
                $cartItemsArray = array();
            }
        }
        return $cartItemsArray;
    }

    /**
     * Gets the total of cart items.
     * @param $shoppingCartMenuItems Array returned by mapCartItemsToMenuItems
     * @return Double
     */
    public function getTotal($shoppingCartMenuItems) {
        $total = 0;
        foreach ($shoppingCartMenuItems as $item) {
            $total += $item->price;
        }
        return $total;
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

    /**
     * Empties the shopping cart - typically after an order has been placed.
     */
    public function emptyCart() {
        $this->getContainer()->get('session')->set('cart_item_ids', array());
    }

    /**
     * Stores the selected delivery type.
     * @param $deliveryType
     */
    public function setDeliveryType($deliveryType) {
        $this->getContainer()->get('session')->set('delivery_type', $deliveryType);
    }

    /**
     * Fetches the delivery type. Defaults to restaurant default if not set.
     * @return Integer
     */
    public function getDeliveryType() {
        $deliveryType = $this->getContainer()->get('session')->get('delivery_type');
        return !is_null($deliveryType) ? $deliveryType : ApiClient::ORDER_DELIVERY_TYPE_DELIVERY;
    }

    /**
     * Method used for storing delivery details for each ongoing order.
     * @param $orderId
     * @param $deliveryType
     * @param $postCode
     * @param $address
     * @param $notes
     */
    public function setDeliveryOptionsForOrder($orderId, $deliveryType, $postCode, $address, $notes) {
        // Get the session service.
        $sessionService = $this->getContainer()->get('session');

        $deliveryOptions = $this->getDeliveryOptions();
        $deliveryOptions[$orderId] = array(
            "delivery_type" => $deliveryType,
            "post_code" => $postCode,
            "address" => $address,
            "notes" => $notes
        );

        $sessionService->set('delivery_options', $deliveryOptions);
    }

    /**
     * Method used for fetching all active orders options.
     * @return array
     */
    public function getDeliveryOptions() {
        // Get the session service.
        $sessionService = $this->getContainer()->get('session');

        // Get the delivery options array. If none set, create one.
        $deliveryOptions = $sessionService->get('delivery_options');
        if (!is_array($deliveryOptions)) {
            $deliveryOptions = array();
        }
        return $deliveryOptions;
    }

    /**
     * Adds an order id (reference) to processed list.
     * @param $orderId
     */
    public function setOrderAsProcessed($orderId) {
        // Get the session service.
        $sessionService = $this->getContainer()->get('session');
        // Get existing.
        $processedOrderIds = $this->getProcessedOrderIds();
        // Add order id.
        $processedOrderIds[] = $orderId;
        // Put back in session.
        $sessionService->set('processed_order_ids', $processedOrderIds);
    }

    /**
     * Returns a list of processed order ids, typically used to avoid processing the
     * same order type.
     * @return array
     */
    public function getProcessedOrderIds() {
        // Get the session service.
        $sessionService = $this->getContainer()->get('session');

        // Get session items.
        $processedOrderIds = $sessionService->get('processed_order_ids');
        if (!is_array($processedOrderIds)) {
            $processedOrderIds = array();
        }

        return $processedOrderIds;
    }
}