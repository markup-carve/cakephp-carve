<?php

declare(strict_types=1);

namespace MarkupCarve\CakeCarve\View\Helper;

use Cake\Core\Configure;
use Cake\View\Helper;
use Cake\View\View;
use MarkupCarve\CakeCarve\Carve\CarveRendererInterface;
use MarkupCarve\CakeCarve\Carve\RenderedCarve;
use MarkupCarve\CakeCarve\Carve\RendererFactory;

/**
 * @extends \Cake\View\Helper<\Cake\View\View>
 */
class CarveHelper extends Helper
{
    /**
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = ['debug' => null];

    private ?CarveRendererInterface $renderer = null;

    /**
     * @param \Cake\View\View $view
     * @param array<string, mixed> $config
     */
    public function __construct(View $view, array $config = [])
    {
        $global = Configure::read('Carve');
        parent::__construct($view, $config + (is_array($global) ? $global : []));
    }

    /**
     * @param string $source
     * @param array<string, mixed> $options
     */
    public function convert(string $source, array $options = []): string
    {
        $debug = $this->getConfig('debug') ?? Configure::read('debug');
        $start = microtime(true);
        $html = $this->renderer()->toHtml($source, $options);
        if ($debug) {
            $html .= '<!-- ' . number_format((microtime(true) - $start) * 1000, 3) . 'ms -->';
        }

        return trim($html);
    }

    /**
     * @param string $source
     * @param array<string, mixed> $options
     */
    public function render(string $source, array $options = []): RenderedCarve
    {
        return $this->renderer()->render($source, $options);
    }

    /**
     * @param string $path
     * @param array<string, mixed> $options
     */
    public function file(string $path, array $options = []): string
    {
        return $this->renderer()->renderFile($path, $options)->html();
    }

    /**
     * @return array<array{rule: string, message: string, line: int, column: int}>
     */
    public function lint(string $source): array
    {
        return $this->renderer()->lint($source);
    }

    public function format(string $source, bool $alignTables = false): string
    {
        return $this->renderer()->format($source, $alignTables);
    }

    /**
     * @param string $source
     * @param array<string, mixed> $options
     */
    public function text(string $source, array $options = []): string
    {
        return trim($this->renderer()->toText($source, $options));
    }

    /**
     * @param string $source
     * @param array<string, mixed> $options
     */
    public function markdown(string $source, array $options = []): string
    {
        return trim($this->renderer()->toMarkdown($source, $options));
    }

    /**
     * @param string $source
     * @param array<string, mixed> $options
     *
     * @return array<array<string, mixed>>
     */
    public function toc(string $source, array $options = []): array
    {
        return $this->renderer()->render($source, $options)->toc();
    }

    private function renderer(): CarveRendererInterface
    {
        if ($this->renderer === null) {
            $this->renderer = RendererFactory::create($this->getConfig());
        }

        return $this->renderer;
    }
}
