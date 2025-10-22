<?php
namespace Marwa\Entity\Contracts;

interface SanitizerInterface
{
    public function __invoke(mixed $value): mixed;
}
