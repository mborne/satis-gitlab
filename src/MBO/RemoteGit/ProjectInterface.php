<?php

namespace MBO\RemoteGit;

/**
 * Common project properties between different git project host (gitlab, github, etc.)
 * 
 * @author mborne
 */
interface ProjectInterface {

    /**
     * Get project id
     * @return string
     */
    public function getId();

    /**
     * Get project name (with namespace)
     * @return string
     */
    public function getName();

    /**
     * Get default branch
     * @return string
     */
    public function getDefaultBranch();

    /**
     * Get http url
     * @return string
     */
    public function getHttpUrl();

    /**
     * Get hosting service specific properties
     * @return array
     */
    public function getRawMetadata();


}
