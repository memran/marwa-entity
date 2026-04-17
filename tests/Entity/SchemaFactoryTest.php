<?php

declare(strict_types=1);

namespace Marwa\Entity\Tests\Entity;

use Marwa\Entity\Entity\SchemaFactory;
use Marwa\Entity\Support\SanitizerFactory;
use Marwa\Support\Validation\Contracts\RuleInterface;
use PHPUnit\Framework\TestCase;

final class SchemaFactoryTest extends TestCase
{
    public function testFromArrayBuildsSchemaWithSanitizers(): void
    {
        /** @var array<string, mixed> $sanitizerParams */
        $sanitizerParams = [];
        $schema = SchemaFactory::fromArray(
            [
                'name' => 'posts',
                'fields' => [
                    'title' => [
                        'type' => 'string',
                        'label' => 'Title',
                        'sanitize' => [
                            'trim',
                            ['name' => 'strip_tags', 'params' => ['allowed' => ['strong']]],
                        ],
                    ],
                ],
            ],
            /** @return RuleInterface */
            static function (string $name, array $params = []): RuleInterface {
                return new class ($name) implements RuleInterface {
                    public function __construct(private string $name) {}

                    public function name(): string
                    {
                        return $this->name;
                    }

                    public function validate(mixed $value, array $context): bool
                    {
                        return true;
                    }

                    public function message(string $field, array $attributes): string
                    {
                        return '';
                    }

                    public function params(): array
                    {
                        return [];
                    }
                };
            },
            /** @return callable(mixed): mixed */
            static function (string $name, array $params = []) use ($sanitizerParams): callable {
                return SanitizerFactory::make($name, $sanitizerParams);
            },
        );

        $field = $schema->get('title');

        self::assertNotNull($field);
        self::assertSame('Title', $field->getLabel());
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
            /** @return RuleInterface */
            static function (string $name, array $params = []): RuleInterface {
                throw new \RuntimeException('Should not reach');
            },
            /** @return callable(mixed): mixed */
            static function (string $name, array $params = []): callable {
                throw new \RuntimeException('Should not reach');
            },
        );
    }
}
