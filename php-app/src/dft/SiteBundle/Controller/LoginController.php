<?php

namespace dft\SiteBundle\Controller;

class LoginController extends BaseController
{
    public function loginAction()
    {
        $errorMessage = "";
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
                    // Redirect to menu page.
                    return $this->redirect(
                        $this->generateUrl('dft_site_menu')
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
