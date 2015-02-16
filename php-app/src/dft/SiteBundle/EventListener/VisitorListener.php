<?php

namespace dft\SiteBundle\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use dft\SiteBundle\Traits\ContainerAware;

/**
 * Class VisitorListener. Add a visitor for statistics.
 * @package AppBundle\EventListener
 */
class VisitorListener {
    use ContainerAware;

    public function onKernelRequest(GetResponseEvent $event) {
        $ipAddress = $event->getRequest()->getClientIp();
        $this->getContainer()->get('dft_site.api_client')->recordVisitor($ipAddress);
    }
}