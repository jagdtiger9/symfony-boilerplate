<?php

declare(strict_types=1);

namespace Aljerom\SymfonyBoilerplate\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\Stamp\ErrorDetailsStamp;

/**
 * Replaces the default AddErrorDetailsStampListener to avoid including FlattenException
 * (with full stack trace) in the stamp, which causes "table too large for buffer" in AMQP.
 * @see https://github.com/symfony/symfony/issues/45944#issuecomment-1167254325
 */
final class AddErrorDetailsStampSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            WorkerMessageFailedEvent::class => ['onMessageFailed', 200],
        ];
    }

    public function onMessageFailed(WorkerMessageFailedEvent $event): void
    {
        $throwable = $event->getThrowable();
        if ($throwable instanceof HandlerFailedException) {
            $throwable = $throwable->getPrevious();
        }

        if ($throwable === null) {
            return;
        }

        $stamp = new ErrorDetailsStamp($throwable::class, $throwable->getCode(), $throwable->getMessage());
        $previousStamp = $event->getEnvelope()->last(ErrorDetailsStamp::class);

        if ($previousStamp === null || !$previousStamp->equals($stamp)) {
            $event->addStamps($stamp);
        }
    }
}
