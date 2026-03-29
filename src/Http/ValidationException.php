<?php

declare(strict_types=1);

namespace Marwa\Entity\Http;

use Marwa\Entity\Validation\ErrorBag;

final class ValidationException extends \RuntimeException
{
    public function __construct(
        private readonly ErrorBag $errors,
        string $message = 'The given data was invalid.',
        int $code = 422,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function errors(): ErrorBag
    {
        return $this->errors;
    }
}
