<?php

declare(strict_types=1);

/*
 * This file is part of PHP CS Fixer.
 * (c) Fabien Potencier <fabien@symfony.com>
 *     Dariusz Rumiński <dariusz.ruminski@gmail.com>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Jtl\Connector\Example\Mapper;

use Jtl\Connector\Core\Mapper\PrimaryKeyMapperInterface;
use PDO;

class PrimaryKeyMapper implements PrimaryKeyMapperInterface
{
    protected PDO $pdo;

    /**
     * PrimaryKeyMapper constructor.
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Returns the corresponding hostId to a endpointId and type.
     * {@inheritDoc}
     */
    public function getHostId(int $type, string $endpointId): ?int
    {
        $statement = $this->pdo->prepare('SELECT host FROM mapping WHERE endpoint = ? AND type = ?');
        $statement->execute([$endpointId, $type]);

        return $statement->fetch();
    }

    /**
     * Returns the corresponding endpointId to a hostId and type.
     * {@inheritDoc}
     */
    public function getEndpointId(int $type, int $hostId): ?string
    {
        $statement = $this->pdo->prepare('SELECT endpoint FROM mapping WHERE host = ? AND type = ?');
        $statement->execute([$hostId, $type]);

        return $statement->fetch()['endpoint'];
    }

    /**
     * Saves one specific linking.
     * {@inheritDoc}
     */
    public function save(int $type, string $endpointId, int $hostId): bool
    {
        $statement = $this->pdo->prepare('INSERT INTO mapping (endpoint, host, type) VALUES (?, ?, ?)');

        return $statement->execute([$endpointId, $hostId, $type]);
    }

    /**
     * Deletes a specific linking.
     * {@inheritDoc}
     */
    public function delete(int $type, string $endpointId = null, int $hostId = null): bool
    {
        $where = [
            'type = ?',
        ];
        $params = [
            $type,
        ];

        if (null !== $endpointId) {
            $where[] = 'endpoint = ?';
            $params[] = $endpointId;
        }

        if (null !== $hostId) {
            $where[] = 'host = ?';
            $params[] = $hostId;
        }

        $statement = $this->pdo->prepare(sprintf('DELETE IGNORE FROM mapping WHERE %s', implode(' AND ', $where)));

        return $statement->execute($params);
    }

    /**
     * Clears either the whole mapping table or all entries of a certain type.
     * {@inheritDoc}
     */
    public function clear(int $type = null): bool
    {
        if (null !== $type) {
            return $this->delete($type);
        }

        $statement = $this->pdo->prepare('DELETE FROM mapping');
        $statement->execute();

        return $statement->fetch();
    }
}
