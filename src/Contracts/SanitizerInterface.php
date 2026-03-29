<?php

declare(strict_types=1);

namespace Marwa\Entity\Contracts;

interface SanitizerInterface
{
    public function __invoke(mixed $value): mixed;
}
