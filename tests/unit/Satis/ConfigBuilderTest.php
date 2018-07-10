<?php

use PHPUnit\Framework\TestCase;

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


}

