<?php

namespace dft\SiteBundle\Controller;

use dft\SiteBundle\Services\ApiClient;

class PaymentController extends BaseController
{
    public function indexAction()
    {
        $errorMessage = "";
        // Check if the user is logged in. If not, then redirect to login page.
        if (!$this->getLoginService()->isAuthenticated()) {
            // Redirect to menu page.
            return $this->redirect(
                $this->generateUrl('dft_site_login') . "?return=payment"
            );
        } else {
            // TODO: Add payment step.
            // Prepare customer data.
            $customer = $this->getLoginService()->getAuthenticatedCustomerData();

            $cartItems = $this->getShoppingCartService()->getItems();
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
                    $customer->address,
                    $customer->post_code,
                    "", // TODO: Add notes.
                    ApiClient::ORDER_TYPE_ONLINE,
                    ApiClient::ORDER_PAYMENT_STATUS_PAID,
                    $customer->verified,
                    $customer->phone_number,
                    $customer->name,
                    ApiClient::ORDER_DELIVERY_TYPE_DELIVERY,
                    0 // TODO: Add discounts.
                );

                // Empty the cart.
                $this->getShoppingCartService()->emptyCart();

                // Set message.
                $errorMessage = "Your order has been placed. Please check your inbox for delivery confirmation.";
            } else {
                $errorMessage = "Your cart is empty. Please add items.";
            }
        }

        return $this->render('dftSiteBundle:Payment:payment.html.twig', array(
                "error_message" => $errorMessage
            )
        );
    }
}