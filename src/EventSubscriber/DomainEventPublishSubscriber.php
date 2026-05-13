<?php

declare(strict_types=1);

namespace Aljerom\SymfonyBoilerplate\EventSubscriber;

use Aljerom\SymfonyBoilerplate\DomainEvents\DomainEventInterface;
use Aljerom\SymfonyBoilerplate\DomainEvents\EventRepositoryInterface;
use DateTimeImmutable;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Serializer\SerializerInterface;

class DomainEventPublishSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly EventRepositoryInterface $eventRepo,
        private readonly EntityManagerInterface $entityManager,
        private readonly MessageBusInterface $eventBus,
        private readonly SerializerInterface $serializer,
        private readonly LoggerInterface $logger,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::TERMINATE => [['onHttpProcess', 10]],
            ConsoleEvents::TERMINATE => [['onConsoleProcess', 10]],
        ];
    }

    public function onHttpProcess(TerminateEvent $event): void
    {
        $this->publishPending();
    }

    public function onConsoleProcess(ConsoleTerminateEvent $event): void
    {
        $this->publishPending();
    }

    private function publishPending(): void
    {
        if (!$this->entityManager->isOpen()) {
            $this->logger->debug('=> EM closed, skipping domain event publish');
            return;
        }

        try {
            $storedEvents = $this->eventRepo->getUnpublished(new DateTimeImmutable());
        } catch (Exception) {
            return;
        }

        foreach ($storedEvents as $storedEvent) {
            try {
                $domainEvent = $this->serializer->deserialize(
                    $storedEvent->getEventBody(),
                    $storedEvent->getTypeName(),
                    'json'
                );
                assert($domainEvent instanceof DomainEventInterface);
                $this->logger->debug('==> Event dispatched: ' . $storedEvent->getTypeName());
                $this->eventBus->dispatch($domainEvent);
                $this->entityManager->remove($storedEvent);
            } catch (\Throwable $e) {
                $this->logger->debug('==> Event dispatch error: ' . $e->getMessage());
            }
        }

        $this->entityManager->flush();
    }
}
