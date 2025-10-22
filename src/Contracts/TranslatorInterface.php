<?php
namespace Marwa\Entity\Contracts;

interface TranslatorInterface
{
    public function translate(string $key, array $vars = []): string;
}
