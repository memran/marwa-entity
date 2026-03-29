<?php

declare(strict_types=1);

namespace Marwa\Entity\Entity;

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

            if (isset($cfg['label']) && $cfg['label'] !== '') {
                $field->label((string) $cfg['label']);
            }

            if (isset($cfg['enum']) && is_array($cfg['enum'])) {
                $field->enum($cfg['enum']);
            }

            foreach ((array) ($cfg['meta'] ?? []) as $key => $value) {
                $field->meta((string) $key, $value);
            }

            $rules = [];

            foreach ((array) ($cfg['rules'] ?? []) as $rule) {
                if (! is_array($rule) || ! isset($rule['name'])) {
                    throw new \InvalidArgumentException(sprintf('Invalid rule definition for field %s.', self::stringifyKey($name)));
                }

                $rules[] = $ruleResolver(self::stringify($rule['name'], 'Rule name'), (array) ($rule['params'] ?? []));
            }

            if ($rules !== []) {
                $field->rule(...$rules);
            }

            $sanitizers = [];

            foreach ((array) ($cfg['sanitize'] ?? []) as $sanitizer) {
                if (is_array($sanitizer)) {
                    $sanitizers[] = $sanitizerResolver(
                        self::stringify($sanitizer['name'] ?? '', 'Sanitizer name'),
                        (array) ($sanitizer['params'] ?? []),
                    );
                    continue;
                }

                $sanitizers[] = $sanitizerResolver(self::stringify($sanitizer, 'Sanitizer name'));
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
        $array = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        return self::fromArray($array, $ruleResolver, $sanitizerResolver);
    }

    private static function resolveType(mixed $type): Types
    {
        $resolved = self::stringify($type, 'Field type');

        return match (strtolower($resolved)) {
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
