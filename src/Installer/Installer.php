<?php

declare(strict_types=1);

/*
 * This file is part of PHP CS Fixer.
 * (c) Fabien Potencier <fabien@symfony.com>
 *     Dariusz Rumiński <dariusz.ruminski@gmail.com>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Jtl\Connector\Example\Installer;

use PDO;

class Installer
{
    protected PDO $pdo;

    protected string $connectorDir;

    /**
     * Installer constructor.
     */
    public function __construct(PDO $pdo, string $connectorDir)
    {
        $this->pdo = $pdo;
        $this->connectorDir = $connectorDir;
    }

    /**
     * Getting and executing all install scripts, to setup the needed connector mapping tables as well as demo shop tables.
     */
    public function run(): void
    {
        $scripts = glob(sprintf('%s/scripts/*.sql', $this->connectorDir));

        foreach ($scripts as $script) {
            $statement = $this->pdo->prepare(file_get_contents($script));
            $statement->execute();
        }
    }
}
