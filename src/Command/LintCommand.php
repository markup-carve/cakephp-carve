<?php

declare(strict_types=1);

namespace MarkupCarve\CakeCarve\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Configure;
use MarkupCarve\CakeCarve\Carve\RendererFactory;
use MarkupCarve\Carve\Lint\IncludeLinter;
use MarkupCarve\Carve\Transform\FilesystemIncludeResolver;
use RuntimeException;
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
                $source = file_get_contents($path);
                if ($source === false) {
                    throw new RuntimeException('Cannot read ' . $path);
                }
                $root = $args->getOption('include-root') ?? Configure::read('Carve.includeRoot');
                if (is_string($root)) {
                    $findings = (new IncludeLinter())->lint($source, [], new FilesystemIncludeResolver($root), realpath($path) ?: $path);
                    foreach ($findings as $finding) {
                        $io->err(sprintf('%s:%d:%d: %s: %s', $finding->file ?? $path, $finding->line, $finding->column, $finding->rule, $finding->message));
                        $failed = true;
                    }
                } else {
                    foreach (RendererFactory::create()->lint($source) as $finding) {
                        $io->err(sprintf('%s:%d:%d: %s: %s', $path, $finding['line'], $finding['column'], $finding['rule'], $finding['message']));
                        $failed = true;
                    }
                }
            } catch (Throwable $exception) {
                $io->err($exception->getMessage());
                $failed = true;
            }
        }

        return $failed ? static::CODE_ERROR : static::CODE_SUCCESS;
    }
}
