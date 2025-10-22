<?php

namespace Marwa\Entity\Validation;

use Marwa\Entity\Contracts\TranslatorInterface;
use Marwa\Entity\Entity\EntitySchema;
use Marwa\Entity\Entity\Field;
use Marwa\Entity\Validation\Rules\Required;

final class Validator
{
    /** @param TranslatorInterface|null $translator Optional translator for messages */

    public function __construct(private readonly ?TranslatorInterface $translator = null) {}

    /**
     * Validate data against schema rules
     * @param EntitySchema $schema
     * @param array $data
     * @param array $context
     * @return ErrorBag
     */
    public function validate(EntitySchema $schema, array $data, array $context = []): ErrorBag
    {
        $errors = new ErrorBag();

        foreach ($schema->fields() as $name => $field) {
            $value = $data[$name] ?? null;
            $rules = $field->getRules();

            foreach ($rules as $rule) {
                $ok = $rule->validate($value, $context);
                if (!$ok) {
                    $errors->add($name, $this->renderMessage($rule->message(), $name, $rule->params()));
                    // optional: short-circuit on first fail per field
                    break;
                }
            }
        }
        return $errors;
    }
    /** Render message with params */
    private function renderMessage(string $template, string $field, array $params): string
    {
        $base = $this->translator?->translate($template, array_merge(['field' => $field], $params))
            ?? $template;

        $repl = array_merge(['field' => $field], $params);
        foreach ($repl as $k => $v) {
            $base = str_replace(':' . $k, (string)$v, $base);
        }
        return $base;
    }
}
