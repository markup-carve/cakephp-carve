<?php

declare(strict_types=1);

namespace MarkupCarve\CakeCarve\View;

use Cake\Core\Configure;
use Cake\Event\EventManagerInterface;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\View\View;
use MarkupCarve\CakeCarve\Carve\RendererFactory;
use Stringable;

class CarveView extends View
{
    protected string $_ext = '.crv';

    /**
     * @param \Cake\Http\ServerRequest|null $request
     * @param \Cake\Http\Response|null $response
     * @param \Cake\Event\EventManagerInterface|null $eventManager
     * @param array<string, mixed> $viewOptions
     */
    public function __construct(
        ?ServerRequest $request = null,
        ?Response $response = null,
        ?EventManagerInterface $eventManager = null,
        array $viewOptions = [],
    ) {
        $global = Configure::read('Carve');
        parent::__construct($request, $response, $eventManager, $viewOptions + (is_array($global) ? $global : []));
    }

    /**
     * @param string $templateFile
     * @param array<string, mixed> $dataForView
     */
    protected function _evaluate(string $templateFile, array $dataForView): string
    {
        $source = file_get_contents($templateFile);
        if ($source === false) {
            return '';
        }
        $renderer = RendererFactory::create($this->getConfig());
        $isLayout = $this->_currentType === static::TYPE_LAYOUT;
        $source = preg_replace_callback('/\{\{([A-Za-z_][A-Za-z0-9_.]*)\}\}/', function (array $match) use ($dataForView, $isLayout): string {
            if ($isLayout && $match[1] === 'content') {
                return $match[0];
            }
            $value = $dataForView;
            foreach (explode('.', $match[1]) as $part) {
                if (!is_array($value) || !array_key_exists($part, $value)) {
                    return $match[0];
                }
                $value = $value[$part];
            }

            return is_scalar($value) || $value instanceof Stringable ? (string)$value : $match[0];
        }, $source) ?? $source;
        if ($isLayout) {
            $parts = explode('{{content}}', $source);
            $html = implode($this->fetch('content'), array_map($renderer->toHtml(...), $parts));

            return count($parts) === 1 ? $html . $this->fetch('content') : $html;
        }
        $result = $renderer->render($source);
        $this->set('carve', ['meta' => $result->meta(), 'toc' => $result->toc()]);

        return $result->html();
    }
}
