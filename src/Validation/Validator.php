<?php

declare(strict_types=1);

namespace Marwa\Entity\Validation;

use Marwa\Entity\Entity\EntitySchema;
use Marwa\Support\Validation\Contracts\RuleInterface;
use Marwa\Support\Validation\ErrorBag;
use Marwa\Support\Validation\RequestValidator;
use Marwa\Support\Validation\ValidationException;

final class Validator
{
    private RequestValidator $supportValidator;

    public function __construct()
    {
        $this->supportValidator = new RequestValidator();
    }

    /**
     * Validate input data against the entity schema.
     *
     * @param array<string, mixed> $data
     * @param array<string, mixed> $context
     */
    public function validate(EntitySchema $schema, array $data, array $context = []): ErrorBag
    {
        $rules = $this->convertSchemaRules($schema);

        try {
            $this->supportValidator->validateInput($data, $rules);
            return new ErrorBag();
        } catch (ValidationException $e) {
            return $this->convertSupportErrors($e);
        }
    }

    /**
     * @return array<string, string|array<int, mixed>>
     */
    private function convertSchemaRules(EntitySchema $schema): array
    {
        $rules = [];

        foreach ($schema->fields() as $name => $field) {
            $fieldRules = $field->getRules();

            if ($fieldRules === []) {
                continue;
            }

            $ruleStrings = [];

            /** @var RuleInterface $rule */
            foreach ($fieldRules as $rule) {
                $ruleName = $rule->name();

                $params = $rule->params();
                if ($params !== []) {
                    $paramStrings = [];
                    foreach ($params as $v) {
                        if (is_scalar($v)) {
                            $paramStrings[] = (string) $v;
                        }
                    }
                    if (!empty($paramStrings)) {
                        $ruleStrings[] = $ruleName . ':' . implode(',', $paramStrings);
                    } else {
                        $ruleStrings[] = $ruleName;
                    }
                } else {
                    $ruleStrings[] = $ruleName;
                }
            }

            $rules[$name] = $ruleStrings;
        }

        return $rules;
    }

    private function convertSupportErrors(ValidationException $e): ErrorBag
    {
        $bag = new ErrorBag();
        $errors = $e->getErrors();

        /** @var array<string, array<string, string>> $errors */
        foreach ($errors as $field => $messages) {
            /** @var string $message */
            foreach ($messages as $message) {
                $bag->add($field, $message);
            }
        }

        return $bag;
    }
}
