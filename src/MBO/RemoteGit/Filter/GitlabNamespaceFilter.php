<?php

namespace MBO\RemoteGit\Filter;

use MBO\RemoteGit\ProjectInterface;
use MBO\RemoteGit\ClientInterface as GitClientInterface;
use Psr\Log\LoggerInterface;

/**
 * Filter projects based on GitLab project namespace name or id.
 */
class GitlabNamespaceFilter implements ProjectFilterInterface {
    /**
     * @var string[]
     */
    protected $groups;

    /**
     * @var GitClientInterface
     */
    protected $gitClient;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * GitlabNamespaceFilter constructor.
     *
     * @param string $type
     * @param GitClientInterface $gitClient
     * @param LoggerInterface $logger
     */
    public function __construct($groups)
    {
        assert(!empty($groups));
        $this->groups = explode(',',strtolower($groups));
    }

    /**
     * {@inheritDoc}
     */
    public function isAccepted(ProjectInterface $project)
    {
        $project_info = $project->getRawMetadata();
        if (isset($project_info['namespace'])) {
            
            // Extra data from namespace to patch on.
            $valid_keys = [
                'name' => 'name',
                'id' => 'id',
            ];
            $namespace_info = array_intersect_key($project_info['namespace'], $valid_keys);
            $namespace_info = array_map('strtolower', $namespace_info);
            
            if (!empty($namespace_info) && !empty(array_intersect($namespace_info, $this->groups))) {
                // Accept any package with a permitted namespace name or id.
                return TRUE;
            }
        }
        return FALSE;
    }
}
