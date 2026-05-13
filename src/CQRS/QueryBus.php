<?php

declare(strict_types=1);

namespace Aljerom\SymfonyBoilerplate\CQRS;

interface QueryBus
{
    public function query(Query $query): mixed;
}
