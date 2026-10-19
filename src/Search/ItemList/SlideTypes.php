<?php

namespace BannerManagerKit\Search\ItemList;

use BannerManagerKit\Entity\SlideType;
use ClassKit\Search\ItemList\ListTrait;
use Pagerfanta\Adapter\DoctrineDbalAdapter;
use Concrete\Core\Search\Pagination\Pagination;
use Concrete\Core\Application\ApplicationAwareTrait;
use Concrete\Core\Search\ItemList\Database\ItemList;
use Concrete\Core\Application\ApplicationAwareInterface;

class SlideTypes extends ItemList implements ApplicationAwareInterface
{
    use ApplicationAwareTrait;
    use ListTrait;
    use PrefetchesEntities;

    protected $autoSortColumns = [
        'st.name',
    ];

    protected function createPaginationObject()
    {
        $adapter = new DoctrineDbalAdapter(
            $this->deliverQueryObject(),
            function (\Doctrine\DBAL\Query\QueryBuilder $query) {
                $query
                    ->resetQueryParts(['groupBy', 'orderBy'])
                    ->select('count(distinct st.id)')
                    ->setMaxResults(1);
            },
        );
        $pagination = new Pagination($this, $adapter);

        return $pagination;
    }

    public function createQuery()
    {
        $this->query->select('st.id')
            ->from('bmSlideTypes', 'st')
        ;
    }

    public function filterByName($name)
    {
        $name = (string) $name;
        if ($name !== '') {
            $this->filterBy('st.name', $name, 'like');
        }
    }

    public function getTotalResults()
    {
        $query = $this->deliverQueryObject();
        $query
            ->resetQueryParts(['groupBy', 'orderBy'])
            ->select('count(distinct st.id)')
            ->setMaxResults(1);
        $result = $query->execute()->fetchOne();

        return (int) $result;
    }

    public function executeGetResults()
    {
        return $this->prefetchEntities(SlideType::class, parent::executeGetResults());
    }

    public function getResult($queryRow)
    {
        return SlideType::getByID($queryRow['id']);
    }
}
