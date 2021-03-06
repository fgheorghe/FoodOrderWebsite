<?php

namespace dft\SiteBundle\Services;

use dft\SiteBundle\Traits\ContainerAware;
use dft\SiteBundle\Traits\Logger;

class Login {
    use ContainerAware;
    use Logger;

    /**
     * Check if a username (email address) and password are valid, using the API.
     * If success, it stores the customer id in the session (unless skipSession is set to true).
     * @param $username
     * @param $password
     * @param $skipSession
     * @return Boolean
     */
    public function login($username, $password, $skipSession = false) {
        $apiClientService = $this->container->get('dft_site.api_client');
        $response = $apiClientService->verifyPassword($username, $password);

        if ($response->success === true && $skipSession !== true) {
            $this->storeCustomerDataInSession($response->customer);
        }

        return $response->success;
    }

    // Method used for storing the customer data in the session.
    public function storeCustomerDataInSession($customer) {
        $this
            ->getContainer()
            ->get('session')
            ->set("customer", $customer);
    }

    /**
     * Method used for checking if a customer is already authenticated.
     */
    public function isAuthenticated() {
        return $this->getAuthenticatedCustomerData() ? true : false;
    }

    /**
     * Method used for fetching the currently authenticated customer data.
     */
    public function getAuthenticatedCustomerData() {
        return $this
            ->getContainer()
            ->get('session')
            ->get("customer", false);
    }

    /**
     * Method used for logging the user out.
     */
    public function doLogout() {
        $this->storeCustomerDataInSession(false);
    }
}