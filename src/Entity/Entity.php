<?php

declare(strict_types=1);

namespace Marwa\Entity\Entity;

use Marwa\Support\Helper;
use Marwa\Entity\Validation\Validator;

final class Entity
{
    public function __construct(
        private readonly EntitySchema $schema,
        private readonly Validator $validator,
    ) {}

    public function schema(): EntitySchema
    {
        return $this->schema;
    }

    /**
     * Sanitize, validate, then cast input according to the entity schema.
     *
     * @param array<string, mixed> $input
     * @param array<string, mixed> $ctx
     *
     * @return array<string, mixed>
     */
    public function hydrate(array $input, array $ctx = []): array
    {
        $data = [];

        foreach ($this->schema->fields() as $name => $field) {
            $value = $input[$name] ?? null;
            $data[$name] = Helper::pipe($value, $field->getSanitizers());
        }

        $errors = $this->validator->validate($this->schema, $data, array_merge($ctx, ['input' => $input]));
        if ($errors->hasAny()) {
            throw new \InvalidArgumentException(json_encode(
                $errors->all(),
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
            ));
        }

        $castErrors = [];

        foreach ($this->schema->fields() as $name => $field) {
            try {
                $data[$name] = $this->cast($data[$name] ?? null, $field);
            } catch (\InvalidArgumentException $e) {
                $castErrors[$name] = [$e->getMessage()];
            }
        }

        if ($castErrors !== []) {
            throw new \InvalidArgumentException(json_encode(
                $castErrors,
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
            ));
        }

        return $data;
    }

    private function cast(mixed $value, Field $field): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        return match ($field->getType()) {
            Types::String   => $this->castString($value, $field),
            Types::Integer  => $this->castInteger($value, $field),
            Types::Boolean  => $this->castBoolean($value, $field),
            Types::Decimal  => $this->castDecimal($value, $field),
            Types::DateTime => $this->castDateTime($value, $field),
            Types::Enum     => $value,
            Types::Json     => $this->decodeJson($value, $field),
        };
    }

    private function castString(mixed $value, Field $field): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value) || is_bool($value) || $value instanceof \Stringable) {
            return (string) $value;
        }

        throw new \InvalidArgumentException(sprintf('The %s must be stringable.', $field->name));
    }

    private function castInteger(mixed $value, Field $field): int
    {
        /** @var int|false $filtered */
        $filtered = filter_var($value, FILTER_VALIDATE_INT);

        if ($filtered === false) {
            throw new \InvalidArgumentException(sprintf('The %s must be a valid integer.', $field->name));
        }

        return $filtered;
    }

    private function castBoolean(mixed $value, Field $field): bool
    {
        $boolean = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        if ($boolean === null) {
            throw new \InvalidArgumentException(sprintf('The %s must be a valid boolean.', $field->name));
        }

        return $boolean;
    }

    private function castDecimal(mixed $value, Field $field): string
    {
        if (! is_numeric($value)) {
            throw new \InvalidArgumentException(sprintf('The %s must be a valid decimal.', $field->name));
        }

        return (string) $value;
    }

    private function castDateTime(mixed $value, Field $field): string
    {
        if (! is_string($value)) {
            throw new \InvalidArgumentException(sprintf('The %s must be a valid datetime string.', $field->name));
        }

        return $value;
    }

    /**
     * @return array<int|string, mixed>
     */
    private function decodeJson(mixed $value, Field $field): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value)) {
            throw new \InvalidArgumentException(sprintf('The %s must be a valid JSON object or array.', $field->name));
        }

        try {
            /** @var array<mixed>|null $decoded */
            $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new \InvalidArgumentException(sprintf('The %s must be valid JSON.', $field->name));
        }

        if (! is_array($decoded)) {
            throw new \InvalidArgumentException(sprintf('The %s must decode to an object or array.', $field->name));
        }

        return $decoded;
    }
}
