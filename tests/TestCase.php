<?php

namespace Tests\SatisGitlab;

use PHPUnit\Framework\TestCase as BaseTestCase;

use MBO\SatisGitlab\Git\ProjectInterface;

class TestCase extends BaseTestCase {

    /**
     * Create a fake project with a given name
     *
     * @param string $projectName
     * @return ProjectInterface
     */
    protected function createMockProject($projectName){
        $project = $this->getMockBuilder(ProjectInterface::class)
            ->getMock()
        ;
        $project->expects($this->any())
            ->method('getName')
            ->willReturn($projectName)
        ;
        return $project;
    }

} 