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
     * @param $discountsToApply Array
     * @return Double
     */
    public function getTotal($shoppingCartMenuItems, $discountsToApply = array()) {
        $total = 0;
        $totalToSubtract = 0;

        foreach ($shoppingCartMenuItems as $item) {
            $total += $item->price;
        }

        // Apply option discounts. TODO: Optimize.
        $alreadyApplied = array();
        foreach ($shoppingCartMenuItems as $item) {
            $discount = $discountsToApply['option_discount'];
            if (!is_null($discount)) {
                if ($discount->discount_type == 1
                    && $discount->discount_item_id == $item->id
                    && $total > $discount->value ) {
                        $total -= $item->price / $item->count;
                }
                if ($discount->discount_type == 3 && !in_array($discount->id, $alreadyApplied)) {
                    $totalToSubtract += $discount->value;
                    $alreadyApplied[] = $discount->id;
                }
            }
        }

        // Last, apply generic discounts.
        foreach ($discountsToApply['generic'] as $discount) {
            if ($discount->discount_type == 0) {
                $totalToSubtract += $discount->value;
            }
        }

        $total -= number_format(($total * $totalToSubtract/100), 2);

        return $total;
    }

    /**
     * Maps cart items to 'database' items, adding prices and item names.
     * This is more of a convenience method.
     * @param $cartItems
     * @param $menuItems
     * @param $discounts
     * @return Array
     */
    public function mapCartItemsToMenuItems($cartItems, $menuItems, $discounts = array()) {
        $response = array();

        // TODO: Optimize.
        // First, compute raw totals.
        $total = 0;
        foreach ($cartItems as $itemId => $count) {
            foreach ($menuItems->data as $menuItem) {
                if ($menuItem->id == $itemId) {
                    $total += $count * $menuItem->price;
                }
            }
        }

        // Then construct items and apply discounts.
        foreach ($cartItems as $itemId => $count) {
            foreach ($menuItems->data as $menuItem) {
                if ($menuItem->id == $itemId) {
                    $response[] = (object) array(
                        "id" => $itemId,
                        "price" => $count * $menuItem->price,
                        "name" => $menuItem->item_name,
                        "count" => $count
                    );
                    // Check if discounts are set. If so, and applies to this item add a 'negative'
                    // value of the same item to the list.
                    foreach ($discounts as $discount) {
                        // TODO: Use constants.
                        if ($this->getOptionDiscountId() == $discount->id
                            && $discount->discount_type == 1
                            && $discount->discount_item_id == $itemId
                            && $total > $discount->value ) {
                            $response[] = (object) array(
                                "id" => $itemId,
                                "price" => -1 * $menuItem->price,
                                "name" => $discount->discount_name,
                                "count" => 1
                            );
                            $total -= $menuItem->price;
                        }
                    }
                }
            }
        }

        // Now apply 'generic' discount types, as items, to make them visible.
        // TODO: Optimize!!!
        if (count($response)) {
            foreach ($discounts as $discount) {
                // TODO: Use constants.
                if ($discount->discount_type == 0) {
                    $response[] = (object) array(
                        "id" => $itemId,
                        "price" => "-" . number_format(($total * $discount->value/100), 2),
                        "name" => $discount->discount_name,
                        "count" => 1
                    );
                }

                if ($this->getOptionDiscountId() == $discount->id
                    && $discount->discount_type == 3) {
                    $response[] = (object) array(
                        "id" => $itemId,
                        "price" => "-" . number_format(($total * $discount->value/100), 2),
                        "name" => $discount->discount_name,
                        "count" => 1
                    );
                    $total -= $menuItem->price;
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

    /**
     * Method used for setting an option type discount id in the session.
     * @param $discountId
     */
    public function setOptionDiscountId($discountId) {
        $this->getContainer()->get('session')->set('option_discount_id', $discountId);
    }

    /**
     * Method used for fetching the selected option type discount id from the session.
     * @return Int
     */
    public function getOptionDiscountId() {
        return (int) $this->getContainer()->get('session')->get('option_discount_id');
    }

    /**
     * Method used for storing selected discounts in limbo. One is the default, the other is the option.
     * @param $orderId
     * @param $genericDiscountsArray
     * @param $optionDiscount
     */
    public function storeDiscountsInLimbo($orderId, $genericDiscountsArray, $optionDiscount) {
        // Get the session service.
        $sessionService = $this->getContainer()->get('session');

        // First, get the existing discounts.
        $existingGenericDiscounts = $sessionService->get('limbo_generic_discounts');
        $existingOptionDiscounts = $sessionService->get('limbo_option_discount');

        if (!is_array($existingGenericDiscounts)) {
            $existingGenericDiscounts = array();
        }

        $existingOptionDiscounts[$orderId] = $optionDiscount;
        $existingGenericDiscounts[$orderId] = $genericDiscountsArray;

        // Store generic discounts.
        $sessionService->set('limbo_generic_discounts', $existingGenericDiscounts);
        // Store optional discount.
        $sessionService->set('limbo_option_discount', $existingOptionDiscounts);
    }

    /**
     * Method used for fetching the limbo discounts, as an associative array with generic and option_discount_id id keys.
     * @param $orderId
     * @return Array
     */
    public function getDiscountsInLimbo($orderId) {
        // Get the session service.
        $sessionService = $this->getContainer()->get('session');

        $existingGenericDiscounts = $sessionService->get('limbo_generic_discounts');
        $existingOptionDiscounts = $sessionService->get('limbo_option_discount');

        $genericDiscount = array();
        $optionDiscounts = null;
        if (is_array($existingGenericDiscounts) && array_key_exists($orderId, $existingGenericDiscounts)) {
            $genericDiscount = $existingGenericDiscounts[$orderId];
        }

        if (is_array($existingOptionDiscounts) && array_key_exists($orderId, $existingOptionDiscounts)) {
            $optionDiscounts = $existingOptionDiscounts[$orderId];
        }

        return array(
            "generic" => $genericDiscount
            ,"option_discount" => $optionDiscounts
        );
    }

    /**
     * Method used for removing discounts in limbo.
     * @param $orderId
     */
    public function removeDiscountsInLimbo($orderId) {
        // Get the session service.
        $sessionService = $this->getContainer()->get('session');

        $existingGenericDiscounts = $sessionService->get('limbo_generic_discounts');
        $existingOptionDiscounts = $sessionService->get('limbo_option_discount');

        if (array_key_exists($orderId, $existingOptionDiscounts)) {
            unset($existingOptionDiscounts[$orderId]);
        }
        if (array_key_exists($orderId, $existingGenericDiscounts)) {
            unset($existingGenericDiscounts[$orderId]);
        }

        // Store generic discounts.
        $sessionService->set('limbo_generic_discounts', $existingGenericDiscounts);
        // Store optional discount.
        $sessionService->set('limbo_option_discount', $existingOptionDiscounts);
    }
}