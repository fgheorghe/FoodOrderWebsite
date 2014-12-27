<?php

namespace dft\SiteBundle\Controller;

class LoginController extends BaseController
{
    public function loginAction()
    {
        // _GET values.
        $query = $this->container->get("request")->query;

        $errorMessage = "";
        // Return url.
        $returnUrl = $query->get("return", "menu");

        // If the user tried making a payment, and is not logged in, then display an
        // error message.
        if ($returnUrl == "payment") {
            $errorMessage = "Please login to make a payment.";
        }

        // Check if form data is posted, and if so, try and authenticate.
        if ($this->getRequest()->isMethod('POST')) {
            // _POST values.
            $request = $this->container->get("request");

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
        }

        return $this->render('dftSiteBundle:Login:login.html.twig', array(
                "error_message" => $errorMessage
            )
        );
    }

    public function logoutAction() {
        $this->getLoginService()->doLogout();
        // Redirect to menu page.
        return $this->redirect(
            $this->generateUrl('dft_site_menu')
        );
    }
}
