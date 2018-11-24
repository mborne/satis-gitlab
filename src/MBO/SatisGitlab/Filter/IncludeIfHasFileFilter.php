<?php

namespace MBO\SatisGitlab\Filter;

use MBO\SatisGitlab\Git\ProjectInterface;
use MBO\SatisGitlab\Git\ClientInterface as GitClientInterface;
use Psr\Log\LoggerInterface;

/**
 * Accept projects if git contains a given file
 * 
 * TODO remove (done at git listing level)
 */
class IncludeIfHasFileFilter implements ProjectFilterInterface {

    /**
     * @var GitClientInterface
     */
    protected $gitClient;

    /**
     * @var string
     */
    protected $filePath;

    public function __construct(
        GitClientInterface $gitClient,
        $filePath,
        LoggerInterface $logger
    )
    {
        $this->gitClient = $gitClient;
        $this->filePath  = $filePath;
        $this->logger    = $logger;
    }

    /**
     * {@inheritDoc}
     */
    public function isAccepted(ProjectInterface $project)
    {
        try {
            $this->gitClient->getRawFile(
                $project,
                $this->filePath,
                $project->getDefaultBranch()
            );
            return true;
        }catch(\Exception $e){
            $this->logger->debug(sprintf(
                '%s (branch %s) : file %s not found',
                $project->getName(),
                $project->getDefaultBranch(),
                $this->filePath
            ));
            return false;
        }
    }

}
