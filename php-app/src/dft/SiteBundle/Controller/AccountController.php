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

            // If any of the fields are empty (except for current password and password), display and error message.
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

                // Update session data.
                $this->getLoginService()->storeCustomerDataInSession($response->data);
                // Redirect to profile page.
                return $this->redirect(
                    $this->generateUrl('dft_site_account')
                );
            }
        }

        return $this->render('dftSiteBundle:Account:account.html.twig', array("error_message" => $errorMessage));
    }
}
