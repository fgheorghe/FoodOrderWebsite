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
use dft\SiteBundle\Traits\Logger;
use dft\SiteBundle\Traits\Curl;

class ApiClient {
    use ContainerAware;
    use Database;
    use Logger;
    use Curl;

    // Store here temporary API tokens. These will be selected based on domain name.
    const TOKEN_1 = "c51f7c3426684daad001927e6508ec90";
    const TOKEN_2 = "cd5d1ea27cb6ed0b60405aa2a1f2bdbb";

    // Some URL parts to append for different services, excluding leading /, including trailing /,
    // To be appended to foapi_services_root_url configuration parameter.
    const SERVICE_MENU_ITEM_CATEGORIES_URL = "menu-item-categories/";
    const SERVICE_CATEGORY_MENU_ITEMS_URL = "menu-items/";
    const SERVICE_FRONT_END_SETTINGS_URL = "front-end-settings/";
    const SERVICE_RESTAURANT_SETTINGS_URL = "restaurant-settings/";

    /**
     * Method used for fetching menu item categories.
     * @return mixed
     */
    public function getMenuItemCategories() {
        return $this->get(
            $this->getContainer()->getParameter('foapi_services_root_url'),
            self::SERVICE_MENU_ITEM_CATEGORIES_URL,
            array(
                "token_1" => self::TOKEN_1,
                "token_2" => self::TOKEN_2
            )
        );
    }

    /**
     * Method used for fetching menu item category items.
     * @param $categoryId
     * @return mixed
     */
    public function getCategoryMenuItems($categoryId) {
        return $this->get(
            $this->getContainer()->getParameter('foapi_services_root_url'),
            self::SERVICE_CATEGORY_MENU_ITEMS_URL,
            array(
                "token_1" => self::TOKEN_1,
                "token_2" => self::TOKEN_2,
                "category_id" => $categoryId
            )
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
                "token_1" => self::TOKEN_1,
                "token_2" => self::TOKEN_2
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
                "token_1" => self::TOKEN_1,
                "token_2" => self::TOKEN_2
            )
        );
    }
}