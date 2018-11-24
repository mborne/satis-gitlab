<?php

namespace MBO\RemoteGit;

/**
 * Common project properties between different git project host (gitlab, github, etc.)
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
     * Get host specific properties
     * @return array
     */
    public function getRawMetadata();


}
