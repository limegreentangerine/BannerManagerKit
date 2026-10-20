<?php

namespace BannerManagerKit\Search\Column\Set;

use Concrete\Core\Search\Column\{Column, Set};

class SlideTypes extends Set
{
    public function __construct()
    {
        $this->addColumn(new Column(
            'st.name',
            t('Name'),
            'getName',
            true,
        ));

        $this->addColumn(new Column(
            'st.slides',
            t('Number of Slides'),
            'getSlidesCount',
            true,
        ));

        $defaultSortColumn = $this->getColumnByKey('st.name');
        $this->setDefaultSortColumn($defaultSortColumn, 'asc');
    }
}
