<?php

declare(strict_types=1);

namespace MarkupCarve\CakeCarve\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use InvalidArgumentException;
use MarkupCarve\CakeCarve\Carve\RendererFactory;
use Throwable;

class RenderCommand extends Command
{
    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        return $parser->addArgument('file', ['required' => true])
            ->addOption('preset', ['help' => 'Rendering preset'])
            ->addOption('format', ['help' => 'html, text, markdown or ansi', 'default' => 'html'])
            ->addOption('output', ['help' => 'Output file']);
    }

    public function execute(Arguments $args, ConsoleIo $io): int
    {
        try {
            $path = (string)$args->getArgument('file');
            $preset = $args->getOption('preset');
            $options = is_string($preset) ? ['preset' => $preset] : [];
            $format = $args->getOption('format') ?? 'html';
            if (!in_array($format, ['html', 'text', 'markdown', 'ansi'], true)) {
                throw new InvalidArgumentException('Unknown Carve render format: ' . (string)$format);
            }
            $html = RendererFactory::create()->renderFileAs($path, $format, $options);
            $output = $args->getOption('output');
            if (is_string($output)) {
                AtomicWrite::write($output, $html);
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
