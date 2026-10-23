<?php

declare(strict_types=1);

namespace MarkupCarve\CakeCarve\Validation;

use InvalidArgumentException;
use MarkupCarve\CakeCarve\Carve\RendererFactory;
use MarkupCarve\Carve\CarveConverter;
use MarkupCarve\Carve\Exception\ProfileViolationException;
use MarkupCarve\Carve\Profile;
use Throwable;
use function Cake\I18n\__d;

final class CarveValidation
{
    /**
     * @param mixed $check
     * @param array<string, mixed> $options
     * @param array<string, mixed>|null $context
     */
    public static function valid(mixed $check, array $options = [], ?array $context = null): string|bool
    {
        if ($check === null || $check === '') {
            return true;
        }
        if (!is_string($check)) {
            return __d('carve', 'Carve content must be text.');
        }
        try {
            $renderer = RendererFactory::create(['warnings' => true]);
            $renderOptions = array_intersect_key($options, array_flip(['preset', 'profile', 'onDisallowed', 'strict']));
            try {
                $renderer->render($check, $renderOptions);
            } catch (ProfileViolationException) {
                return __d('carve', 'Carve content contains markup disallowed by its profile.');
            }
            $profileOption = $renderer->resolveOptions($renderOptions)['profile'] ?? null;
            if ($profileOption instanceof Profile || is_string($profileOption)) {
                $profile = $profileOption instanceof Profile ? clone $profileOption : self::profile($profileOption);
                $profileRenderer = new CarveConverter(profile: $profile);
                $profileRenderer->parse($check);
                if ($profileRenderer->hasProfileViolations()) {
                    return __d('carve', 'Carve content contains markup disallowed by its profile.');
                }
            }
            $lint = $options['lint'] ?? false;
            if (!is_bool($lint)) {
                throw new InvalidArgumentException('Carve lint must be a boolean.');
            }
            foreach (['lintRules', 'ignoreLintRules'] as $key) {
                if (isset($options[$key]) && (!is_array($options[$key]) || array_filter($options[$key], 'is_string') !== $options[$key])) {
                    throw new InvalidArgumentException('Invalid Carve ' . $key . '.');
                }
            }
            if ($lint) {
                $allowed = $options['lintRules'] ?? null;
                $allowed = is_array($allowed) ? $allowed : null;
                $ignored = $options['ignoreLintRules'] ?? [];
                $ignored = is_array($ignored) ? $ignored : [];
                foreach ($renderer->lint($check) as $finding) {
                    if (
                        ($allowed === null || in_array($finding['rule'], $allowed, true))
                        && !in_array($finding['rule'], $ignored, true)
                    ) {
                        return __d('carve', 'Carve content has lint findings.');
                    }
                }
            }
            $length = mb_strlen(trim($renderer->toText($check, $renderOptions)));
            if (isset($options['maxLength']) && $length > $options['maxLength']) {
                return __d('carve', 'Carve content is too long.');
            }
            if (isset($options['minLength']) && $length < $options['minLength']) {
                return __d('carve', 'Carve content is too short.');
            }
        } catch (InvalidArgumentException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            return __d('carve', 'Invalid Carve content: {0}', $exception->getMessage());
        }

        return true;
    }

    private static function profile(string $name): Profile
    {
        if (!in_array($name, ['full', 'article', 'comment', 'minimal'], true)) {
            throw new InvalidArgumentException('Unknown Carve profile: ' . $name);
        }

        return Profile::{$name}();
    }
}
