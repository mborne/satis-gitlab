<?php

namespace MBO\RemoteGit;

use \GuzzleHttp\Client as GuzzleHttpClient;
use Psr\Log\LoggerInterface;

use MBO\RemoteGit\Filter\ProjectFilterInterface;

/**
 * Find gitlab projects
 * 
 * See following gitlab docs :
 * 
 * https://docs.gitlab.com/ee/api/projects.html#list-all-projects
 * https://docs.gitlab.com/ee/api/projects.html#search-for-projects-by-name
 *  
 */
class GitlabClient implements ClientInterface {

    const DEFAULT_PER_PAGE = 50;
    const MAX_PAGES = 10000;

    /**
     * @var GuzzleHttpClient
     */
    protected $httpClient;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Constructor with an http client and a logger
     * @param $httpClient http client
     * @param $logger
     */
    public function __construct(
        GuzzleHttpClient $httpClient,
        LoggerInterface $logger
    ){
        $this->httpClient = $httpClient ;
        $this->logger = $logger ;
    }

    /*
     * @{inheritDoc}
     */    
    public function find(FindOptions $options){
        /* find all projects applying optional search */
        if ( empty($options->getUsers()) && empty($options->getOrganizations()) ){
            return $this->findBySearch($options);
        }

        $result = array();
        foreach ( $options->getUsers() as $user ){
            $result = array_merge($result,$this->findByUser(
                $user,
                $options->getFilterCollection()
            ));
        }
        foreach ( $options->getOrganizations() as $org ){
            $result = array_merge($result,$this->findByGroup(
                $org,
                $options->getFilterCollection()
            ));
        }
        return $result;
    }

    /**
     * Find projects by username
     *
     * @return void
     */
    protected function findByUser(
        $user,
        ProjectFilterInterface $projectFilter
    ){
        return $this->fetchAllPages(
            '/api/v4/users/'.urlencode($user).'/projects',
            $projectFilter
        );
    }

    /**
     * Find projects by group
     *
     * @return void
     */
    protected function findByGroup(
        $group,
        ProjectFilterInterface $projectFilter
    ){
        return $this->fetchAllPages(
            '/api/v4/groups/'.urlencode($group).'/projects',
            $projectFilter
        );
    }

    /**
     * Find all projects using option search
     */
    protected function findBySearch(FindOptions $options){
        $path = '/api/v4/projects';
        if ( $options->hasSearch() ){
            $path .= '?search='.$options->getSearch();
        }
        return $this->fetchAllPages(
            $path,
            $options->getFilterCollection()
        );
    }


    /**
     * Fetch all pages for a given path
     *
     * @param string $path "/api/v4/projects?search=something", "/api/v4/projects"
     * @param ProjectFilterInterface $projectFilter
     * @return void
     */
    private function fetchAllPages(
        $path,
        ProjectFilterInterface $projectFilter
    ){
        $result = array();
        if ( strpos($path,'?') === false ){
            $path .= '?';
        }
        for ($page = 1; $page <= self::MAX_PAGES; $page++) {
            $uri = $path.'page='.$page.'&per_page='.self::DEFAULT_PER_PAGE;
            $this->logger->debug('GET '.$uri);
            $response = $this->httpClient->get($uri);
            $rawProjects = json_decode( (string)$response->getBody(), true ) ;
            if ( empty($rawProjects) ){
                break;
            }
            foreach ( $rawProjects as $rawProject ){
                $project = new GitlabProject($rawProject);
                if ( ! $projectFilter->isAccepted($project) ){
                    continue;
                }
                $result[] = $project;
            }
        }

        return $result;
    }

    /*
     * @{inheritDoc}
     */
    public function getRawFile(
        ProjectInterface $project, 
        $filePath,
        $ref
    ){
        // ref : https://docs.gitlab.com/ee/api/repository_files.html#get-raw-file-from-repository
        $uri  = '/api/v4/projects/'.$project->getId().'/repository/files/'.urlencode($filePath).'/raw';
        $uri .= '?ref='.$ref;
        $this->logger->debug('GET '.$uri);
        $response = $this->httpClient->get($uri);
        return (string)$response->getBody();
    }


}