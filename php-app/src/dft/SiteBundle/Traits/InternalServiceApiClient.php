<?php
/**
 * Created by PhpStorm.
 * User: fgheorghe
 * Date: 22/11/14
 * Time: 09:30
 */

namespace dft\SiteBundle\Traits;

/**
 * Class Curl.
 * @package dft\FoapiBundle\Traits
 */
trait InternalServiceApiClient {
    // Selects API tokens for the request domain.
    public function getApiTokens() {
        return $this->get(
            $this->getContainer()->getParameter('foapi_services_root_url'),
            // Hard-code this value.
            "tokens/" . $_SERVER["HTTP_HOST"],
            array() // No parameters.
        );
    }
}