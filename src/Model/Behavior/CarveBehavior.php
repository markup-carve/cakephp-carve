<?php

declare(strict_types=1);

namespace MarkupCarve\CakeCarve\Model\Behavior;

use ArrayObject;
use Cake\Event\EventInterface;
use Cake\ORM\Behavior;
use Cake\ORM\Entity;
use MarkupCarve\CakeCarve\Carve\RendererFactory;

class CarveBehavior extends Behavior
{
    /**
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = ['fields' => []];

    /**
     * @param \Cake\Event\EventInterface<\Cake\ORM\Table> $event
     * @param \Cake\ORM\Entity $entity
     * @param \ArrayObject<string, mixed> $options
     */
    public function beforeSave(EventInterface $event, Entity $entity, ArrayObject $options): void
    {
        $fields = $this->getConfig('fields');
        if (!is_array($fields)) {
            return;
        }
        $renderer = RendererFactory::create(['rendererClass' => $this->getConfig('rendererClass')]);
        foreach ($fields as $source => $spec) {
            if (!is_string($source) || (!is_string($spec) && !is_array($spec))) {
                continue;
            }
            if (!$entity->has($source)) {
                continue;
            }
            $target = is_string($spec) ? $spec : ($spec['target'] ?? $source . '_html');
            if (!is_string($target)) {
                continue;
            }
            $fieldOptions = is_array($spec) && isset($spec['options']) && is_array($spec['options']) ? $spec['options'] : [];
            if (is_array($spec) && isset($spec['preset']) && is_string($spec['preset'])) {
                $fieldOptions['preset'] = $spec['preset'];
            }
            if (!$entity->isDirty($source) && !$entity->isDirty($target) && $entity->get($target) !== null && $entity->get($target) !== '') {
                continue;
            }
            $body = $entity->get($source);
            if ($body === null || $body === '') {
                $entity->set($target, $body);
                if (is_array($spec) && isset($spec['toc']) && is_string($spec['toc'])) {
                    $entity->set($spec['toc'], []);
                }
                if (is_array($spec) && isset($spec['text']) && is_string($spec['text'])) {
                    $entity->set($spec['text'], $body);
                }

                continue;
            }
            if (!is_string($body)) {
                continue;
            }
            $result = $renderer->render($body, $fieldOptions);
            $entity->set($target, $result->html());
            if (is_array($spec) && isset($spec['toc']) && is_string($spec['toc'])) {
                $entity->set($spec['toc'], $result->toc());
            }
            if (is_array($spec) && isset($spec['text']) && is_string($spec['text'])) {
                $entity->set($spec['text'], $renderer->toText($body, $fieldOptions));
            }
        }
    }
}
