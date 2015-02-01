<?php
/**
 * Created by PhpStorm.
 * User: fgheorghe
 * Date: 06/12/14
 * Time: 21:48
 */

namespace dft\SiteBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use dft\SiteBundle\Services\ApiClient;

class BaseController extends Controller {
    /**
     * Returns the Shopping Cart Service.
     * @return \dft\SiteBundle\Services\ShoppingCart
     */
    protected function getShoppingCartService() {
        return $this->container->get('dft_site.shopping_cart');
    }

    /**
     * Total number of items in shopping cart.
     * @return Integer
     */
    protected function getItemCount() {
        return array_sum($this->getShoppingCartService()->getItems());
    }

    /**
     * Returns the API Client Service.
     * @return \dft\SiteBundle\Services\ApiClient
     */
    protected function getApiClientService() {
        return $this->container->get('dft_site.api_client');
    }

    /**
     * Returns the Form Validators Service.
     * @return \dft\SiteBundle\Services\FormValidators
     */
    protected function getFormValidatorsService() {
        return $this->container->get('dft_site.form_validators');
    }

    /**
     * Returns the Barclays Payment Service.
     * @return \dft\SiteBundle\Services\BarclaysPayment
     */
    protected function getBarclaysPaymentService() {
        $service = $this->container->get('dft_site.barclays_payment');
        $barclaysPaymentSettings = $this->getApiClientService()->getBarclaysPaymentSettings();

        // Configure this service.
        $service->setSHA($barclaysPaymentSettings->sha1);
        $service->setPSPID($barclaysPaymentSettings->pspid);
        $service->setRestaurantDomainName($barclaysPaymentSettings->domain_name);
        $service->setLive($barclaysPaymentSettings->live_payment_system == 1 ? true : false);

        return $service;
    }

    /**
     * Returns the Login Service.
     * @return \dft\SiteBundle\Services\Login
     */
    protected function getLoginService() {
        return $this->container->get('dft_site.login');
    }

    // Helper method used for constructing the logo and fact images.
    private function constructLogoAndFactImages($imagesArray) {
        // Each type may have multiple images, which are then randomly selected
        // for display.
        $facts1 = array();
        $facts2 = array();
        $facts3 = array();
        $logos = array();
        $banners = array();
        $images = array(
            "logo" => null,
            "fact_1" => null,
            "fact_2" => null,
            "fact_3" => null,
            "banner" => null
        );

        foreach ($imagesArray as $image) {
            switch ($image->type) {
                case ApiClient::IMAGE_TYPE_LOGO:
                    $logos[] = $image;
                    break;
                case ApiClient::IMAGE_TYPE_FACT_1:
                    $facts1[] = $image;
                    break;
                case ApiClient::IMAGE_TYPE_FACT_2:
                    $facts2[] = $image;
                    break;
                case ApiClient::IMAGE_TYPE_FACT_3:
                    $facts3[] = $image;
                    break;
                case ApiClient::IMAGE_TYPE_BANNER:
                    $banners[] = $image;
                    break;
            }
        }

        // Now select one of them for each type.
        $images["logo"] = count($logos) ? $logos[rand(0, count($logos) - 1)] : null;
        $images["banner"] = count($banners) ? $banners[rand(0, count($banners) - 1)] : null;
        $images["fact_1"] = count($facts1) ? $facts1[rand(0, count($facts1) - 1)] : null;
        $images["fact_2"] = count($facts2) ? $facts2[rand(0, count($facts2) - 1)] : null;
        $images["fact_3"] = count($facts3) ? $facts3[rand(0, count($facts3) - 1)] : null;

        return $images;
    }

    // Convenience method used for checking if the team is on lunch break.
    protected function isOnLunch() {
        $restaurantSettings = $this->getApiClientService()->getRestaurantSettings();

        return $restaurantSettings->lunch_break == 0 ? false : (time() >= strtotime($restaurantSettings->lunch_break_start) && time() <= strtotime($restaurantSettings->lunch_break_end));
    }

    // Convenience method used for checking if a restaurant is closed or not.
    protected function isRestaurantClosed() {
        $restaurantSettings = $this->getApiClientService()->getRestaurantSettings();

        return $restaurantSettings->open_all_day == 1 ? false : (time() < strtotime($restaurantSettings->opening_time) || time() > strtotime($restaurantSettings->closing_time));
    }

    /**
     * Renders a view.
     *
     * @param string   $view       The view name
     * @param array    $parameters An array of parameters to pass to the view
     * @param Response $response   A response instance
     *
     * @return Response A Response instance
     */
    public function render($view, array $parameters = array(), Response $response = null)
    {
        $restaurantSettings = $this->getApiClientService()->getRestaurantSettings();

        // Get the front end settings and cart items count, and append to parameters array.
        $parameters = array_merge($parameters, array(
               "front_end_settings" => $this->getApiClientService()->getFrontEndSettings(),
                // TODO: Optimise duplicate calls to this service!
               "restaurant_settings" => $restaurantSettings,
               "shopping_cart_item_count" => $this->getItemCount(),
               "customer_data" => $this->getLoginService()->getAuthenticatedCustomerData(),
               "images" => $this->constructLogoAndFactImages($this->getApiClientService()->getImages()),
               "image_store_url" => $this->container->getParameter('foapi_image_store_url'),
               "restaurant_closed" => $this->isRestaurantClosed(),
               "lunch_break" => $this->isOnLunch(),
               "discounts" => $this->getApiClientService()->getDiscounts()
            )
        );

        return parent::render($view, $parameters, $response);
    }
}