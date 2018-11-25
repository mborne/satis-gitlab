<?php

namespace MBO\RemoteGit\Github;

use MBO\RemoteGit\ProjectInterface;

/**
 * Project implementation for github
 * 
 * @author mborne
 */
class GithubProject implements ProjectInterface {

    protected $rawMetadata;

    public function __construct($rawMetadata)
    {
        $this->rawMetadata = $rawMetadata;
    }

    /*
     * @{inheritDoc}
     */
    public function getId(){
        return $this->rawMetadata['id'];
    }

    /*
     * @{inheritDoc}
     */
    public function getName(){
        return $this->rawMetadata['full_name'];
    }

    /*
     * @{inheritDoc}
     */
    public function getDefaultBranch(){
        return $this->rawMetadata['default_branch'];
    }

    /*
     * @{inheritDoc}
     */
    public function getHttpUrl(){
        return $this->rawMetadata['clone_url'];
    }

    /*
     * @{inheritDoc}
     */
    public function getRawMetadata(){
        return $this->rawMetadata;
    }


}
