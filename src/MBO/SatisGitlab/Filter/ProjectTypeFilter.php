<?php

namespace MBO\SatisGitlab\Filter;

use MBO\SatisGitlab\Git\ProjectInterface;
use MBO\SatisGitlab\Git\ClientInterface as GitClientInterface;
use Psr\Log\LoggerInterface;

/**
 * Ignore project according to their type
 */
class ProjectTypeFilter implements ProjectFilterInterface {
    /**
     * @var string
     */
    protected $projectType;
    
    /**
     * @var GitClientInterface
     */
    protected $gitClient;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * ProjectTypeFilter constructor.
     *
     * @param string $type
     * @param GitClientInterface $gitClient
     * @param LoggerInterface $logger
     */
    public function __construct($type, GitClientInterface $gitClient, LoggerInterface $logger)
    {
        assert(!empty($type));        
        $this->projectType = $type;
        $this->gitClient = $gitClient;
        $this->logger = $logger;
    }
    
    /**
     * {@inheritDoc}
     */
    public function isAccepted(ProjectInterface $project)
    {
        try {
            $json = $this->gitClient->getRawFile(
                $project,
                'composer.json',
                $project->getDefaultBranch()
            );
            $composer = json_decode($json, true);
            return isset($composer['type']) && strtolower($composer['type']) === strtolower($this->projectType);
        }catch(\Exception $e){
            $this->logger->debug(sprintf(
                '%s (branch %s) : file %s not found',
                $project->getName(),
                $project->getDefaultBranch(),
                'composer.json'
            ));
            return false;
        }
    }
}
