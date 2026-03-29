<?php

declare(strict_types=1);

namespace Marwa\Entity\Tests\Entity;

use Marwa\Entity\Entity\SchemaFactory;
use Marwa\Entity\Support\SanitizerFactory;
use Marwa\Entity\Validation\RuleFactory;
use PHPUnit\Framework\TestCase;

final class SchemaFactoryTest extends TestCase
{
    public function testFromArrayBuildsSchemaWithParameterizedSanitizers(): void
    {
        $schema = SchemaFactory::fromArray(
            [
                'name' => 'posts',
                'fields' => [
                    'title' => [
                        'type' => 'string',
                        'label' => 'Title',
                        'rules' => [
                            ['name' => 'required'],
                        ],
                        'sanitize' => [
                            'trim',
                            ['name' => 'strip_tags', 'params' => ['allowed' => ['strong']]],
                        ],
                    ],
                ],
            ],
            static fn(string $name, array $params = []) => RuleFactory::make($name, $params),
            static fn(string $name, array $params = []) => SanitizerFactory::make($name, $params),
        );

        $field = $schema->get('title');

        self::assertNotNull($field);
        self::assertSame('Title', $field->getLabel());
        self::assertCount(1, $field->getRules());
        self::assertCount(2, $field->getSanitizers());
    }

    public function testFromArrayRejectsUnsupportedFieldTypes(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported field type: uuid');

        SchemaFactory::fromArray(
            [
                'fields' => [
                    'id' => ['type' => 'uuid'],
                ],
            ],
            static fn(string $name, array $params = []) => RuleFactory::make($name, $params),
            static fn(string $name, array $params = []) => SanitizerFactory::make($name, $params),
        );
    }
}
