<?php

declare(strict_types=1);

namespace Aljerom\SymfonyBoilerplate\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\KernelInterface;

final class ResponseSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly KernelInterface $kernel)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => [['onKernelResponse', 10]],
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$this->kernel->isDebug()) {
            return;
        }

        $request = $event->getRequest();
        if (!$request->isXmlHttpRequest()) {
            return;
        }

        $event->getResponse()->headers->set('Symfony-Debug-Toolbar-Replace', '1');
    }
}
