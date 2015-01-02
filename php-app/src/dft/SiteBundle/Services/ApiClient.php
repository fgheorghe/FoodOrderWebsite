<?php
/**
 * Created by PhpStorm.
 * User: fgheorghe
 * Date: 06/12/14
 * Time: 21:45
 */

namespace dft\SiteBundle\Services;

use dft\SiteBundle\Traits\ContainerAware;
use dft\SiteBundle\Traits\Database;
use dft\SiteBundle\Traits\InternalServiceApiClient;
use dft\SiteBundle\Traits\Logger;
use dft\SiteBundle\Traits\Curl;

class ApiClient {
    use ContainerAware;
    use Logger;
    use Curl;
    use InternalServiceApiClient;

    // Store here temporary API tokens. These will be selected based on domain name.
    public static $TOKEN_1;
    public static $TOKEN_2;

    // Order creation constants.
    const ORDER_TYPE_OFFLINE = 0x00;
    const ORDER_TYPE_ONLINE = 0x01;
    const ORDER_TYPE_PHONE = 0x02;
    const ORDER_TYPE_TABLE = 0x03;
    const ORDER_PAYMENT_STATUS_PAID = 0x06;
    const ORDER_PAYMENT_STATUS_NOT_PAID = 0x07;
    const ORDER_CUSTOMER_TYPE_VERIFIED = 0x04;
    const ORDER_CUSTOMER_TYPE_NOT_VERIFIED = 0x05;
    const ORDER_DELIVERY_TYPE_DELIVERY = 0x01;
    const ORDER_DELIVERY_TYPE_COLLECTION = 0x02;

    // Image constants.
    const IMAGE_TYPE_LOGO = 0x01;
    const IMAGE_TYPE_FACT_1 = 0x02;
    const IMAGE_TYPE_FACT_2 = 0x03;
    const IMAGE_TYPE_FACT_3 = 0x04;

    // Some URL parts to append for different services, excluding leading /, including trailing /,
    // To be appended to foapi_services_root_url configuration parameter.
    const SERVICE_MENU_ITEM_CATEGORIES_URL = "menu-item-categories/";
    const SERVICE_CATEGORY_MENU_ITEMS_URL = "menu-items/";
    const SERVICE_FRONT_END_SETTINGS_URL = "front-end-settings/";
    const SERVICE_RESTAURANT_SETTINGS_URL = "restaurant-settings/";
    const SERVICE_VERIFY_CUSTOMER_PASSWORD_URL = "customer/verify-password/";
    const SERVICE_UPDATE_CUSTOMER_DATA_URL = "customer/"; // The customer id is appended here.
    const SERVICE_CREATE_ORDER_URL = "order/";
    const SERVICE_GET_ORDERS_URL = "orders/";
    const SERVICE_IMAGES_URL = "images/";

    // Fetch api tokens upon instantiation.
    // If this code is made public, remove this.
    public function __construct($container) {
        $this->setContainer($container);

        if (is_null(self::$TOKEN_1) || is_null(self::$TOKEN_2)) {
            $tokens = $this->getApiTokens();
            $this::$TOKEN_1 = $tokens->token_1;
            $this::$TOKEN_2 = $tokens->token_2;
        }
    }

    /**
     * Method used for fetching menu item categories.
     * @return mixed
     */
    public function getMenuItemCategories() {
        return $this->get(
            $this->getContainer()->getParameter('foapi_services_root_url'),
            self::SERVICE_MENU_ITEM_CATEGORIES_URL,
            array(
                "token_1" => self::$TOKEN_1,
                "token_2" => self::$TOKEN_2,
                "non_empty" => 1
            )
        );
    }

    /**
     * Method used for fetching images.
     * @return mixed
     */
    public function getImages() {
        return $this->get(
            $this->getContainer()->getParameter('foapi_services_root_url'),
            self::SERVICE_IMAGES_URL,
            array(
                "token_1" => self::$TOKEN_1,
                "token_2" => self::$TOKEN_2
            )
        );
    }

    /**
     * Method used for fetching menu item category items.
     * @param $categoryIdOrName Mixed. If empty, it will select all menu items for this account.
     * If this is a number, then it will filter by category id, otherwise category url.
     * @return mixed
     */
    public function getCategoryMenuItems($categoryIdOrName = null) {
        // Prepare service request parameters.
        $requestParameters = array(
            "token_1" => self::$TOKEN_1,
            "token_2" => self::$TOKEN_2
        );

        // Add a category id.
        if (!is_null($categoryIdOrName)) {
            // If a number, filter by category id.
            if (is_numeric($categoryIdOrName)) {
                $requestParameters["category_id"] = $categoryIdOrName;
            } else {
                // otherwise by url.
                $requestParameters["category_url"] = $categoryIdOrName;
            }
        }

        return $this->get(
            $this->getContainer()->getParameter('foapi_services_root_url'),
            self::SERVICE_CATEGORY_MENU_ITEMS_URL,
            $requestParameters
        );
    }

