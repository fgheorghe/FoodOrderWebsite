<?php
/**
 * Created by PhpStorm.
 * User: fgheorghe
 * Date: 06/12/14
 * Time: 21:48
 */

namespace dft\SiteBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class BaseController extends Controller {
    /**
     * Returns the API Client Service.
     * @return \dft\SiteBundle\Services\ApiClient
     */
    protected function getApiClientService() {
        // Get the customer service.
        return $this->container->get('dft_site.api_client');
    }
} 