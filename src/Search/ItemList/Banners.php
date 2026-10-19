<?php

namespace BannerManagerKit\Search\ItemList;

use BannerManagerKit\Entity\Banner;
use ClassKit\Search\ItemList\ListTrait;
use Pagerfanta\Adapter\DoctrineDbalAdapter;
use Concrete\Core\Search\Pagination\Pagination;
use Concrete\Core\Application\ApplicationAwareTrait;
use Concrete\Core\Search\ItemList\Database\ItemList;
use Concrete\Core\Application\ApplicationAwareInterface;

class Banners extends ItemList implements ApplicationAwareInterface
{
    use ApplicationAwareTrait;
    use ListTrait;
    use PrefetchesEntities;

    protected $autoSortColumns = [
        'b.name',
    ];

    protected function createPaginationObject()
    {
        $adapter = new DoctrineDbalAdapter(
            $this->deliverQueryObject(),
            function (\Doctrine\DBAL\Query\QueryBuilder $query) {
                $query
                    ->resetQueryParts(['groupBy', 'orderBy'])
                    ->select('count(distinct b.id)')
                    ->setMaxResults(1);
            },
        );
        $pagination = new Pagination($this, $adapter);

        return $pagination;
    }

    public function createQuery()
    {
        $this->query->select('b.id')
            ->from('bmBanners', 'b')
        ;
    }

    public function filterByName($name)
    {
        $name = (string) $name;
        if ($name !== '') {
            $this->filterBy('b.name', $name, 'like');
        }
    }

    public function getTotalResults()
    {
        $query = $this->deliverQueryObject();
        $query
            ->resetQueryParts(['groupBy', 'orderBy'])
            ->select('count(distinct b.id)')
            ->setMaxResults(1);
        $result = $query->execute()->fetchOne();

        return (int) $result;
    }

    public function executeGetResults()
    {
        return $this->prefetchEntities(Banner::class, parent::executeGetResults());
    }

    public function getResult($queryRow)
    {
        return Banner::getByID($queryRow['id']);
    }
}