    /**
     * Get front end settings.
     * @return mixed
     */
    public function getFrontEndSettings() {
        return $this->get(
            $this->getContainer()->getParameter('foapi_services_root_url'),
            self::SERVICE_FRONT_END_SETTINGS_URL,
            array(
                "token_1" => self::$TOKEN_1,
                "token_2" => self::$TOKEN_2
            )
        );
    }

    /**
     * Get restaurant settings.
     * @return mixed
     */
    public function getRestaurantSettings() {
        return $this->get(
            $this->getContainer()->getParameter('foapi_services_root_url'),
            self::SERVICE_RESTAURANT_SETTINGS_URL,
            array(
                "token_1" => self::$TOKEN_1,
                "token_2" => self::$TOKEN_2
            )
        );
    }

    /**
     * Verifies a password for a given email address.
     * @param $email
     * @param $password
     * @return Mixed
     */
    public function verifyPassword($email, $password) {
        return $this->post(
            $this->getContainer()->getParameter('foapi_services_root_url'),
            self::SERVICE_VERIFY_CUSTOMER_PASSWORD_URL,
            array(
                "token_1" => self::$TOKEN_1,
                "token_2" => self::$TOKEN_2
            ),
            array(
                "username" => $email,
                "password" => $password
            )
        );
    }

    /**
     * Updates customer data.
     * @param $customerId
     * @param $name
     * @param $email
     * @param $postCode
     * @param $address
     * @param $phoneNumber
     * @param $password
     * @return Mixed
     */
    public function updateCustomerProfile($customerId, $name, $email, $postCode, $address, $phoneNumber, $password) {
        return $this->post(
            $this->getContainer()->getParameter('foapi_services_root_url'),
            self::SERVICE_UPDATE_CUSTOMER_DATA_URL . $customerId,
            array(
                "token_1" => self::$TOKEN_1,
                "token_2" => self::$TOKEN_2
            ),
            array(
                "name" => $name,
                "post_code" => $postCode,
                "email" => $email,
                "address" => $address,
                "phone_number" => $phoneNumber,
                "password" => $password,
                "verified" => 0 // Reset back to not verified.
            )
        );
    }

    /**
     * Creates an order.
     * @param $customerId
     * @param $itemsJsonString String I.e.: items:[{"id":35,"size_id":1,"count":1},{"id":59,"size_id":2,"count":1},{"id":48,"size_id":4,"count":1}]
     * @param $deliveryAddress
     * @param $postCode
     * @param $notes
     * @param $orderType
     * @param $paymentStatus
     * @param $customerType
     * @param $customerPhoneNumber
     * @param $customerName
     * @param $deliveryType
     * @param $discount
     * @return Mixed
     */
    public function createOrder($customerId, $itemsJsonString, $deliveryAddress, $postCode, $notes, $orderType, $paymentStatus, $customerType, $customerPhoneNumber, $customerName, $deliveryType, $discount) {
        return $this->post(
            $this->getContainer()->getParameter('foapi_services_root_url'),
            self::SERVICE_CREATE_ORDER_URL,
            array(
                "token_1" => self::$TOKEN_1,
                "token_2" => self::$TOKEN_2
            ),
            array(
                "customer_id" => $customerId,
                "items" => $itemsJsonString,
                "delivery_address" => $deliveryAddress,
                "post_code" => $postCode,
                "notes" => $notes,
                "order_type" => $orderType,
                "payment_status" => $paymentStatus,
                "customer_type" => $customerType,
                "customer_phone_number" => $customerPhoneNumber,
                "customer_name" => $customerName,
                "delivery_type" => $deliveryType,
                "discount" => $discount
            )
        );
    }

    /**
     * Method used for fetching customer orders.
     * @param $customerId
     * @return Mixed
     */
    public function getCustomerOrders($customerId) {
        return $this->get(
            $this->getContainer()->getParameter('foapi_services_root_url'),
            self::SERVICE_GET_ORDERS_URL,
            array(
                "token_1" => self::$TOKEN_1,
                "token_2" => self::$TOKEN_2,
                "customer_id" => $customerId
            )
        );
    }

    /**
     * Method used for creating a customer.
     * @param $name
     * @param $email
     * @param $postCode
     * @param $address
     * @param $phoneNumber
     * @param $password
     * @return Mixed
     */
     public function createCustomer($name, $email, $postCode, $address, $phoneNumber, $password) {
         return $this->post(
             $this->getContainer()->getParameter('foapi_services_root_url'),
             self::SERVICE_UPDATE_CUSTOMER_DATA_URL,
             array(
                 "token_1" => self::$TOKEN_1,
                 "token_2" => self::$TOKEN_2
             ),
             array(
                 "name" => $name,
                 "post_code" => $postCode,
                 "email" => $email,
                 "address" => $address,
                 "phone_number" => $phoneNumber,
                 "password" => $password,
                 "verified" => 0 // Defaults to not verified.
             )
         );
     }
}