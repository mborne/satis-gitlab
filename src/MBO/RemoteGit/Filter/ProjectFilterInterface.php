<?php

namespace MBO\RemoteGit\Filter;

use MBO\RemoteGit\ProjectInterface;


/**
 * Test if a project should be included in satis config (regexp, )
 */
interface ProjectFilterInterface {

    /**
     * Returns true if the project should be included in satis configuration
     *
     * @param ProjectInterface $project
     * @return boolean
     */
    public function isAccepted(ProjectInterface $project);

}
