<?php

namespace Tests\SatisGitlab\Satis;

use Tests\SatisGitlab\TestCase;

use Symfony\Component\Console\Tester\CommandTester;
use MBO\SatisGitlab\Satis\ConfigBuilder;

class ConfigBuilderTest extends TestCase {

    public function testDefaultConstructor(){
        $configBuilder = new ConfigBuilder();
        $result = $configBuilder->getConfig();
        // homepage
        $this->assertArrayHasKey('homepage',$result);
        $this->assertEquals('http://localhost/satis/',$result['homepage']);
    }

    public function testSetHomepage(){
        $configBuilder = new ConfigBuilder();
        $configBuilder->setHomepage('http://satis.example.org');
        $result = $configBuilder->getConfig();
        // homepage
        $this->assertArrayHasKey('homepage',$result);
        $this->assertEquals('http://satis.example.org',$result['homepage']);
    }

    public function testEnableArchive(){
        $configBuilder = new ConfigBuilder();
        $configBuilder->enableArchive();
        $result = $configBuilder->getConfig();

        $this->assertArrayHasKey('archive',$result);
        
        $this->assertArrayHasKey('directory',$result['archive']);
        $this->assertEquals('dist',$result['archive']['directory']);
        
        $this->assertArrayHasKey('format',$result['archive']);
        $this->assertEquals('tar',$result['archive']['format']);
        
        $this->assertArrayHasKey('skip-dev',$result['archive']);
        $this->assertTrue($result['archive']['skip-dev']);
    }

    public function testAddGitlabDomain(){
        $configBuilder = new ConfigBuilder();
        $configBuilder->addGitlabDomain('gitlab.com');
        $configBuilder->addGitlabDomain('my-gitlab.com');        

        $result = $configBuilder->getConfig();

        $this->assertArrayHasKey('config',$result);
        
        $this->assertEquals(
            '{"gitlab-domains":["gitlab.com","my-gitlab.com"]}',
            json_encode($result['config'])
        );
    }

    public function testAddGitlabToken(){
        $configBuilder = new ConfigBuilder();
        $configBuilder->addGitlabToken('gitlab.com','test');

        $result = $configBuilder->getConfig();

        $this->assertArrayHasKey('config',$result);
        
        $this->assertEquals(
            '{"gitlab-token":{"gitlab.com":"test"}}',
            json_encode($result['config'])
        );
    }

    public function testAddRepository(){
        $configBuilder = new ConfigBuilder();
        $configBuilder->addRepository(
            'mborne/fake-a',
            'https://github.com/mborne/fake-a.git',
            false
        );
        $configBuilder->addRepository(
            'mborne/fake-b',
            'https://github.com/mborne/fake-b.git',
            true
        );

        $satis = $configBuilder->getConfig();

        $this->assertArrayHasKey('repositories',$satis);

        $result = $satis['repositories'];
        /* compare complete file */
        $expectedPath = dirname(__FILE__).'/expected-repositories.json';
        //file_put_contents($expectedPath,json_encode($result,JSON_PRETTY_PRINT));
        $this->assertJsonStringEqualsJsonFile(
            $expectedPath,
            json_encode($result,JSON_PRETTY_PRINT)
        );
    }


}

