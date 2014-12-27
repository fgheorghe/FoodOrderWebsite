<?php

namespace dft\SiteBundle\Controller;

class ContactController extends BaseController
{
    public function indexAction()
    {
        $errorMessage = "";
        // If the user submitted some form data, send an email to the administrator.
        if ($this->getRequest()->isMethod('POST')) {
            $errorMessage = "Your message has been sent. We will contact you shortly.";
            // _POST values.
            $request = $this->container->get("request");

            $name = $request->get('name');
            $email = $request->get('email');
            $phoneNumber = $request->get('phone_number');
            // TODO: Send email.
        }

        return $this->render('dftSiteBundle:Contact:contact.html.twig', array(
                "error_message" => $errorMessage
            )
        );
    }
}
