<?php

namespace dft\SiteBundle\Controller;

class MenuController extends BaseController
{
    public function indexAction()
    {
        // TODO: Implement.
        return $this->render('dftSiteBundle:Menu:menu.html.twig', array(
                "menu_item_categories" => $this->getApiClientService()->getMenuItemCategories() // Get menu item categories.
        ));
    }
}
