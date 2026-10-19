<?php

namespace BannerManagerKit\Search\ItemList;

use BannerManagerKit\Entity\Slide;
use ClassKit\Search\ItemList\ListTrait;
use Pagerfanta\Adapter\DoctrineDbalAdapter;
use Concrete\Core\Search\Pagination\Pagination;
use Concrete\Core\Application\ApplicationAwareTrait;
use Concrete\Core\Search\ItemList\Database\ItemList;
use Concrete\Core\Application\ApplicationAwareInterface;

class Slides extends ItemList implements ApplicationAwareInterface
{
    use ApplicationAwareTrait;
    use ListTrait;
    use PrefetchesEntities;

    protected $autoSortColumns = [
        's.name',
    ];

    protected function createPaginationObject()
    {
        $adapter = new DoctrineDbalAdapter(
            $this->deliverQueryObject(),
            function (\Doctrine\DBAL\Query\QueryBuilder $query) {
                $query
                    ->resetQueryParts(['groupBy', 'orderBy'])
                    ->select('count(distinct s.id)')
                    ->setMaxResults(1);
            },
        );
        $pagination = new Pagination($this, $adapter);

        return $pagination;
    }

    public function createQuery()
    {
        $this->query->select('s.id')
            ->from('bmSlides', 's')
        ;
    }

    public function filterByName($name)
    {
        $name = (string) $name;
        if ($name !== '') {
            $this->filterBy('s.name', $name, 'like');
        }
    }

    public function getTotalResults()
    {
        $query = $this->deliverQueryObject();
        $query
            ->resetQueryParts(['groupBy', 'orderBy'])
            ->select('count(distinct s.id)')
            ->setMaxResults(1);
        $result = $query->execute()->fetchOne();

        return (int) $result;
    }

    public function executeGetResults()
    {
        return $this->prefetchEntities(Slide::class, parent::executeGetResults());
    }

    public function getResult($queryRow)
    {
        return Slide::getByID($queryRow['id']);
    }
}
