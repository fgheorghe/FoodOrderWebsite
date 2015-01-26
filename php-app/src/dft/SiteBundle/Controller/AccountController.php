<?php

namespace dft\SiteBundle\Controller;

class AccountController extends BaseController
{
    public function indexAction()
    {
        $errorMessage = "";
        // If the user is logged in, then this page should not be displayed.
        if (!$this->getLoginService()->isAuthenticated()) {
            // Redirect to menu page.
            return $this->redirect(
                $this->generateUrl('dft_site_menu')
            );
        }
        // If the user submitted some form data, update their profile.
        if ($this->getRequest()->isMethod('POST')) {
            $canUpdate = true; // The update can happen.

            // _POST values.
            $request = $this->container->get("request");

            $name = $request->get('name');
            $email = $request->get('email');
            $postCode = $request->get('post_code');
            $address = $request->get('address');
            $phoneNumber = $request->get('phone_number');
            $currentPassword = $request->get('current_password');
            $newPassword = $request->get('new_password');

            $customerData = $this->getLoginService()->getAuthenticatedCustomerData();

            // If any of the fields are empty (except for current password and password), display an error message.
            if (empty($name) || empty($email) || empty($postCode) || empty($address) || empty($phoneNumber)) {
                $errorMessage = "All input fields are mandatory, except Current and New Password.";
                $canUpdate = false; // User can not update profile;
            } else {
                // Check if the user is trying to update their password. If so, the current password field is mandatory.
                if (!empty($newPassword) && empty($currentPassword)) {
                    $errorMessage = "Please input your current password.";
                    $canUpdate = false; // User can not update profile;
                } elseif (!empty($newPassword) && !empty($currentPassword)) {
                    // Validate the current user password.
                    if (!$this->getLoginService()->login($customerData->email, $currentPassword)) {
                        $errorMessage = "Invalid current password.";
                        $canUpdate = false; // User can not update profile;
                    }
                }
            }

            // Validate form data.
            if (!$this->getFormValidatorsService()->isValidEmailAddress($email)) {
                $canUpdate = false;
                $errorMessage = "Please input a valid email address.";
            } elseif (!$this->getFormValidatorsService()->isValidName($name)) {
                $canUpdate = false;
                $errorMessage = "Please input a valid name.";
            } elseif (!$this->getFormValidatorsService()->isValidUkPostCode($postCode)) {
                $canUpdate = false;
                $errorMessage = "Please input a valid UK post code.";
            } elseif (!$this->getFormValidatorsService()->isValidUkAddress($address)) {
                $canUpdate = false;
                $errorMessage = "Please input a valid UK address.";
            } elseif (!$this->getFormValidatorsService()->isValidUkPhoneNumber($phoneNumber)) {
                $canUpdate = false;
                $errorMessage = "Please input a valid UK phone number.";
            } elseif (!empty($newPassword) && !empty($currentPassword) && !$this->getFormValidatorsService()->isValidPassword($newPassword)) {
                $canUpdate = false;
                $errorMessage = "Please input a valid password (minimum 7 characters long).";
            }

            // Update the customer data.
            if ($canUpdate) {
                $response = $this->getApiClientService()->updateCustomerProfile(
                    $customerData->id,
                    $name,
                    $email,
                    $postCode,
                    $address,
                    $phoneNumber,
                    $newPassword
                );

                if ($response->success == true) {
                    // Update session data.
                    $this->getLoginService()->storeCustomerDataInSession($response->data);
                    // Redirect to profile page, if success.
                    return $this->redirect(
                        $this->generateUrl('dft_site_account')
                    );
                } else {
                    $errorMessage = "Can not update account: " . $response->reason;
                }
            }
        }

        return $this->render('dftSiteBundle:Account:account.html.twig', array("error_message" => $errorMessage));
    }
}
