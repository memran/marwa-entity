<?php

declare(strict_types=1);

namespace Marwa\Entity\Entity;

use Marwa\Support\Json;
use Marwa\Support\Str;

final class SchemaFactory
{
    /**
     * @param array<string, mixed> $def
     */
    public static function fromArray(array $def, callable $ruleResolver, callable $sanitizerResolver): EntitySchema
    {
        $schema = EntitySchema::make(isset($def['name']) ? self::stringify($def['name'], 'Schema name') : null);
        $fields = $def['fields'] ?? null;

        if (! is_array($fields)) {
            throw new \InvalidArgumentException('Schema definition must contain a fields array.');
        }

        foreach ($fields as $name => $cfg) {
            if (! is_array($cfg)) {
                throw new \InvalidArgumentException(sprintf('Field definition for %s must be an array.', self::stringifyKey($name)));
            }

            $field = Field::make(self::stringifyKey($name))->type(self::resolveType($cfg['type'] ?? 'string'));

            if (isset($cfg['label']) && is_string($cfg['label']) && $cfg['label'] !== '') {
                $field->label($cfg['label']);
            }

            if (isset($cfg['enum']) && is_array($cfg['enum'])) {
                /** @var list<string> $enumValues */
                $enumValues = array_values(array_filter($cfg['enum'], is_string(...)));
                $field->enum($enumValues);
            }

            foreach ((array) ($cfg['meta'] ?? []) as $key => $value) {
                $field->meta((string) $key, $value);
            }

            $rules = [];

            foreach ((array) ($cfg['rules'] ?? []) as $rule) {
                if (! is_array($rule) || ! isset($rule['name'])) {
                    throw new \InvalidArgumentException(sprintf('Invalid rule definition for field %s.', self::stringifyKey($name)));
                }

                /** @var array<string, mixed> $ruleParams */
                $ruleParams = $rule['params'] ?? [];
                $resolved = $ruleResolver(self::stringify($rule['name'], 'Rule name'), $ruleParams);
                if (! $resolved instanceof \Marwa\Support\Validation\Contracts\RuleInterface) {
                    throw new \InvalidArgumentException(sprintf('Rule resolver must return RuleInterface for field %s.', self::stringifyKey($name)));
                }
                $rules[] = $resolved;
            }

            if ($rules !== []) {
                $field->rule(...$rules);
            }

            $sanitizers = [];

            foreach ((array) ($cfg['sanitize'] ?? []) as $sanitizer) {
                if (is_array($sanitizer)) {
                    /** @var array<string, mixed> $sanitizerParams */
                    $sanitizerParams = $sanitizer['params'] ?? [];
                    $resolved = $sanitizerResolver(
                        self::stringify($sanitizer['name'] ?? '', 'Sanitizer name'),
                        $sanitizerParams,
                    );
                    if (! is_callable($resolved)) {
                        throw new \InvalidArgumentException(sprintf('Sanitizer resolver must return callable for field %s.', self::stringifyKey($name)));
                    }
                    $sanitizers[] = $resolved;
                    continue;
                }

                $resolved = $sanitizerResolver(self::stringify($sanitizer, 'Sanitizer name'));
                if (! is_callable($resolved)) {
                    throw new \InvalidArgumentException(sprintf('Sanitizer resolver must return callable for field %s.', self::stringifyKey($name)));
                }
                $sanitizers[] = $resolved;
            }

            if ($sanitizers !== []) {
                $field->sanitize(...$sanitizers);
            }

            $schema->field($field);
        }

        return $schema;
    }

    public static function fromYaml(
        string $yaml,
        callable $yamlDecoder,
        callable $ruleResolver,
        callable $sanitizerResolver,
    ): EntitySchema {
        /** @var array<string, mixed> $array */
        $array = $yamlDecoder($yaml);

        return self::fromArray($array, $ruleResolver, $sanitizerResolver);
    }

    public static function fromJson(string $json, callable $ruleResolver, callable $sanitizerResolver): EntitySchema
    {
        /** @var array<string, mixed> $array */
        $array = Json::decode($json);

        return self::fromArray($array, $ruleResolver, $sanitizerResolver);
    }

    private static function resolveType(mixed $type): Types
    {
        $resolved = self::stringify($type, 'Field type');

        return match (Str::lower($resolved)) {
            'string' => Types::String,
            'integer' => Types::Integer,
            'boolean' => Types::Boolean,
            'decimal' => Types::Decimal,
            'datetime' => Types::DateTime,
            'json' => Types::Json,
            'enum' => Types::Enum,
            default => throw new \InvalidArgumentException(sprintf('Unsupported field type: %s', $resolved)),
        };
    }

    private static function stringify(mixed $value, string $label): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value) || is_bool($value) || $value instanceof \Stringable) {
            return (string) $value;
        }

        throw new \InvalidArgumentException(sprintf('%s must be a string-compatible value.', $label));
    }

    private static function stringifyKey(int|string $value): string
    {
        return (string) $value;
    }
}
