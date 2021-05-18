<?php

declare(strict_types=1);

/*
 * This file is part of PHP CS Fixer.
 * (c) Fabien Potencier <fabien@symfony.com>
 *     Dariusz Rumiński <dariusz.ruminski@gmail.com>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Jtl\Connector\Example\Controller;

use Jtl\Connector\Core\Controller\DeleteInterface;
use Jtl\Connector\Core\Controller\PullInterface;
use Jtl\Connector\Core\Controller\PushInterface;
use Jtl\Connector\Core\Controller\StatisticInterface;
use Jtl\Connector\Core\Definition\IdentityType;
use Jtl\Connector\Core\Model\AbstractDataModel;
use Jtl\Connector\Core\Model\Category;
use Jtl\Connector\Core\Model\CategoryI18n;
use Jtl\Connector\Core\Model\Identity;
use Jtl\Connector\Core\Model\QueryFilter;
use Ramsey\Uuid\Uuid;

/**
 * Class CategoryController.
 */
class CategoryController extends AbstractController implements PullInterface, PushInterface, StatisticInterface, DeleteInterface
{
    /**
     * Deletes a category by its id.
     */
    public function delete(AbstractDataModel $model): AbstractDataModel
    {
        /** @var Category $model */
        if (!empty($categoryId = $model->getId()->getEndpoint())) {
            $statement = $this->pdo->prepare('DELETE FROM categories WHERE id = ?');
            $statement->execute([$categoryId]);
        }

        return $model;
    }

    /**
     * Inserts a new category or updates an existing one.
     */
    public function push(AbstractDataModel $model): AbstractDataModel
    {
        /** @var Category $model */
        $endpointId = $model->getId()->getEndpoint();

        if (empty($endpointId)) {
            $endpointId = Uuid::uuid4()->getHex()->toString();
            $model->getId()->setEndpoint($endpointId);
        }

        $query = 'INSERT INTO categories (id, parent_id, status) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE status = ?, parent_id = ?';

        $params = [
            $endpointId,
            $parentId = '' === $model->getParentCategoryId()->getEndpoint() ? null : $model->getParentCategoryId()->getEndpoint(),
            $status = (int) $model->getIsActive(),
            $status,
            $parentId,
        ];

        $statement = $this->pdo->prepare($query);
        $statement->execute($params);

        foreach ($model->getI18ns() as $i18n) {
            $statement = $this->pdo->prepare(
                'INSERT INTO category_translations (category_id, name, description, title_tag, meta_description, meta_keywords, language_iso) VALUES (?, ?, ?, ?, ?, ?, ?)
                           ON DUPLICATE KEY UPDATE name = ?, description = ?, title_tag = ? , meta_description = ?, meta_keywords = ?'
            );

            $statement->execute([
                $endpointId,
                $i18n->getName(),
                $i18n->getDescription(),
                $i18n->getTitleTag(),
                $i18n->getMetaDescription(),
                $i18n->getMetaKeywords(),
                $i18n->getLanguageIso(),
                $i18n->getName(),
                $i18n->getDescription(),
                $i18n->getTitleTag(),
                $i18n->getMetaDescription(),
                $i18n->getMetaKeywords(),
            ]);
        }

        return $model;
    }

    /**
     * Returns all unlinked categories.
     * {@inheritDoc}
     */
    public function pull(QueryFilter $queryFilter): array
    {
        $return = [];

        $statement = $this->pdo->prepare('
            SELECT * FROM categories c
            LEFT JOIN mapping m ON c.id = m.endpoint
            WHERE m.host IS NULL OR m.type != ?
        ');

        $statement->execute([
            IdentityType::CATEGORY,
        ]);

        $categories = $statement->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($categories as $category) {
            $return[] = $this->createJtlCategory($category);
        }

        return $return;
    }

    /**
     * Returns the number of unlinked categories.
     */
    public function statistic(QueryFilter $queryFilter): int
    {
        $statement = $this->pdo->prepare('
            SELECT * FROM categories c
            LEFT JOIN mapping m ON c.id = m.endpoint
            WHERE m.host IS NULL OR m.type != ?
        ');
        $statement->execute([
            IdentityType::CATEGORY,
        ]);

        return $statement->rowCount();
    }

    /**
     * A helper function to convert database records to models used in the connector.
     */
    protected function createJtlCategory(array $category): Category
    {
        $jtlCategory = (new Category())
            ->setId(new Identity($category['id']))
            ->setIsActive($category['status'])
            ->setParentCategoryId(new Identity($category['parent_id'] ?? ''))
        ;

        $statement = $this->pdo->prepare('
            SELECT * FROM category_translations t
            LEFT JOIN categories c ON c.id = t.category_id
            WHERE c.id = ?
        ');

        $statement->execute([$category['id']]);
        $i18ns = $statement->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($i18ns as $i18n) {
            $jtlCategory->addI18n($this->createJtlCategoryI18n($i18n));
        }

        return $jtlCategory;
    }

    /**
     * A helper function to convert database records to models used in the connector.
     */
    protected function createJtlCategoryI18n(array $i18n): CategoryI18n
    {
        return (new CategoryI18n())
            ->setName($i18n['name'])
            ->setDescription($i18n['description'] ?? '')
            ->setTitleTag($i18n['title_tag'] ?? '')
            ->setMetaDescription($i18n['meta_description'] ?? '')
            ->setMetaKeywords($i18n['meta_keywords'] ?? '')
            ->setLanguageIso($i18n['language_iso'])
        ;
    }
}
