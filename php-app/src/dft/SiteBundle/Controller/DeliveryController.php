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

        $postCode = $request->get('post_code', $customerData->post_code);
        $address = $request->get('address', $customerData->address);
        $notes = $request->get('notes');
        // TODO: Configurable default.
        $deliveryType = $request->get('delivery_type', ApiClient::ORDER_DELIVERY_TYPE_DELIVERY);

        // Validate form data.
        $continueToPayment = false; // Assume we can't continue.
        if ($postCode && $address && $deliveryType) {
            $continueToPayment = true;
            // Let the user know she can continue with placing an order.
            $errorMessage = "Please review your delivery details or continue to payment.";
        }

        // Load the shopping cart. Items can be added through this page.
        $shoppingCartService = $this->getShoppingCartService();

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

        // TODO: Implement.
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
                "live_payment_system" => $this->getBarclaysPaymentService()->getLive()
            )
        );
    }
}
