<?php

namespace MBO\RemoteGit\Github;

use Psr\Log\LoggerInterface;
use \GuzzleHttp\Client as GuzzleHttpClient;

use MBO\RemoteGit\ClientInterface;
use MBO\RemoteGit\ProjectInterface;
use MBO\RemoteGit\FindOptions;
use MBO\RemoteGit\ProjectFilterInterface;


/**
 * Client implementation for github
 * 
 * See following github docs :
 * 
 * https://developer.github.com/v3/repos/#list-organization-repositories
 * https://developer.github.com/v3/repos/#list-user-repositories
 * https://developer.github.com/v3/#pagination
 * 
 * @author mborne
 * 
 */
class GithubClient implements ClientInterface {

    const DEFAULT_PER_PAGE = 100;
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
        $result = array();
        if ( empty($options->getUsers()) && empty($options->getOrganizations()) ){
            throw new \Exception("[GithubClient]Define at least an org or a user to use find");
        }
        foreach ( $options->getUsers() as $user ){
            $result = array_merge($result,$this->findByUser(
                $user,
                $options->getFilter()
            ));
        }
        foreach ( $options->getOrganizations() as $org ){
            $result = array_merge($result,$this->findByOrg(
                $org,
                $options->getFilter()
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
            '/users/'.$user.'/repos',
            $projectFilter
        );
    }

    /**
     * Find projects by username
     *
     * @return void
     */
    protected function findByOrg(
        $org,
        ProjectFilterInterface $projectFilter
    ){
        return $this->fetchAllPages(
            '/orgs/'.$org.'/repos',
            $projectFilter
        );
    }

    /**
     * Fetch all pages for a given URI
     *
     * @param string $path such as '/orgs/IGNF/repos' or '/users/mborne/repos'
     * @return ProjectInterface[]
     */
    private function fetchAllPages(
        $path,
        ProjectFilterInterface $projectFilter
    ){
        $result = array();
        for ($page = 1; $page <= self::MAX_PAGES; $page++) {
            $uri = $path.'?page='.$page.'&per_page='.self::DEFAULT_PER_PAGE;

            $this->logger->debug('GET '.$uri);
            $response = $this->httpClient->get($uri);
            $rawProjects = json_decode( (string)$response->getBody(), true ) ;
            if ( empty($rawProjects) ){
                break;
            }
            foreach ( $rawProjects as $rawProject ){
                $project = new GithubProject($rawProject);
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
        $metadata = $project->getRawMetadata();
        $uri = str_replace(
            '{+path}',
            urlencode($filePath),
            $metadata['contents_url']
        );
        $uri .= '?ref='.$ref;
        $this->logger->debug('GET '.$uri);
        $response = $this->httpClient->get($uri,[
            'headers' => [
                'Accept' => 'application/vnd.github.v3.raw'
            ]
        ]);
        return (string)$response->getBody();
    }


}