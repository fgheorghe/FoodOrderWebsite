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
            // Redirect to menu page.
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

        // Prepare items to include.
        $shoppingCartItems = $shoppingCartService->mapCartItemsToMenuItems(
            $shoppingCartService->getItems(),
            // TODO: Optimise this bit.
            $this->getApiClientService()->getCategoryMenuItems(null)
        );

        // Prepare payment parameters.
        $paymentParameters = array(
            // TODO: Add order unique identifier!
            "ORDERID" => microtime(true),
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
                "orderid" => $paymentParameters["ORDERID"],
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
