<?php

namespace Tests\SatisGitlab\Filter;

use Tests\SatisGitlab\TestCase;

use Psr\Log\NullLogger;

use MBO\RemoteGit\Filter\FilterCollection;
use MBO\RemoteGit\ProjectInterface;
use MBO\RemoteGit\Filter\ProjectFilterInterface;

/**
 * Test FilterCollection
 */
class FilterCollectionTest extends TestCase {

    public function testEmpty(){
        $filterCollection = new FilterCollection(new NullLogger());
        $project = $this->createMockProject('test');
        $this->assertTrue($filterCollection->isAccepted($project));
    }

    /**
     * Create a fake project filter returning true or false
     *
     * @param boolean $accepted
     * @return ProjectFilterInterface
     */
    private function createMockFilter($accepted){
        $filter = $this->getMockBuilder(ProjectFilterInterface::class)
            ->getMock()
        ;
        $filter->expects($this->any())
            ->method('isAccepted')
            ->willReturn($accepted)
        ;
        return $filter;
    }


    public function testOneTrue(){
        $filterCollection = new FilterCollection(new NullLogger());
        $filterCollection->addFilter($this->createMockFilter(true));
        $project = $this->createMockProject('test');
        $this->assertTrue($filterCollection->isAccepted($project));
    }

    public function testOneFalse(){
        $filterCollection = new FilterCollection(new NullLogger());
        $filterCollection->addFilter($this->createMockFilter(false));
        $project = $this->createMockProject('test');
        $this->assertFalse($filterCollection->isAccepted($project));
    }

    /**
     * Check that isAccepted is unanymous
     */
    public function testTrueFalseTrue(){
        $filterCollection = new FilterCollection(new NullLogger());
        $filterCollection->addFilter($this->createMockFilter(true));      
        $filterCollection->addFilter($this->createMockFilter(false));
        $filterCollection->addFilter($this->createMockFilter(true));      
        $project = $this->createMockProject('test');
        $this->assertFalse($filterCollection->isAccepted($project));
    }

}

