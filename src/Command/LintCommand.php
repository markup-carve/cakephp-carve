<?php

declare(strict_types=1);

namespace MarkupCarve\CakeCarve\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use MarkupCarve\CakeCarve\Carve\RendererFactory;
use Throwable;

class LintCommand extends Command
{
    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        return $parser->addArgument('files', ['separator' => ',', 'required' => true])
            ->addOption('include-root', ['help' => 'Absolute root for include linting']);
    }

    public function execute(Arguments $args, ConsoleIo $io): int
    {
        $failed = false;
        try {
            $paths = FilePaths::expand($args->getArrayArgument('files') ?? []);
        } catch (Throwable $exception) {
            $io->err($exception->getMessage());

            return static::CODE_ERROR;
        }
        foreach ($paths as $path) {
            try {
                $root = $args->getOption('include-root');
                $options = is_string($root) ? ['includeRoot' => $root] : [];
                foreach (RendererFactory::create()->lintFile($path, $options) as $finding) {
                    $io->err(sprintf('%s:%d:%d: %s: %s', $finding['file'] ?? $path, $finding['line'], $finding['column'], $finding['rule'], $finding['message']));
                    $failed = true;
                }
            } catch (Throwable $exception) {
                $io->err($exception->getMessage());
                $failed = true;
            }
        }

        return $failed ? static::CODE_ERROR : static::CODE_SUCCESS;
    }
}
