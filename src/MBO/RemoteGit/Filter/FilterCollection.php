<?php

namespace MBO\RemoteGit\Filter;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

use MBO\RemoteGit\ProjectInterface;
use MBO\RemoteGit\ProjectFilterInterface;

/**
 * Compose a list of filter to simplify command line integration
 * 
 * @author mborne
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
    public function getDescription(){
        $parts = array();
        foreach ( $this->filters as $filter ){
            $parts[] = ' - '.$filter->getDescription();
        }
        return implode(PHP_EOL,$parts);
    }

    /**
     * {@inheritDoc}
     */
    public function isAccepted(ProjectInterface $project){
        foreach ( $this->filters as $filter ){
            if ( ! $filter->isAccepted($project) ){
                $this->logger->info(sprintf(
                    "[%s]Ignoring project %s (%s)",
                    $this->getFilterName($filter),
                    $project->getName(),
                    $filter->getDescription()
                ));
                return false;
            }
        }
        $this->logger->debug(sprintf(
            "[FilterCollection]keep project %s",
            $project->getName()
        ));
        return true;
    }

    /**
     * Get filter name
     *
     * @param ProjectFilterInterface $filter
     * @return string
     */
    private function getFilterName(ProjectFilterInterface $filter){
        $clazz = get_class($filter);
        $parts = explode('\\',$clazz);
        return $parts[count($parts)-1];
    }
}

