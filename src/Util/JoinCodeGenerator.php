<?php

declare(strict_types=1);

namespace App\Util;

/**
 * Generates unique six-character class join codes.
 *
 * @author Jericho
 * @since 2026-05-17
 * @package App\Util
 */
final class JoinCodeGenerator
{
    /** @var string Characters used in join codes (no ambiguous I/O/0/1) */
    private const CHARSET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    /** @var int Length of generated codes */
    private const CODE_LENGTH = 6;

    /**
     * @return string Six-character uppercase code
     */
    public function generate(): string
    {
        $chars = self::CHARSET;
        $maxIndex = strlen($chars) - 1;
        $code = '';

        for ($i = 0; $i < self::CODE_LENGTH; $i++) {
            $code .= $chars[random_int(0, $maxIndex)];
        }

        return $code;
    }
}
