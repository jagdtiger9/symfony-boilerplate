<?php

declare(strict_types=1);

namespace Aljerom\SymfonyBoilerplate\EventSubscriber;

use Aljerom\SymfonyBoilerplate\Request\ValidationException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Validator\ConstraintViolation;

final class ValidationExceptionListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['__invoke', 0],
        ];
    }

    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        if (!$exception instanceof ValidationException) {
            return;
        }

        $errors = [];
        /** @var ConstraintViolation $violation */
        foreach ($exception->getViolations() as $violation) {
            $errors[] = [
                'property' => $violation->getPropertyPath(),
                'value' => $violation->getInvalidValue(),
                'message' => $violation->getMessage(),
            ];
        }

        $event->setResponse(new JsonResponse([
            'message' => $exception->getMessage(),
            'errors' => $errors,
        ], 400));
    }
}
