<?php

namespace dft\SiteBundle\Services;

use dft\SiteBundle\Traits\ContainerAware;
use dft\SiteBundle\Traits\Logger;

// TODO: Perhaps move to API.
class FormValidators {
    use ContainerAware;
    use Logger;

    /**
     * Validates an email address.
     * @param $email
     * @return bool
     */
    public function isValidEmailAddress($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    /**
     * Validates a name.
     * @param $name
     * @return bool
     */
    public function isValidName($name) {
        return strlen(trim($name)) > 3;
    }

    /**
     * Validate a UK post code: http://stackoverflow.com/questions/14935013/preg-match-regex-required-for-specific-uk-postcode-area-code
     * @param $postCode
     * @return bool
     */
    public function isValidUkPostCode($postCode) {
        return preg_match('#^(GIR ?0AA|[A-PR-UWYZ]([0-9]{1,2}|([A-HK-Y][0-9]([0-9ABEHMNPRV-Y])?)|[0-9][A-HJKPS-UW]) ?[0-9][ABD-HJLNP-UW-Z]{2})$#', $postCode) ? true : false;
    }

    /**
     * Validates a UK address.
     * @param $address
     * @return bool
     */
    public function isValidUkAddress($address) {
        return strlen(trim($address)) > 3;
    }

    /**
     * Validates a UK phone number (landline or mobile): http://regexlib.com/Search.aspx?k=uk%20mobile
     * @param $phoneNumber
     * @return bool
     */
    public function isValidUkPhoneNumber($phoneNumber) {
        return preg_match('#^(?:\(\+?44\)\s?|\+?44 ?)?(?:0|\(0\))?\s?(?:(?:1\d{3}|7[1-9]\d{2}|20\s?[78])\s?\d\s?\d{2}[ -]?\d{3}|2\d{2}\s?\d{3}[ -]?\d{4})$#', $phoneNumber)
            || preg_match('#^(\+44\s?7\d{3}|\(?07\d{3}\)?)\s?\d{3}\s?\d{3}$#', $phoneNumber) ? true : false;
    }

    /**
     * Validates a password.
     * @param $password
     * @return bool
     */
    public function isValidPassword($password) {
        return strlen(trim($password)) > 6;
    }
}