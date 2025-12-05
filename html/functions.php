<?php

/**
 * Generate a secure random token.
 *
 * @param int $length Number of hex characters (must be even).
 * @return string
 */
function random_token(int $length = 64): string
{
    // random_bytes returns raw bytes → we convert to hex
    // if $length is 64, we need 32 bytes.
    return bin2hex(random_bytes($length / 2));
}
