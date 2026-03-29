<?php

declare(strict_types=1);

namespace Marwa\Entity\Validation;

use Marwa\Entity\Contracts\TranslatorInterface;
use Marwa\Entity\Entity\EntitySchema;
use Marwa\Entity\Validation\Rules\Nullable;

final class Validator
{
    public function __construct(private readonly ?TranslatorInterface $translator = null) {}

    /**
     * Validate input data against the entity schema.
     *
     * @param array<string, mixed> $data
     * @param array<string, mixed> $context
     */
    public function validate(EntitySchema $schema, array $data, array $context = []): ErrorBag
    {
        $errors = new ErrorBag();

        foreach ($schema->fields() as $name => $field) {
            $value = $data[$name] ?? null;
            $rules = $field->getRules();
            $ruleContext = $this->buildRuleContext($context, $data, $name);

            if ($this->shouldSkipNullableRules($rules, $value)) {
                continue;
            }

            foreach ($rules as $rule) {
                if ($rule instanceof Nullable) {
                    continue;
                }

                if (! $rule->validate($value, $ruleContext)) {
                    $errors->add($name, $this->renderMessage($rule->message(), $name, $rule->params()));
                    break;
                }
            }
        }

        return $errors;
    }

    /**
     * @param array<string, mixed> $context
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private function buildRuleContext(array $context, array $data, string $field): array
    {
        $context['data'] ??= $data;
        $context['input'] ??= $data;
        $context['field'] = $field;

        return $context;
    }

    /**
     * @param array<int, object> $rules
     */
    private function shouldSkipNullableRules(array $rules, mixed $value): bool
    {
        if ($value !== null && $value !== '') {
            return false;
        }

        foreach ($rules as $rule) {
            if ($rule instanceof Nullable) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, mixed> $params
     */
    private function renderMessage(string $template, string $field, array $params): string
    {
        $base = $this->translator?->translate($template, array_merge(['field' => $field], $params))
            ?? $template;

        $repl = array_merge(['field' => $field], $params);
        foreach ($repl as $k => $v) {
            $base = str_replace(':' . $k, $this->stringifyValue($v), $base);
        }
        return $base;
    }

    private function stringifyValue(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value) || is_bool($value) || $value instanceof \Stringable) {
            return (string) $value;
        }

        if (is_array($value)) {
            return implode(', ', array_map($this->stringifyValue(...), $value));
        }

        return '';
    }
}
