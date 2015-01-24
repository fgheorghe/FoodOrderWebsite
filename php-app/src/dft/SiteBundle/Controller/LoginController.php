<?php

namespace dft\SiteBundle\Controller;

class LoginController extends BaseController
{
    public function loginAction()
    {
        // _GET values.
        $query = $this->container->get("request")->query;
        // _POST values.
        $request = $this->container->get("request");

        $errorMessage = "";
        $registrationErrorMessage = "";
        // Return url.
        $returnUrl = $query->get("return", "menu");
        // Action type. This may be 'register' or it defaults to login.
        $action = $request->get('action');

        // If the user tried making a payment, and is not logged in, then display an
        // error message.
        switch ($returnUrl) {
            case "payment":
                $errorMessage = "Please login to make a payment.";
                break;
            case "cart":
                $errorMessage = "Please login to view your shopping cart.";
                break;
            default:
                // Do nothing.
                break;
        }

        // Check if form data is posted, and if so, try and authenticate.
        if ($this->getRequest()->isMethod('POST') && empty($action)) {
            $username = $request->get('email');
            $password = $request->get('password');

            // If any of these two are empty, display an error message.
            if (empty($username) || empty($password)) {
                $errorMessage = "Please input you email address and password.";
            } else {
                // Authenticate the user.
                $loginService = $this->getLoginService();
                $success = $loginService->login($username, $password);
                if (!$success) {
                    $errorMessage = "Invalid email address or password.";
                } else {
                    switch ($returnUrl) {
                        case "payment":
                            $redirect = $this->generateUrl('dft_site_payment');
                            break;
                        case "cart":
                            $redirect = $this->generateUrl('dft_site_cart');
                            break;
                        case "menu":
                        default:
                            $redirect = $this->generateUrl('dft_site_menu');
                            break;
                    }
                    // Redirect to menu page.
                    return $this->redirect(
                        $redirect
                    );
                }
            }
        } elseif ($this->getRequest()->isMethod('POST') && $action == "register") {
            $name = $request->get('name');
            $email = $request->get('email');
            $postCode = $request->get('post_code');
            $address = $request->get('address');
            $phoneNumber = $request->get('phone_number');
            $password = $request->get('password');
            $confirmPassword = $request->get('confirm_password');

            // Store whether user input is valid and the user can register.
            $canRegister = true;

            // Check if all fields are set. If not, display an error message.
            if (empty($name) || empty($email) || empty($postCode) || empty($address)
                || empty($phoneNumber) || empty($password) || empty($confirmPassword)
            ) {
                $registrationErrorMessage = "All fields are mandatory.";
                $canRegister = false;
            } else {
                // Check if password fields match.
                if ($password != $confirmPassword) {
                    $registrationErrorMessage = "Password mismatch.";
                    $canRegister = false;
                }
            }
            if ($canRegister) {
                // Create a new account.
                $response = $this->getApiClientService()->createCustomer(
                    $name,
                    $email,
                    $postCode,
                    $address,
                    $phoneNumber,
                    $password
                );
                $registrationErrorMessage = $response->success == true ?
                    "Account created. Please login to continue."
                    : "Can not create account: " . $response->reason;
            }
        }

        return $this->render(
            'dftSiteBundle:Login:login.html.twig',
            array(
                "error_message" => $errorMessage,
                "registration_error_message" => $registrationErrorMessage
            )
        );
    }

    public function logoutAction()
    {
        $this->getLoginService()->doLogout();
        // Redirect to menu page.
        return $this->redirect(
            $this->generateUrl('dft_site_menu')
        );
    }
}
