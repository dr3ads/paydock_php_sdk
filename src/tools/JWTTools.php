<?php

namespace Paydock\Tools;

/*
 * This file is part of the Paydock.Sdk package.
 *
 * (c) Paydock
 *
 * For the full copyright and license information, please view
 * the LICENSE file which was distributed with this source code.
 */

final class JWTTools
{
    public static function isJWTToken($input)
    {
        return (count(explode('.', $input)) == 3);
    }
}