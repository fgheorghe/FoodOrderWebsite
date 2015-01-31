<?php

namespace dft\SiteBundle\Controller;

use dft\SiteBundle\Services\ApiClient;
use dft\SiteBundle\Services\BarclaysPayment;

class DeliveryController extends BaseController
{
    public function indexAction()
    {
        // Check if the user is logged in. If not, then redirect to login page.
        if (!$this->getLoginService()->isAuthenticated()) {
            // Redirect to login page.
            return $this->redirect(
                $this->generateUrl('dft_site_login') . "?return=cart"
            );
        }

        $errorMessage = "Please review your delivery details.";

        // Get customer data, for defaults.
        // TODO: Optimise this call.
        $customerData = $this->getLoginService()->getAuthenticatedCustomerData();

        // Get delivery details form content. Otherwise default to customer account settings.
        // TODO: Load estimated delivery times.
        // _POST values.
        $request = $this->container->get("request");

        // First, check if an order id is set.
        $orderId = $request->get('order_id');
        // If not, stop the process and redirect the user to the shopping menu,
        // as she should not open this page directly.
        if (!$orderId) {
            // Redirect to menu page.
            return $this->redirect(
                $this->generateUrl('dft_site_menu')
            );
        }

        // Load the shopping cart. Items can be added through this page.
        $shoppingCartService = $this->getShoppingCartService();

        $postCode = $request->get('post_code', $customerData->post_code, "");
        $address = $request->get('address', $customerData->address, "");
        $notes = $request->get('notes', "");
        $deliveryType = $request->get('delivery_type');

        // If the user updates the delivery type, then update the cart as well.
        if (!is_null($deliveryType)) {
            $shoppingCartService->setDeliveryType($deliveryType);
        } else {
            // Otherwise, load the value already stored.
            $deliveryType = $shoppingCartService->getDeliveryType();
        }

        // Validate form data.
        $continueToPayment = false; // Assume we can't continue.
        if ($postCode && $address && $deliveryType) {
            $continueToPayment = true;
            // Let the user know she can continue with placing an order.
            $errorMessage = "Please review your delivery details or continue to payment.";
        }

        // Validate form data.
        if (!$this->getFormValidatorsService()->isValidUkPostCode($postCode)) {
            $continueToPayment = false;
            $errorMessage = "Please input a valid UK post code.";
        } elseif (!$this->getFormValidatorsService()->isValidUkAddress($address)) {
            $continueToPayment = false;
            $errorMessage = "Please input a valid UK address.";
        }

        // Update delivery options in session.
        $shoppingCartService->setDeliveryOptionsForOrder($orderId, $deliveryType, $postCode, $address, $notes);

        // Get shopping cart items, for this order.
        $allLimboItems = $shoppingCartService->getItems(true);
        $limboItems = $allLimboItems[$orderId];

        // Prepare items to include.
        $shoppingCartItems = $shoppingCartService->mapCartItemsToMenuItems(
            $limboItems,
            // TODO: Optimise this bit.
            $this->getApiClientService()->getCategoryMenuItems(null)
        );

        // Prepare payment parameters.
        $paymentParameters = array(
            "ORDERID" => $orderId,
            "AMOUNT" => $this->getShoppingCartService()->getTotal($shoppingCartItems) * 100
        );

        // Create hash.
        $shaSignature = $this->getBarclaysPaymentService()->generateSignature($paymentParameters);

        $restaurantSettings = $this->getApiClientService()->getRestaurantSettings();
        $serviceCoverage = $deliveryType == ApiClient::ORDER_DELIVERY_TYPE_COLLECTION ? true : $this->getApiClientService()->getServiceCoverage(
            $restaurantSettings->restaurant_post_code,
            $postCode,
            $restaurantSettings->delivery_range
        );
        // Check if we can deliver to the given post code. If not, overwrite the error message, and let the UI disable
        // the continue button.
        if ($serviceCoverage->success == false) {
            $errorMessage = "Unfortunately we do not deliver at this post code.";
        }

        // Final check...if restaurant just closed, or delivery type is 'delivery' and the minimum value has not been met.
        if ($this->isRestaurantClosed() ||
            $deliveryType == ApiClient::ORDER_DELIVERY_TYPE_DELIVERY
            && $paymentParameters["AMOUNT"] < $restaurantSettings->minimum_website_order_value) {
            $continueToPayment = false;
        }

        return $this->render('dftSiteBundle:Delivery:delivery-details.html.twig', array(
                "error_message" => $errorMessage,
                "post_code" => $postCode,
                "address" => $address,
                "notes" => $notes,
                "delivery_type" => $deliveryType,
                "continue_to_payment" => $continueToPayment,
                "shasign" => $shaSignature,
                // Barclays input field.
                "orderid" => $paymentParameters["ORDERID"],
                // Details input field.
                "order_id" => $paymentParameters["ORDERID"],
                "amount" => $paymentParameters["AMOUNT"],
                "currency" => BarclaysPayment::DEFAULT_CURRENCY,
                "language" => BarclaysPayment::DEFAULT_LANGUAGE,
                "pspid" => $this->getBarclaysPaymentService()->getPSPID(),
                "accepturl" => $this->getBarclaysPaymentService()->getPaymentReturnUrl(),
                "declineurl" => $this->getBarclaysPaymentService()->getPaymentReturnUrl(),
                "exceptionurl" => $this->getBarclaysPaymentService()->getPaymentReturnUrl(),
                "cancelurl" => $this->getBarclaysPaymentService()->getPaymentReturnUrl(),
                "backurl" => $this->getBarclaysPaymentService()->getPaymentReturnUrl(),
                "live_payment_system" => $this->getBarclaysPaymentService()->getLive(),
                "service_coverage" => $serviceCoverage
            )
        );
    }
}
