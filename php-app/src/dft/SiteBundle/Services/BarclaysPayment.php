<?php
namespace dft\SiteBundle\Services;

use dft\SiteBundle\Traits\ContainerAware;
use dft\SiteBundle\Traits\Database;
use dft\SiteBundle\Traits\InternalServiceApiClient;
use dft\SiteBundle\Traits\Logger;
use dft\SiteBundle\Traits\Curl;

class BarclaysPayment {
    use ContainerAware;
    use Logger;

    // Defaults.
    const DEFAULT_CURRENCY = "GBP";
    const DEFAULT_LANGUAGE = "en_GB";

    // Stores configuration.
    private $paymentReturnUrl;
    private $psPid;
    private $sha;

    /**
     * Generates a Barclays specific SHA1 signature.
     * @param $params
     * @return string
     */
    public function generateSignature($params) {
        // Apply defaults.
        $params = array_merge(
            array(
                "PSPID" => $this->getPSPID(),
                "CURRENCY" => self::DEFAULT_CURRENCY,
                "LANGUAGE" => self::DEFAULT_LANGUAGE,
                // Capture all statuses within one URL.
                "ACCEPTURL" => $this->getPaymentReturnUrl(),
                "DECLINEURL" => $this->getPaymentReturnUrl(),
                "EXCEPTIONURL" => $this->getPaymentReturnUrl(),
                "CANCELURL" => $this->getPaymentReturnUrl(),
                "BACKURL" => $this->getPaymentReturnUrl()
            ), $params
        );

        ksort($params);

        // Construct string to hash.
        $string = "";
        foreach($params as $key => $value) {
            $string .= $key . "=" . $value . $this->getSHA();
        }
        return sha1($string);
    }

    /**
     * Method used for setting the return url.
     * @param $url
     */
    public function setPaymentReturnUrl($url) {
        $this->paymentReturnUrl = $url;
    }

    /**
     * Method used for constructing the return payment url.
     * @return string
     */
    public function getPaymentReturnUrl() {
        return $this->paymentReturnUrl;
    }

    /**
     * Method used for returning the configured PSPID.
     * @return string
     */
    public function getPSPID() {
       return $this->psPid;
    }

    /**
     * Configure psPid.
     * @param $psPid
     */
    public function setPSPID($psPid) {
        $this->psPid = $psPid;
    }

    /**
     * Sets the SHA1 value.
     * @param $sha
     */
    public function setSHA($sha) {
        $this->sha = $sha;
    }

    /**
     * Gets the configured SHA1 value.
     * @return mixed
     */
    public function getSHA() {
        return $this->sha;
    }
}