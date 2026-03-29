<?php

declare(strict_types=1);

namespace Marwa\Entity\Form;

use Marwa\Entity\Entity\EntitySchema;

final class FormBuilder
{
    public function __construct(private readonly EntitySchema $schema) {}

    /**
     * @param array<string, mixed> $values
     * @param array<string, list<string>> $errors
     *
     * @return array{fields: array<string, array<string, mixed>>, values: array<string, mixed>, errors: array<string, list<string>>}
     */
    public function context(array $values = [], array $errors = []): array
    {
        return [
            'fields' => $this->schema->uiSpec(),
            'values' => $values,
            'errors' => $errors,
        ];
    }
}
