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
trait Curl {
    /**
     * Executes a post request. The GET params are required for authentication tokens.
     * @param $servicesUrl
     * @param $servicePath
     * @param $getParams
     * @param $postParams
     * @return mixed
     * @throws \Exception
     */
    public function post($servicesUrl, $servicePath, $getParams, $postParams) {
        // Configure curl.
        $curl = curl_init();
        curl_setopt_array($curl, array(
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_URL => $servicesUrl . $servicePath . "?" . http_build_query($getParams),
                // TODO: Make configurable.
                CURLOPT_USERAGENT => "Food Order Website v0.1.",
                CURLOPT_POST => count($postParams),
                CURLOPT_POSTFIELDS => http_build_query($postParams)
            )
        );

        // Execute.
        $response = curl_exec($curl);
        // And close connection.
        curl_close($curl);

        return $this->decodeResponse($response);
    }

    /**
     * Executes a get request.
     * @param $servicesUrl
     * @param $servicePath
     * @param $getParams
     * @return mixed
     * @throws \Exception
     */
    public function get($servicesUrl, $servicePath, $getParams) {
        // Configure curl.
        $curl = curl_init();
        curl_setopt_array($curl, array(
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_URL => $servicesUrl . $servicePath . "?" . http_build_query($getParams),
                // TODO: Make configurable.
                CURLOPT_USERAGENT => "Food Order Website v0.1."
            )
        );

        // Execute.
        $response = curl_exec($curl);
        // And close connection.
        curl_close($curl);

        return $this->decodeResponse($response);
    }

    // Convenience method used for parsing a response.
    private function decodeResponse($response) {
        $response = json_decode($response);
        // If the response is not JSON, throw an exception.
        if (is_null($response)) {
            throw new \Exception("Invalid API response.");
        } else {
            // Else check if there was an authentication problem.
            if ( is_object($response)
                && property_exists($response, "success")
                && $response->success == false
                && property_exists($response, "reason")
                && $response->reason == 1) {
                throw new \Exception("Invalid API credentials.");
            }
        }
        return $response;
    }
}