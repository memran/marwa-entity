<?php

namespace Marwa\Entity\Entity;

use Marwa\Entity\Validation\Validator;

final class Entity
{
    public function __construct(
        private readonly EntitySchema $schema,
        private readonly Validator $validator
    ) {}

    public function schema(): EntitySchema
    {
        return $this->schema;
    }

    /** Sanitize + validate + cast */
    public function hydrate(array $input, array $ctx = []): array
    {
        $data = [];
        foreach ($this->schema->fields() as $name => $field) {
            $value = $input[$name] ?? null;
            foreach ($field->getSanitizers() as $san) {
                $value = $san($value);
            }
            $data[$name] = $this->cast($value, $field);
        }

        $errors = $this->validator->validate($this->schema, $data, $ctx);
        if ($errors->hasAny()) {
            throw new \InvalidArgumentException(json_encode($errors->all(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        }
        return $data;
    }

    private function cast(mixed $value, Field $field): mixed
    {
        if ($value === null) return null;
        return match ($field->getType()) {
            Types::String   => (string) $value,
            Types::Integer  => is_numeric($value) ? (int)$value : null,
            Types::Boolean  => filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            Types::Decimal  => is_numeric($value) ? (string)$value : null, // keep precision
            Types::DateTime => is_string($value) ? $value : null, // let app parse with timezone rules
            Types::Enum     => $value,
            Types::Json     => is_array($value) ? $value : (json_decode((string)$value, true) ?? null),
        };
    }
}
