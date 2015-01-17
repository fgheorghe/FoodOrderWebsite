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

    // Barclays specific constants.
    const PAYMENT_INVALID_OR_INCOMPLETE = 0x00;
    const PAYMENT_CANCELLED_BY_CUSTOMER = 0x01;
    const PAYMENT_AUTHORISATION_DECLINED = 0x02;
    const PAYMENT_AUTHORISED = 0x05;
    const PAYMENT_PAYMENT_REQUESTED = 0x09;

    // Defaults.
    const DEFAULT_CURRENCY = "GBP";
    const DEFAULT_LANGUAGE = "en_GB";
    const DEFAULT_SCHEMA = "http";
    const DEFAULT_PAYMENT_PAGE = "payment/";

    // Stores configuration.
    private $psPid;
    private $sha;
    private $domainName;
    private $live;

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
     * Method used for constructing the return payment url.
     * @return string
     */
    public function getPaymentReturnUrl() {
        return self::DEFAULT_SCHEMA . "://" . $this->getRestaurantDomainName() . "/" . self::DEFAULT_PAYMENT_PAGE;
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

    /**
     * Sets the restaurant domain name, for constructing the return url in getPaymentReturnUrl.
     * This value is part of the restaurant settings service, but returned by the payment configuration
     * service as a convenience.
     * @param $domainName
     */
    public function setRestaurantDomainName($domainName) {
        $this->domainName = $domainName;
    }

    /**
     * Fetches the
     * @return mixed
     */
    public function getRestaurantDomainName() {
        return $this->domainName;
    }

    /**
     * Set the payment system to live or not.
     * @param $live
     */
    public function setLive($live) {
        $this->live = $live;
    }

    /**
     * Fetches the system live configuration.
     * @return mixed
     */
    public function getLive() {
        return $this->live;
    }
}