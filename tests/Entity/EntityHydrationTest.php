<?php

declare(strict_types=1);

namespace Marwa\Entity\Tests\Entity;

use Marwa\Entity\Entity\Entity;
use Marwa\Entity\Entity\EntitySchema;
use Marwa\Entity\Support\Sanitizers;
use Marwa\Entity\Validation\Rules\Required;
use Marwa\Entity\Validation\Validator;
use PHPUnit\Framework\TestCase;

final class EntityHydrationTest extends TestCase
{
    public function testHydrateSanitizesValidatesAndCastsTypedFields(): void
    {
        $schema = EntitySchema::make('users');
        $schema->string('name')
            ->rule(new Required())
            ->sanitize(Sanitizers::trim());
        $schema->integer('age');
        $schema->boolean('is_active');
        $schema->json('preferences');

        $entity = new Entity($schema, new Validator());

        $data = $entity->hydrate([
            'name' => '  Emran  ',
            'age' => '42',
            'is_active' => 'true',
            'preferences' => '{"theme":"light"}',
        ]);

        self::assertSame('Emran', $data['name']);
        self::assertSame(42, $data['age']);
        self::assertTrue($data['is_active']);
        self::assertSame(['theme' => 'light'], $data['preferences']);
    }

    public function testHydrateRejectsInvalidTypedValueWithFieldSpecificError(): void
    {
        $schema = EntitySchema::make('users');
        $schema->integer('age');

        $entity = new Entity($schema, new Validator());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The age must be a valid integer.');

        $entity->hydrate(['age' => 'abc']);
    }
}
