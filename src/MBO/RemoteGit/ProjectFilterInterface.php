<?php

namespace MBO\RemoteGit;

/**
 * Test if a project should be included in satis config (regexp, )
 * 
 * @author mborne
 */
interface ProjectFilterInterface {

    /**
     * Get filter description (ex : "Project should contains a composer.json file")
     *
     * @return string
     */
    public function getDescription();

    /**
     * Returns true if the project should be included in satis configuration
     *
     * @param ProjectInterface $project
     * @return boolean
     */
    public function isAccepted(ProjectInterface $project);

}
