<?php

namespace dft\SiteBundle\Controller;

class CheckServicesController extends BaseController
{
    public function indexAction()
    {
        // _GET values.
        $query = $this->container->get("request")->query;
        $restaurantSettings = $this->getApiClientService()->getRestaurantSettings();

        return $this->render('dftSiteBundle:CheckServices:check-services.html.twig', array(
                "service_coverage" => $this->getApiClientService()->getServiceCoverage(
                        $restaurantSettings->restaurant_post_code,
                        $query->get('postcode'),
                        $restaurantSettings->delivery_range
                    ),
                "post_code" => $query->get('postcode')
            )
        );
    }
}
