<?php

declare(strict_types=1);

namespace Aljerom\SymfonyBoilerplate\Request;

use Aljerom\SymfonyBoilerplate\CQRS\Command;
use Aljerom\SymfonyBoilerplate\CQRS\Query;
use Aljerom\SimpleHydrator\SimpleHydrator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class MessageValueResolver implements ValueResolverInterface
{
    public function __construct(
        private readonly SimpleHydrator $hydrator,
        private readonly ValidatorInterface $validator,
    ) {
    }

    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        $type = $argument->getType();
        if (!$type || !$this->isMessage($type)) {
            return [];
        }

        $message = $this->hydrator->hydrate($this->extractParams($request), $type);

        $violations = $this->validator->validate($message);
        if (count($violations) > 0) {
            throw new ValidationException($violations);
        }

        yield $message;
    }

    private function isMessage(string $type): bool
    {
        return is_subclass_of($type, Query::class) || is_subclass_of($type, Command::class);
    }

    private function extractParams(Request $request): array
    {
        $params = in_array($request->getMethod(), [Request::METHOD_POST, Request::METHOD_PUT], true)
            ? $request->request->all()
            : $request->query->all();

        if ($routeParams = $request->attributes->get('_route_params')) {
            $params = [...$routeParams, ...$params];
        }

        if ($request->files->count()) {
            $params += $request->files->all();
        }

        return $params;
    }
}
