<?php

declare(strict_types=1);

namespace Aljerom\SymfonyBoilerplate\CQRS;

interface CommandBus
{
    public function dispatch(Command $command): mixed;
}
