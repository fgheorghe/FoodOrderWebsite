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
        // Get the customer service.
        return $this->container->get('dft_site.api_client');
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
        // Get the front end settings, and append to parameters array.
        $parameters = array_merge($parameters, array(
               "front_end_settings" => $this->getApiClientService()->getFrontEndSettings(),
               "restaurant_settings" => $this->getApiClientService()->getRestaurantSettings()
            )
        );

        return parent::render($view, $parameters, $response);
    }
}