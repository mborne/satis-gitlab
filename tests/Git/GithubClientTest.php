<?php

namespace Tests\SatisGitlab\Git;

use Tests\SatisGitlab\TestCase;

use GuzzleHttp\Client as GuzzleHttpClient;
use MBO\SatisGitlab\Git\GitlabClient;

use Psr\Log\NullLogger;
use MBO\SatisGitlab\Git\ClientOptions;
use MBO\SatisGitlab\Git\ClientFactory;
use MBO\SatisGitlab\Git\GithubClient;
use MBO\SatisGitlab\Git\GithubProject;
use MBO\SatisGitlab\Git\FindOptions;

class GithubClientTest extends TestCase {

    /**
     * Ensure client can find mborne's projects
     */
    public function testUserRepositories(){
        $clientOptions = new ClientOptions();
        $clientOptions
            ->setUrl('https://api.github.com')
        ;

        /* create client */
        $client = ClientFactory::createClient(
            $clientOptions,
            new NullLogger()
        );
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
        $clientOptions = new ClientOptions();
        $clientOptions
            ->setUrl('https://api.github.com')
        ;

        /* create client */
        $client = ClientFactory::createClient(
            $clientOptions,
            new NullLogger()
        );
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
