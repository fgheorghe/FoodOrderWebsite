<?php

namespace dft\SiteBundle\Controller;

class OrdersController extends BaseController
{
    public function indexAction()
    {
        // If the user is logged in, then this page should not be displayed.
        if (!$this->getLoginService()->isAuthenticated()) {
            // Redirect to menu page.
            return $this->redirect(
                $this->generateUrl('dft_site_menu')
            );
        }

        // Otherwise, the orders.
        $customer = $this->getLoginService()->getAuthenticatedCustomerData();
        $orders = $this->getApiClientService()->getCustomerOrders($customer->id);

        // Prepare some user friendly status codes.
        $statusCodes = array(
            // Based on UI OrderStatuses.
            0 => "Pending",
            1 => "Pending",
            2 => "Accepted",
            3 => "Rejected",
            99 => "Rejected"
        );

        return $this->render('dftSiteBundle:Orders:orders.html.twig', array(
                "orders" => $orders->data,
                "status_codes" => $statusCodes
            )
        );
    }
}
