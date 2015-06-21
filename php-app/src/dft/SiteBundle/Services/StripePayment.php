<?php

namespace dft\SiteBundle\Services;

use dft\SiteBundle\Traits\ContainerAware;
use dft\SiteBundle\Traits\Logger;

class StripePayment {
    use ContainerAware;
    use Logger;

    /**
     * Process a stripe payment.
     *
     * TODO: Add logging.
     *
     * @param $token
     * @param $secretKey
     * @param $orderReference
     * @param $customerEmail
     * @param $amount
     */
    public function processPayment($token, $secretKey, $orderReference, $customerEmail, $amount) {
        \Stripe\Stripe::setApiKey($secretKey);

        $customer = \Stripe\Customer::create(array(
            'email' => $customerEmail,
            'card'  => $token
        ));

        \Stripe\Charge::create(array(
            'customer' => $customer->id,
            'amount'   => $amount,
            'currency' => 'gbp',
            'description' => "Order reference:" . $orderReference
        ));
    }
}