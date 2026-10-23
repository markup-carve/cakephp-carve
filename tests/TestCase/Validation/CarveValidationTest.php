<?php

declare(strict_types=1);

namespace MarkupCarve\CakeCarve\Test\TestCase\Validation;

use Cake\Core\Configure;
use Cake\Validation\Validator;
use MarkupCarve\CakeCarve\Carve\CarveRenderer;
use MarkupCarve\CakeCarve\Validation\CarveValidation;
use MarkupCarve\Carve\Profile;
use PHPUnit\Framework\TestCase;

class CarveValidationTest extends TestCase
{
    public function testProviderAndLength(): void
    {
        $validator = new Validator();
        $validator->setProvider('carve', CarveValidation::class)
            ->add('body', 'carve', ['rule' => ['valid', ['minLength' => 5]], 'provider' => 'carve']);
        self::assertSame([], $validator->validate(['body' => 'Hello']));
        self::assertNotSame([], $validator->validate(['body' => 'Hi']));
    }

    public function testProfileFromCustomPresetAndInstance(): void
    {
        Configure::write('Carve.presets', ['limited' => ['profile' => 'comment']]);
        try {
            self::assertSame('Carve content contains markup disallowed by its profile.', CarveValidation::valid('# Heading', ['preset' => 'limited']));
            self::assertSame('Carve content contains markup disallowed by its profile.', CarveValidation::valid('# Heading', ['profile' => Profile::comment()]));
            self::assertSame('Carve content contains markup disallowed by its profile.', CarveValidation::valid('# Heading', ['profile' => 'comment', 'onDisallowed' => 'error']));
            Configure::write('Carve.profile', 'comment');
            self::assertSame('Carve content contains markup disallowed by its profile.', CarveValidation::valid('# Heading'));
        } finally {
            Configure::delete('Carve');
        }
    }

    public function testPresetProfileWinsOverGlobalProfile(): void
    {
        Configure::write('Carve.profile', 'full');
        try {
            self::assertSame('Carve content contains markup disallowed by its profile.', CarveValidation::valid('# Heading', ['preset' => 'comment']));
            Configure::write('Carve.profile', 'comment');
            self::assertTrue(CarveValidation::valid('# Heading', ['profile' => 'full']));
        } finally {
            Configure::delete('Carve');
        }
    }

    public function testLintMessageNamesTheFindings(): void
    {
        self::assertSame(
            'Carve content has lint findings: 1:1 unresolved-reference-link: Reference has no matching definition or heading.',
            CarveValidation::valid('[x][nope]', ['lint' => true]),
        );
        self::assertSame(
            'Carve content has lint findings: 1:1 unresolved-reference-link: Reference has no matching definition or heading; 1:8 unresolved-reference-link: Reference has no matching definition or heading.',
            CarveValidation::valid('[a][x] [b][y]', ['lint' => true]),
        );
        $message = CarveValidation::valid('[a][x] [b][y] [c][z] [d][w] [e][v]', ['lint' => true]);
        self::assertIsString($message);
        self::assertStringEndsWith('(and 2 more).', $message);
        self::assertSame(3, substr_count($message, 'unresolved-reference-link'));
    }

    public function testLintFiltersAndPlainTextBoundaries(): void
    {
        $source = '**bold**';
        $finding = (new CarveRenderer())->lint($source)[0];
        self::assertArrayNotHasKey('severity', $finding);
        self::assertSame(true, CarveValidation::valid($source, ['lint' => false]));
        self::assertNotSame(true, CarveValidation::valid($source, ['lint' => true]));
        self::assertNotSame(true, CarveValidation::valid($source, ['lint' => true, 'lintRules' => [$finding['rule']]]));
        self::assertSame(true, CarveValidation::valid($source, ['lint' => true, 'lintRules' => ['absent-rule']]));
        self::assertSame(true, CarveValidation::valid($source, ['lint' => true, 'ignoreLintRules' => [$finding['rule']]]));
        self::assertSame(true, CarveValidation::valid('*bold*', ['minLength' => 4, 'maxLength' => 4]));
        self::assertNotSame(true, CarveValidation::valid('*bold*', ['minLength' => 5]));
        self::assertNotSame(true, CarveValidation::valid('*bold*', ['maxLength' => 3]));
        self::assertSame('Carve content must be text.', CarveValidation::valid(['body']));
    }
}
