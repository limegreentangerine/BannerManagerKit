<?php

namespace BannerManagerKit\Search\ItemList;

use Doctrine\ORM\EntityManagerInterface;

trait PrefetchesEntities
{
    /**
     * Load every row's entity in one query so the per-row getByID() calls in
     * getResult() are answered from Doctrine's identity map rather than
     * costing a query each.
     */
    protected function prefetchEntities(string $entityClass, array $rows): array
    {
        $ids = array_map('strval', array_column($rows, 'id'));

        if ($ids) {
            app(EntityManagerInterface::class)->createQueryBuilder()
                ->select('e')
                ->from($entityClass, 'e')
                ->where('e.id IN (:ids)')
                ->setParameter('ids', $ids)
                ->getQuery()
                ->getResult();
        }

        return $rows;
    }
}
