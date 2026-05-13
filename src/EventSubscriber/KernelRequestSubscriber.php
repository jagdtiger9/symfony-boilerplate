<?php

declare(strict_types=1);

namespace Aljerom\SymfonyBoilerplate\EventSubscriber;

use JsonException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Uid\Uuid;

final readonly class KernelRequestSubscriber implements EventSubscriberInterface
{
    private const string UNIQUE_ID_NAME = '_request.unique.id';

    public function __construct(
        private string $defaultTimeZone,
        private string $defaultLocale,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [
                ['presetDefaults', 1],
                ['uniqueRequestId', 5],
                ['jsonPayload', 15],
                ['setLocale', 20],
            ],
        ];
    }

    public function presetDefaults(): void
    {
        date_default_timezone_set($this->defaultTimeZone);
    }

    public function uniqueRequestId(RequestEvent $event): void
    {
        $event->getRequest()?->attributes?->set(self::UNIQUE_ID_NAME, Uuid::v7()->toString());
    }

    public function jsonPayload(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if (!$this->supports($request)) {
            return;
        }

        try {
            $data = json_decode((string) $request->getContent(), true, 512, JSON_THROW_ON_ERROR);
            if (is_array($data)) {
                $request->request->replace($data);
            }
        } catch (JsonException $exception) {
            $event->setResponse(new JsonResponse(['message' => $exception->getMessage()], Response::HTTP_BAD_REQUEST));
        }
    }

    public function setLocale(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if (!$request->hasPreviousSession()) {
            return;
        }

        if ($locale = $request->attributes->get('_locale')) {
            $request->getSession()->set('_locale', $locale);
        } else {
            $request->setLocale($request->getSession()->get('_locale', $this->defaultLocale));
        }
    }

    private function supports(Request $request): bool
    {
        return in_array($request->getContentTypeFormat(), ['json', 'jsonb'], true)
            && $request->getContent();
    }
}
