<?php

declare(strict_types=1);

namespace App\Helper;

final class SensitiveValueMasker
{
    private const KEEP_EDGES_LENGTH = 4;

    public static function maskKeepingEdges(?string $value, int $keepEdgesLength = self::KEEP_EDGES_LENGTH): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($keepEdgesLength < 0) {
            $keepEdgesLength *= -1;
        }

        $valueLength = strlen($value);
        if ($valueLength <= $keepEdgesLength * 2) {
            return str_repeat('*', $valueLength);
        }

        return sprintf(
            '%s%s%s',
            substr($value, 0, $keepEdgesLength),
            str_repeat('*', $valueLength - ($keepEdgesLength * 2)),
            substr($value, -$keepEdgesLength),
        );
    }
}
