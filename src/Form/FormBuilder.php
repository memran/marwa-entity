<?php
namespace Marwa\Entity\Form;

use Marwa\Entity\Entity\EntitySchema;
use Marwa\Entity\Validation\ErrorBag;

final class FormBuilder
{
    public function __construct(private readonly EntitySchema $schema) {}

    public function context(array $values = [], array $errors = []): array
    {
        return [
            'fields' => $this->schema->uiSpec(),
            'values' => $values,
            'errors' => $errors
        ];
    }
}
