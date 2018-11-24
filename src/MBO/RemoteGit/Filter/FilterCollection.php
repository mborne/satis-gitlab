<?php

namespace MBO\RemoteGit\Filter;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use MBO\RemoteGit\ProjectInterface;

/**
 * Compose a list of filter to simplify command line integration
 */
class FilterCollection implements ProjectFilterInterface {
    
    /**
     * @var ProjectFilterInterface[]
     */
    private $filters;
    
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger = null ){
        $this->filters = array();
        $this->logger = is_null($logger) ? new NullLogger() : $logger;
    }

    /**
     * Add a filter to the collection
     *
     * @param ProjectFilterInterface $filter
     * @return void
     */
    public function addFilter(ProjectFilterInterface $filter){
        $this->filters[] = $filter;
    }

    /**
     * {@inheritDoc}
     */
    public function isAccepted(ProjectInterface $project){
        foreach ( $this->filters as $filter ){
            if ( ! $filter->isAccepted($project) ){
                $this->logger->info(sprintf(
                    "[%s]Ignoring project %s",
                    get_class($filter),
                    $project->getName()
                ));
                return false;
            }
        }
        $this->logger->info(sprintf(
            "[FilterCollection]keep project %s",
            $project->getName()
        ));
        return true;
    }
}

