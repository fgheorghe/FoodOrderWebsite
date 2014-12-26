<?php

namespace dft\SiteBundle\Controller;

class MenuController extends BaseController
{
    public function indexAction($categoryId = null)
    {
        return $this->render(
            'dftSiteBundle:Menu:menu.html.twig',
            array(
                "selected_category_id" => $categoryId,
                // Get menu item categories.
                "menu_item_categories" => $this->getApiClientService()->getMenuItemCategories(),
                // Get category menu items.
                "menu_items" => $this->getApiClientService()->getCategoryMenuItems(
                        $categoryId
                    )
            )
        );
    }
}
