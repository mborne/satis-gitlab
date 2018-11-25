<?php

namespace MBO\RemoteGit;

/**
 * Lightweight client interface to list hosted git project 
 * and access files such as composer.json
 * 
 * @author mborne
 */
interface ClientInterface {

    /**
     * Find projects throw API
     * 
     * @return ProjectInterface[]
     */
    public function find(FindOptions $options);

    /**
     * Get raw file
     * 
     * @param string $projectId ex : 123456
     * @param string $filePath ex : composer.json
     * @param string $ref ex : master
     * 
     * @return string
     */
    public function getRawFile(
        ProjectInterface $project, 
        $filePath,
        $ref
    );


}

