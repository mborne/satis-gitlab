<?php

namespace MBO\SatisGitlab\Git;

use \GuzzleHttp\Client as GuzzleHttpClient;
use Psr\Log\LoggerInterface;

/**
 * Find gitlab projects
 * 
 * TODO add ClientInterface and RepositoryInterface to allow github, gogs and local repositories & co?
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
        /*
         * refs : 
         * https://docs.gitlab.com/ee/api/projects.html#list-all-projects
         * https://docs.gitlab.com/ee/api/projects.html#search-for-projects-by-name
         */
        $result = array();
        
        for ($page = 1; $page <= self::MAX_PAGES; $page++) {
            $uri = '/api/v4/projects?page='.$page.'&per_page='.self::DEFAULT_PER_PAGE;
            if ( $options->hasSearch() ){
                $uri .= '&search='.$options->getSearch();
            }
            $this->logger->debug('GET '.$uri);
            $response = $this->httpClient->get($uri);
            $rawProjects = json_decode( (string)$response->getBody(), true ) ;
            if ( empty($rawProjects) ){
                break;
            }
            foreach ( $rawProjects as $rawProject ){
                $project = new GitlabProject($rawProject);
                if ( ! $options->getFilterCollection()->isAccepted($project) ){
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