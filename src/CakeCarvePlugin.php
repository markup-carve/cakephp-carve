<?php

declare(strict_types=1);

namespace MarkupCarve\CakeCarve;

use Cake\Console\CommandCollection;
use Cake\Core\BasePlugin;
use MarkupCarve\CakeCarve\Command\ConvertCommand;
use MarkupCarve\CakeCarve\Command\FormatCommand;
use MarkupCarve\CakeCarve\Command\LintCommand;
use MarkupCarve\CakeCarve\Command\RenderCommand;

class CakeCarvePlugin extends BasePlugin
{
    public function console(CommandCollection $commands): CommandCollection
    {
        $commands->add('carve render', RenderCommand::class);
        $commands->add('carve convert', ConvertCommand::class);
        $commands->add('carve format', FormatCommand::class);
        $commands->add('carve lint', LintCommand::class);

        return $commands;
    }
}
