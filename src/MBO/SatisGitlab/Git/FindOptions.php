<?php

namespace MBO\SatisGitlab\Git;

use MBO\SatisGitlab\Filter\FilterCollection;


/**
 * Find options to filter project listing
 */
class FindOptions {

    /**
     * Search string (prefer the use of organizations and users)
     *
     * @var string
     */
    private $search ;

    /**
     * Filters not appliable throw API usage
     *
     * @var FilterCollection
     */
    private $filterCollection ;

    public function __construct(){
        $this->filterCollection = new FilterCollection();
    }

    /**
     * True if search is defined
     *
     * @return boolean
     */
    public function hasSearch(){
        return ! empty($this->search);
    }

    /**
     * Get search string (prefer the use of organizations and users)
     *
     * @return  string
     */ 
    public function getSearch()
    {
        return $this->search;
    }

    /**
     * Set search string (prefer the use of organizations and users)
     *
     * @param  string  $search  Search string (prefer the use of organizations and users)
     *
     * @return  self
     */ 
    public function setSearch(string $search)
    {
        $this->search = $search;

        return $this;
    }



    /**
     * Get filters not appliable throw API usage
     *
     * @return  FilterCollection
     */ 
    public function getFilterCollection()
    {
        return $this->filterCollection;
    }

    /**
     * Set filters not appliable throw API usage
     *
     * @param  FilterCollection  $filterCollection  Filters not appliable throw API usage
     *
     * @return  self
     */ 
    public function setFilterCollection(FilterCollection $filterCollection)
    {
        $this->filterCollection = $filterCollection;

        return $this;
    }
}