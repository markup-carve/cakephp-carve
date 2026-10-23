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

class RenderCommand extends Command
{
    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        return $parser->addArgument('file', ['required' => true])
            ->addOption('preset', ['help' => 'Rendering preset'])
            ->addOption('output', ['help' => 'Output file']);
    }

    public function execute(Arguments $args, ConsoleIo $io): int
    {
        try {
            $path = (string)$args->getArgument('file');
            $preset = $args->getOption('preset');
            $options = is_string($preset) ? ['preset' => $preset] : [];
            $html = RendererFactory::create()->renderFile($path, $options)->html();
            $output = $args->getOption('output');
            if (is_string($output)) {
                if (file_put_contents($output, $html) === false) {
                    throw new RuntimeException('Cannot write output file.');
                }
            } else {
                $io->out($html);
            }

            return static::CODE_SUCCESS;
        } catch (Throwable $exception) {
            $io->err($exception->getMessage());

            return static::CODE_ERROR;
        }
    }
}
