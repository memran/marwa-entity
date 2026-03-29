<?php

declare(strict_types=1);

namespace Marwa\Entity\Contracts;

interface TranslatorInterface
{
    /**
     * @param array<string, mixed> $vars
     */
    public function translate(string $key, array $vars = []): string;
}
