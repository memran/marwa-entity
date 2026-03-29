<?php

declare(strict_types=1);

namespace Marwa\Entity\Support;

/**
 * Class Sanitizers
 * @package Marwa\Entity\Support
 */
final class Sanitizers
{
    /**
     * * Trim whitespace from the beginning and end of a string
     * @return \Closure
     * */
    public static function trim(): \Closure
    {
        return static fn($v) => is_string($v) ? trim($v) : $v;
    }
    /**
     * * Convert string to lowercase
     * @return \Closure
     * */
    public static function lower(): \Closure
    {
        return static fn($v) => is_string($v) ? mb_strtolower($v) : $v;
    }
    /**
     * * Strip HTML and PHP tags from a string
     * @param list<string> $allowed
     * @return \Closure
     * */
    public static function stripTags(array $allowed = []): \Closure
    {
        $allow = $allowed;
        return static fn($v) => is_string($v) ? strip_tags($v, implode('', array_map(fn($t) => "<$t>", $allow))) : $v;
    }
}
