<?php

namespace dft\SiteBundle\Controller;

use dft\SiteBundle\Services\ApiClient;
use dft\SiteBundle\Services\BarclaysPayment;

class PaymentController extends BaseController
{
    public function indexAction()
    {
        // Check if the user is logged in. If not, then redirect to login page.
        if (!$this->getLoginService()->isAuthenticated()) {
            // Redirect to login page.
            return $this->redirect(
                $this->generateUrl('dft_site_login') . "?return=payment"
            );
        } else {
            // _GET values.
            $query = $this->container->get("request")->query;

            // Check if payment on delivery.
            $pod = $query->get('pod') == 'true' ? true : false;

            // If no payment id (reference) is set, redirect the user to menu page.
            $orderId = $query->get('orderID');
            if (!$orderId) {
                // Redirect to menu page.
                return $this->redirect(
                    $this->generateUrl('dft_site_menu')
                );
            } else {
                // Check if the order exists in 'limbo'.
                $limboOrders = $this->getShoppingCartService()->getItemsInLimbo();
                $processedOrderIds = $this->getShoppingCartService()->getProcessedOrderIds();

                // If it does not exist, redirect the user to menu page.
                if (!in_array($orderId, $processedOrderIds) && !array_key_exists($orderId, $limboOrders)) {
                    // Redirect to menu page.
                    return $this->redirect(
                        $this->generateUrl('dft_site_menu')
                    );
                } else {
                    // Check the payment status. If not a verified customer, and 'Requested' or 'Authorised' then notify the user,
                    // and do not place an order.
                    $paymentStatus = $query->get('STATUS', 0);
                    if (($pod && !($this->getLoginService()->getAuthenticatedCustomerData()->verified
                            || $this->isAcceptingPaymentOnDeliveryOrCollectionForUnverifiedUsers())) &&
                        !in_array($paymentStatus, array(BarclaysPayment::PAYMENT_PAYMENT_REQUESTED, BarclaysPayment::PAYMENT_AUTHORISED))) {
                        $errorMessage = "Can not process payment. Please try again later.";
                    } else {
                        // Verify if the order has already been processed.
                        if (!in_array($orderId, $processedOrderIds)) {
                            // Get items and construct order.
                            $cartItems = $limboOrders[$orderId];

                            // Get order delivery details.
                            $deliveryOptions = $this->getShoppingCartService()->getDeliveryOptions();

                            // And options for the current order id.
                            $orderDeliveryOptions = $deliveryOptions[$orderId];

                            // Prepare customer data.
                            $customer = $this->getLoginService()->getAuthenticatedCustomerData();

                            // Prepare discount ids.
                            $discountsToApply = $this->getShoppingCartService()->getDiscountsInLimbo($orderId);
                            $discountIds = array();
                            $discount = $discountsToApply['option_discount'];
                            if (!is_null($discount)) {
                                $discountIds[] = $discount->id;
                            }
                            foreach ($discountsToApply['generic'] as $discount) {
                                if ($discount->discount_type == 0) {
                                    $discountIds[] = $discount->id;
                                }
                            }
                            $items = array();
                            if (count($cartItems)) {
                                foreach ($cartItems as $id => $count) {
                                    $items[] = array(
                                        "id" => $id,
                                        "size_id" => 1, // TODO: Add size ids.
                                        "count" => $count
                                    );
                                }

                                // Place the order.
                                $this->getApiClientService()->createOrder(
                                    $customer->id,
                                    json_encode($items, JSON_NUMERIC_CHECK),
                                    $orderDeliveryOptions["address"],
                                    $orderDeliveryOptions["post_code"],
                                    $orderDeliveryOptions["notes"],
                                    ApiClient::ORDER_TYPE_ONLINE,
                                    $pod ? ApiClient::ORDER_PAYMENT_STATUS_NOT_PAID : ApiClient::ORDER_PAYMENT_STATUS_PAID,
                                    $customer->verified ? ApiClient::ORDER_CUSTOMER_TYPE_VERIFIED : ApiClient::ORDER_CUSTOMER_TYPE_NOT_VERIFIED,
                                    $customer->phone_number,
                                    $customer->name,
                                    $orderDeliveryOptions["delivery_type"],
                                    0, // TODO: Add discounts.
                                    $orderId,
                                    json_encode($discountIds, JSON_NUMERIC_CHECK)
                                );

                                // Empty the cart.
                                $this->getShoppingCartService()->emptyCart();
                                // Remove discounts from limbo.
                                $this->getShoppingCartService()->removeDiscountsInLimbo($orderId);
                                // Remove order from limbo.
                                $this->getShoppingCartService()->removeItemsFromLimbo($orderId);
                                $this->getShoppingCartService()->setOrderAsProcessed($orderId);
                                // Set message.
                                $errorMessage = "Your order has been placed. Please check your inbox for delivery and time confirmation.";
                            } else {
                                $errorMessage = "Your cart is empty. Please add items.";
                            }
                        } else {
                            $errorMessage = "Your order has been placed. Please check your inbox for delivery and time confirmation.";
                        }
                    }

                    return $this->render('dftSiteBundle:Payment:payment.html.twig', array(
                            "error_message" => $errorMessage,
                            "order_id" => $orderId
                        )
                    );
                }
            }
        }
    }
}