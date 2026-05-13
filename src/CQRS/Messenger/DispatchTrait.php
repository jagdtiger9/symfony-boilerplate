<?php

declare(strict_types=1);

namespace Aljerom\SymfonyBoilerplate\CQRS\Messenger;

use Symfony\Component\Messenger\Exception\HandlerFailedException;

trait DispatchTrait
{
    private function dispatchWrap(object $message): mixed
    {
        try {
            return $this->handle($message);
        } catch (HandlerFailedException $e) {
            while ($e instanceof HandlerFailedException) {
                /** @var \Throwable $e */
                $e = $e->getPrevious();
            }

            throw $e;
        }
    }
}
