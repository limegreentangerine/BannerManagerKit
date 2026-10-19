<?php

namespace BannerManagerKit\Search\Column\Set;

use Concrete\Core\Search\Column\{Column, Set};

class Slides extends Set
{
    public function __construct()
    {
        $this->addColumn(new Column(
            's.name',
            t('Name'),
            'getName',
            true,
        ));

        $this->addColumn(new Column(
            's.dateCreated',
            t('Date Created'),
            'getDateCreatedString',
            true,
        ));

        $defaultSortColumn = $this->getColumnByKey('s.name');
        $this->setDefaultSortColumn($defaultSortColumn, 'asc');
    }
}
