<?php

declare(strict_types=1);

namespace Aljerom\CqrsEvents\DomainEvents\Doctrine;

use Aljerom\CqrsEvents\DomainEvents\DomainEventAwareInterface;
use Aljerom\CqrsEvents\DomainEvents\EventRepositoryInterface;
use Aljerom\CqrsEvents\DomainEvents\ReplaceableDomainEventInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;

final class PersistDomainEventListener
{
    public function __construct(private readonly EventRepositoryInterface $eventRepo)
    {
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        $uow = $args->getObjectManager()->getUnitOfWork();

        foreach ([$uow->getScheduledEntityInsertions(), $uow->getScheduledEntityUpdates(), $uow->getScheduledEntityDeletions()] as $scheduled) {
            foreach ($scheduled as $entity) {
                if ($entity instanceof DomainEventAwareInterface) {
                    $this->persistEvents($entity);
                }
            }
        }
    }

    private function persistEvents(DomainEventAwareInterface $entity): void
    {
        foreach ($entity->releaseEvents() as $event) {
            if ($event instanceof ReplaceableDomainEventInterface) {
                $this->eventRepo->replace($event);
            } else {
                $this->eventRepo->append($event);
            }
        }
    }
}
