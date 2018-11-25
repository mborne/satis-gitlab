<?php

namespace MBO\RemoteGit\Gitlab;

use MBO\RemoteGit\ProjectInterface;

/**
 * Common project properties between different git project host (gitlab, github, etc.)
 * 
 * @author mborne
 */
class GitlabProject implements ProjectInterface {

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
        return $this->rawMetadata['path_with_namespace'];
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
        return $this->rawMetadata['http_url_to_repo'];
    }

    /*
     * @{inheritDoc}
     */
    public function getRawMetadata(){
        return $this->rawMetadata;
    }


}
