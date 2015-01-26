<?php

namespace dft\SiteBundle\Controller;

class ContactController extends BaseController
{
    public function indexAction()
    {
        $errorMessage = "";

        // _POST values.
        $request = $this->container->get("request");

        // Prepare form data.
        $name = $request->get('name', "");
        $email = $request->get('email', "");
        $phoneNumber = $request->get('phone_number', "");
        $message = $request->get('message', "");

        // If the user submitted some form data, send an email to the administrator.
        if ($this->getRequest()->isMethod('POST')) {
            $errorMessage = "Your message has been sent. We will contact you shortly.";

            // Validate form data.
            $canEmail = true;
            if (!$this->getFormValidatorsService()->isValidName($name)) {
                $canEmail = false;
                $errorMessage = "Please input a valid name.";
            } elseif (!$this->getFormValidatorsService()->isValidEmailAddress($email)) {
                $canEmail = false;
                $errorMessage = "Please input a valid email address.";
            } elseif (!$this->getFormValidatorsService()->isValidUkPhoneNumber($phoneNumber)) {
                $canEmail = false;
                $errorMessage = "Please input a valid UK phone number.";
            } elseif (strlen(trim($message)) == 0) {
                $canEmail = false;
                $errorMessage = "Please input a message.";
            }
            // Send the email.
            if ($canEmail) {
                // Get restaurant settings.
                $restaurantSettings = $this->getApiClientService()->getRestaurantSettings();
                // We send the email straight from here, as its quite basic.
                // If needed, move to a service.
                $mailer = $this->container->get('mailer');
                $content = $mailer->createMessage()
                    ->setSubject('Website inquiry site ' . $restaurantSettings->restaurant_name . '(' . $restaurantSettings->domain_name . ')')
                    ->setFrom('contact-form@' . $restaurantSettings->domain_name)
                    ->setTo($restaurantSettings->site_contact_recipient_email)
                    ->setBody(
                        $this->container->get('templating')->render(
                            'dftSiteBundle:Emails:contact-form-message.html.twig',
                            array(
                                'name' => $name,
                                'email_address' => $email,
                                'phone_number' => $phoneNumber,
                                'message' => $message,
                                'restaurant_settings' => $restaurantSettings
                            )
                        )
                    );
                $mailer->send($content);
            }
        }

        return $this->render('dftSiteBundle:Contact:contact.html.twig', array(
                "error_message" => $errorMessage,
                "form_input" => array(
                    "name" => $name,
                    "email" => $email,
                    "phone_number" => $phoneNumber,
                    "message" => $message
                )
            )
        );
    }
}
