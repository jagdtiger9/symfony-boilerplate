<?php

declare(strict_types=1);

namespace Aljerom\SymfonyBoilerplate\DomainEvents\Doctrine;

use Aljerom\SymfonyBoilerplate\DomainEvents\DomainEventInterface;
use Aljerom\SymfonyBoilerplate\DomainEvents\EventId;
use Aljerom\SymfonyBoilerplate\DomainEvents\EventRepositoryInterface;
use Aljerom\SymfonyBoilerplate\DomainEvents\StoredEvent;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Serializer\SerializerInterface;

abstract class AbstractDoctrineEventRepository implements EventRepositoryInterface
{
    private string $uniqueRequestId;

    public function __construct(
        protected readonly EntityManagerInterface $em,
        protected readonly SerializerInterface $serializer,
        protected readonly LoggerInterface $logger,
    ) {
        $this->uniqueRequestId = Uuid::uuid7()->toString();
    }

    /** @return class-string<StoredEvent> */
    abstract protected function getEntityClass(): string;

    abstract protected function createStoredEvent(
        EventId $eventId,
        string $typeName,
        DateTimeImmutable $occurredOn,
        string $aggregateRoot,
        string $eventBody,
        string $requestId,
    ): StoredEvent;

    public function nextIdentity(): EventId
    {
        return EventId::fromUuid(Uuid::uuid4());
    }

    public function append(DomainEventInterface $domainEvent): void
    {
        $occurredOn = DateTimeImmutable::createFromFormat(
            DomainEventInterface::MICROSECOND_DATE_FORMAT,
            $domainEvent->getOccurredOn()
        );
        assert($occurredOn instanceof DateTimeImmutable);

        $storedEvent = $this->createStoredEvent(
            $this->nextIdentity(),
            $domainEvent::class,
            $occurredOn,
            $domainEvent->getAggregateRootId(),
            $this->serializer->serialize($domainEvent, 'json'),
            $this->uniqueRequestId,
        );

        $this->logger->debug('=> Event stored: ' . $domainEvent::class);

        $this->em->persist($storedEvent);
        $classMetadata = $this->em->getClassMetadata($this->getEntityClass());
        $this->em->getUnitOfWork()->computeChangeSet($classMetadata, $storedEvent);
    }

    public function replace(DomainEventInterface $domainEvent): void
    {
        $unpublished = $this->em->getRepository($this->getEntityClass())->findBy([
            'aggregateRoot' => $domainEvent->getAggregateRootId(),
            'typeName' => $domainEvent::class,
            'publishedOn' => 0,
        ]);

        foreach ($unpublished as $event) {
            $this->em->remove($event);
        }

        $this->append($domainEvent);
    }

    public function publish(StoredEvent $storedEvent): void
    {
        $storedEvent->setPublishedOn(new DateTimeImmutable());
        $this->em->persist($storedEvent);
        $this->em->flush();
    }

    /** @return StoredEvent[] */
    public function getUnpublished(DateTimeImmutable $dateTime): array
    {
        return $this->em->createQueryBuilder()
            ->select('e')
            ->from($this->getEntityClass(), 'e')
            ->where('e.requestId = :requestId')
            ->setParameter('requestId', $this->uniqueRequestId)
            ->andWhere('e.publishedOn = 0')
            ->andWhere('e.occurredOn <= :now')
            ->setParameter('now', $dateTime)
            ->orderBy('e.occurredOn')
            ->getQuery()
            ->getResult();
    }
}
