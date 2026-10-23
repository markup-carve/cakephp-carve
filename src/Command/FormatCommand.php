<?php

declare(strict_types=1);

namespace MarkupCarve\CakeCarve\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use MarkupCarve\CakeCarve\Carve\RendererFactory;
use RuntimeException;
use Throwable;

class FormatCommand extends Command
{
    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        return $parser->addArgument('files', ['separator' => ',', 'required' => true])
            ->addOption('check', ['boolean' => true])
            ->addOption('write', ['boolean' => true]);
    }

    public function execute(Arguments $args, ConsoleIo $io): int
    {
        if ($args->getOption('check') && $args->getOption('write')) {
            $io->err('Choose --check or --write.');

            return static::CODE_ERROR;
        }
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
                $formatted = RendererFactory::create()->format($source);
                if ($args->getOption('check') && $formatted !== $source) {
                    $io->err($path . ' needs formatting');
                    $failed = true;
                } elseif ($args->getOption('write')) {
                    if (file_put_contents($path, $formatted) === false) {
                        throw new RuntimeException('Cannot write ' . $path);
                    }
                } elseif (!$args->getOption('check')) {
                    $io->out($formatted, 0);
                }
            } catch (Throwable $exception) {
                $io->err($exception->getMessage());
                $failed = true;
            }
        }

        return $failed ? static::CODE_ERROR : static::CODE_SUCCESS;
    }
}
