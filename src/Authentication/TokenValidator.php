<?php

declare(strict_types=1);

/*
 * This file is part of PHP CS Fixer.
 * (c) Fabien Potencier <fabien@symfony.com>
 *     Dariusz Rumiński <dariusz.ruminski@gmail.com>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Jtl\Connector\Example\Authentication;

use Exception;
use Jtl\Connector\Core\Authentication\TokenValidatorInterface;

class TokenValidator implements TokenValidatorInterface
{
    protected string $token;

    /**
     * TokenValidator constructor.
     *
     * @throws Exception
     */
    public function __construct(string $token)
    {
        if ('' === $token) {
            throw new Exception('Token can not be an empty string');
        }

        $this->token = $token;
    }

    /**
     * {@inheritDoc}
     */
    public function validate(string $token): bool
    {
        return $token === $this->token;
    }
}
