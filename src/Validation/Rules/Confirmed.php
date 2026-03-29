<?php

declare(strict_types=1);

namespace Marwa\Entity\Validation\Rules;

use Psr\Http\Message\ServerRequestInterface;

final class Confirmed extends AbstractRule
{
    public function __construct(
        private readonly string $confirmField = '',
        string $message = 'The :field confirmation does not match.',
    ) {
        $this->message = $message;
        $this->params = ['confirm' => $confirmField];
    }

    public function name(): string
    {
        return 'confirmed';
    }

    /** @param array<string, mixed> $context */
    public function validate(mixed $value, array $context = []): bool
    {
        if (!isset($context['request']) && !isset($context['input'])) {
            return true;
        }

        $data = is_array($context['input'] ?? null) ? $context['input'] : [];
        $request = $context['request'] ?? null;

        if ($request instanceof ServerRequestInterface) {
            $data = array_merge($data, is_array($request->getParsedBody()) ? $request->getParsedBody() : []);
        }

        $field = is_string($context['field'] ?? null) ? $context['field'] : 'field';
        $confirmKey = $this->confirmField ?: "{$field}_confirmation";
        return isset($data[$confirmKey]) && $data[$confirmKey] === $value;
    }
}
