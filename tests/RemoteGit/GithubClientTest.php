<?php

namespace MBO\RemoteGit\Tests;

use MBO\SatisGitlab\Tests\TestCase;

use GuzzleHttp\Client as GuzzleHttpClient;
use MBO\RemoteGit\GitlabClient;

use Psr\Log\NullLogger;
use MBO\RemoteGit\ClientOptions;
use MBO\RemoteGit\ClientFactory;
use MBO\RemoteGit\GithubClient;
use MBO\RemoteGit\GithubProject;
use MBO\RemoteGit\FindOptions;

class GithubClientTest extends TestCase {

    /**
     * @return GithubClient
     */
    protected function createGithubClient(){
        $token = getenv('SATIS_GITHUB_TOKEN');
        if ( empty($token) ){
            $this->markTestSkipped("Missing SATIS_GITHUB_TOKEN for github.com");
        }

        $clientOptions = new ClientOptions();
        $clientOptions
            ->setUrl('https://github.com')
            ->setToken($token)
        ;

        /* create client */
        return ClientFactory::createClient(
            $clientOptions,
            new NullLogger()
        );
    }

    /**
     * Ensure client can find mborne's projects
     */
    public function testUserRepositories(){
        /* create client */
        $client = $this->createGithubClient();
        $this->assertInstanceOf(GithubClient::class,$client);

        /* search projects */
        $options = new FindOptions();
        $options->setUsers(array('mborne'));
        $projects = $client->find($options);
        $projectsByName = array();
        foreach ( $projects as $project ){
            $this->assertInstanceOf(GithubProject::class,$project);
            $projectsByName[$project->getName()] = $project;
        }

        /* check project found */
        $this->assertArrayHasKey(
            'mborne/satis-gitlab',
            $projectsByName
        );

        $project = $projectsByName['mborne/satis-gitlab'];
        $composer = $client->getRawFile(
            $project,
            'composer.json',
            $project->getDefaultBranch()
        );
        $this->assertContains('mborne@users.noreply.github.com',$composer);

        $testFileInSubdirectory = $client->getRawFile(
            $project,
            'tests/TestCase.php',
            $project->getDefaultBranch()
        );
        $this->assertContains('class TestCase',$testFileInSubdirectory);
    }


    /**
     * Ensure client can find mborne's projects with composer.json file
     */
    public function testFilterFile(){
        /* create client */
        $client = $this->createGithubClient();
        $this->assertInstanceOf(GithubClient::class,$client);

        /* search projects */
        $options = new FindOptions();
        $options->setUsers(array('mborne'));        
        $projects = $client->find($options);
        $projectsByName = array();
        foreach ( $projects as $project ){
            $this->assertInstanceOf(GithubProject::class,$project);
            $projectsByName[$project->getName()] = $project;
        }

        /* check project found */
        $this->assertArrayHasKey(
            'mborne/satis-gitlab',
            $projectsByName
        );

        $project = $projectsByName['mborne/satis-gitlab'];
        $composer = $client->getRawFile(
            $project,
            'composer.json',
            $project->getDefaultBranch()
        );
        $this->assertContains('mborne@users.noreply.github.com',$composer);

        $testFileInSubdirectory = $client->getRawFile(
            $project,
            'tests/TestCase.php',
            $project->getDefaultBranch()
        );
        $this->assertContains('class TestCase',$testFileInSubdirectory);
    }


}
