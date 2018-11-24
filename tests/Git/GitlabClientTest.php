<?php

namespace Tests\SatisGitlab\Git;

use Tests\SatisGitlab\TestCase;

use GuzzleHttp\Client as GuzzleHttpClient;
use MBO\RemoteGit\GitlabClient;

use Psr\Log\NullLogger;
use MBO\RemoteGit\ClientOptions;
use MBO\RemoteGit\ClientFactory;
use MBO\RemoteGit\FindOptions;


class GitlabClientTest extends TestCase {

    /**
     * @return GitlabClient
     */
    protected function createGitlabClient(){
        $gitlabToken = getenv('SATIS_GITLAB_TOKEN');
        if ( empty($gitlabToken) ){
            $this->markTestSkipped("Missing SATIS_GITLAB_TOKEN for gitlab.com");
        }

        $clientOptions = new ClientOptions();
        $clientOptions
            ->setUrl('https://gitlab.com')
            ->setToken($gitlabToken)
        ;

        /* create client */
        return ClientFactory::createClient(
            $clientOptions,
            new NullLogger()
        );
    }

    /**
     * Ensure client can find mborne/sample-composer by username
     */
    public function testGitlabDotComByUser(){
        /* create client */
        $client = $this->createGitlabClient();
        $this->assertInstanceOf(GitlabClient::class,$client);

        /* search projects */
        $findOptions = new FindOptions();
        $findOptions->setUsers(array('mborne'));
        $projects = $client->find($findOptions);
        $projectsByName = array();
        foreach ( $projects as $project ){
            $projectsByName[$project->getName()] = $project;
        }
        /* check project found */
        $this->assertArrayHasKey(
            'mborne/sample-composer',
            $projectsByName
        );

        $project = $projectsByName['mborne/sample-composer'];
        $composer = $client->getRawFile(
            $project,
            'composer.json',
            $project->getDefaultBranch()
        );
        $this->assertContains('mborne@users.noreply.github.com',$composer);
    }


    /**
     * Ensure client can find mborne/sample-composer with search
     */
    public function testGitlabDotComSearch(){
        /* create client */
        $client = $this->createGitlabClient();
        $this->assertInstanceOf(GitlabClient::class,$client);

        /* search projects */
        $findOptions = new FindOptions();
        $findOptions->setSearch('sample-composer');
        $projects = $client->find($findOptions);
        $projectsByName = array();
        foreach ( $projects as $project ){
            $projectsByName[$project->getName()] = $project;
        }
        /* check project found */
        $this->assertArrayHasKey(
            'mborne/sample-composer',
            $projectsByName
        );

        $project = $projectsByName['mborne/sample-composer'];
        $composer = $client->getRawFile(
            $project,
            'composer.json',
            $project->getDefaultBranch()
        );
        $this->assertContains('mborne@users.noreply.github.com',$composer);
    }

}
