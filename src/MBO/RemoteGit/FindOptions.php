<?php

namespace MBO\RemoteGit;

use MBO\RemoteGit\Filter\FilterCollection;


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
     * Filter according to organizations
     *
     * @var string[]
     */
    private $organizations = array();

    /**
     * Filter according to user names
     *
     * @var string[]
     */
    private $users = array();

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


    /**
     * Get filter according to organizations
     *
     * @return  string[]
     */ 
    public function getOrganizations()
    {
        return $this->organizations;
    }

    /**
     * Set filter according to organizations
     *
     * @param  string[]  $organizations  Filter according to organizations
     *
     * @return  self
     */ 
    public function setOrganizations(array $organizations)
    {
        $this->organizations = $organizations;

        return $this;
    }


    /**
     * Get filter according to user names
     *
     * @return  string[]
     */ 
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * Set filter according to user names
     *
     * @param  string[]  $users  Filter according to user names
     *
     * @return  self
     */ 
    public function setUsers(array $users)
    {
        $this->users = $users;

        return $this;
    }

}