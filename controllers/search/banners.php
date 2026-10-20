<?php

namespace Concrete\Package\BannerManagerKit\Controller\Search;

use Concrete\Core\Search\StickyRequest;
use Concrete\Core\Controller\AbstractController;
use BannerManagerKit\Search\{Column\Set\Banners as ColumnSet, ItemList\Banners as SearchList, Result\Banners as SearchResult};

class Banners extends AbstractController
{
    private $stickyRequest;

    private $searchList;

    private $searchResult;

    protected function getSearchList()
    {
        if ($this->searchList === null) {
            $this->searchList = $this->app->make(SearchList::class, [$this->getStickyRequest()]);
        }

        return $this->searchList;
    }

    public function getStickyRequest()
    {
        if ($this->stickyRequest === null) {
            $this->stickyRequest = new StickyRequest('bm.banners');
        }

        return $this->stickyRequest;
    }

    public function getAllowedPaginationSizes()
    {
        return [
            10,
            20,
            50,
            100,
        ];
    }

    public function getDefaultPaginationSize()
    {
        $allAllowed = $this->getAllowedPaginationSizes();

        return $allAllowed[1];
    }

    public function search($reset = false)
    {
        $stickyRequest = $this->getStickyRequest();
        $searchList = $this->getSearchList();

        if ($reset) {
            $stickyRequest->resetSearchRequest();
        }

        $req = $stickyRequest->getSearchRequest();

        $columnSet = new ColumnSet();

        if (!$searchList->getActiveSortColumn()) {
            $sortColumn = $columnSet->getDefaultSortColumn();
            $searchList->sanitizedSortBy($sortColumn->getColumnKey(), $sortColumn->getColumnDefaultSortDirection());
        }

        $vnumbers = $this->app->make('helper/validation/numbers');
        $req = $stickyRequest->getSearchRequest();

        $q = isset($req['name']) ? $req['name'] : null;
        if (is_string($q) && $q !== '') {
            $searchList->filterByName($q);
        }

        $paginationSize = null;
        $q = isset($req['num_results']) ? $req['num_results'] : null;
        if ($q && $vnumbers->integer($q)) {
            $q = (int) $q;
            $paginationSizes = $this->getAllowedPaginationSizes();
            if (in_array($q, $paginationSizes, true)) {
                $paginationSize = (int) $q;
            }
        }

        if ($paginationSize === null) {
            $paginationSize = $this->getDefaultPaginationSize();
        }

        $searchList->setItemsPerPage($paginationSize);

        $this->searchResult = new SearchResult(
            $columnSet,
            $searchList,
            $this->app->make('url/manager')->resolve(['dashboard/banner_manager/banners/']),
        );
    }

    /**
     * Get the search result (once the search() method has been called).
     *
     * @return SearchResult|null
     */
    public function getSearchResultObject()
    {
        return $this->searchResult;
    }
}
