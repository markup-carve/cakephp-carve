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

class ConvertCommand extends Command
{
    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        return $parser->addArgument('file', ['required' => true])
            ->addOption('from', ['help' => 'markdown, html, djot, or bbcode', 'required' => true])
            ->addOption('output', ['help' => 'Output file'])
            ->addOption('check-loss', ['boolean' => true]);
    }

    public function execute(Arguments $args, ConsoleIo $io): int
    {
        try {
            $source = file_get_contents((string)$args->getArgument('file'));
            if ($source === false) {
                throw new RuntimeException('Cannot read input file.');
            }
            $result = RendererFactory::create()->import($source, (string)$args->getOption('from'));
            // Checked before writing so a rejected conversion never replaces an existing output file.
            if ($args->getOption('check-loss')) {
                $lossy = false;
                foreach ($result->diagnostics as $diagnostic) {
                    if (in_array($diagnostic->fidelity, ['degraded', 'dropped'], true)) {
                        $io->err($diagnostic->message);
                        $lossy = true;
                    }
                }
                if ($lossy) {
                    return static::CODE_ERROR;
                }
            }
            $output = $args->getOption('output');
            if (is_string($output)) {
                if (file_put_contents($output, $result->value) === false) {
                    throw new RuntimeException('Cannot write output file.');
                }
            } else {
                $io->out($result->value);
            }

            return static::CODE_SUCCESS;
        } catch (Throwable $exception) {
            $io->err($exception->getMessage());

            return static::CODE_ERROR;
        }
    }
}
