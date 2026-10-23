<?php

declare(strict_types=1);

namespace MarkupCarve\CakeCarve\Test\TestCase\Command;

use Cake\Console\CommandCollection;
use Cake\Http\BaseApplication;
use Cake\Http\MiddlewareQueue;
use Cake\Routing\RouteBuilder;
use MarkupCarve\CakeCarve\CakeCarvePlugin;

class CarveTestApplication extends BaseApplication
{
    public function bootstrap(): void
    {
    }

    public function routes(RouteBuilder $routes): void
    {
    }

    public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue
    {
        return $middlewareQueue;
    }

    public function console(CommandCollection $commands): CommandCollection
    {
        return (new CakeCarvePlugin())->console($commands);
    }
}
