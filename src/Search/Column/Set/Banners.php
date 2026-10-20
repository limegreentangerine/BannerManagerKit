<?php

namespace BannerManagerKit\Search\Column\Set;

use Concrete\Core\Search\Column\{Column, Set};

class Banners extends Set
{
    public function __construct()
    {
        $this->addColumn(new Column(
            'b.name',
            t('Name'),
            'getName',
            true,
        ));

        $this->addColumn(new Column(
            'b.dateCreated',
            t('Date Created'),
            'getDateCreatedString',
            true,
        ));

        $defaultSortColumn = $this->getColumnByKey('b.name');
        $this->setDefaultSortColumn($defaultSortColumn, 'asc');
    }
}
