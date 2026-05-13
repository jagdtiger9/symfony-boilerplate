<?php

declare(strict_types=1);

namespace Aljerom\SymfonyBoilerplate\EventSubscriber;

use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;
use Symfony\Component\VarDumper\VarDumper;

final readonly class ConsoleDumperSubscriber implements EventSubscriberInterface
{
    public function __construct(private string $defaultTimeZone)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ConsoleCommandEvent::class => [['__invoke', 1]],
            ConsoleEvents::COMMAND => [['presetDefaults', 1]],
        ];
    }

    public function presetDefaults(ConsoleCommandEvent $event): void
    {
        date_default_timezone_set($this->defaultTimeZone);
    }

    public function __invoke(ConsoleCommandEvent $event): void
    {
        VarDumper::setHandler(function (mixed $var): ?string {
            $cloner = new VarCloner();
            $dumper = 'cli' === PHP_SAPI ? new CliDumper() : new HtmlDumper();
            return $dumper->dump($cloner->cloneVar($var));
        });
    }
}
