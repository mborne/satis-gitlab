<?php

namespace MBO\SatisGitlab\Command;

use Composer\Composer;
use Composer\Config;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * Generate SATIS configuration scanning gitlab repositories and adding
 * dependencies to the list of required packages.
 *
 * @author Rich Gerdes
 */
class GitlabDependenciesToConfigCommand extends GitlabCommandBase {

    protected function configure() {
        parent::configure();

        $this
            // the name of the command (the part after "bin/console")
            ->setName('gitlab-dependencies-to-config')
        ;
    }

    protected function processProject(InputInterface $input, OutputInterface $output, array &$satis, array $project, array $composer) {
        $project_name = $this->projectName($project, $composer);

        $local_packages = array();
        if (isset($composer['repositories']) && is_array($composer['repositories'])) {
            foreach ($composer['repositories'] as $repository) {
                // find list of packages that are defined locally.
                if ($repository['type'] === 'package' && isset ($repository['package'])) {
                    $local_packages[] = $repository['package']['name'];
                }
            }
        }

        if (isset($composer['require']) && is_array($composer['require'])) {
            foreach (array_keys($composer['require']) as $dep_name) {
                if (in_array($dep_name, $local_packages)) {
                    $output->writeln(sprintf("<info> Skipping local package %s...</info>", $dep_name));
                    continue;
                } else if (in_array($dep_name, array_keys($satis['require']))) {
                    continue;
                }
                $satis['require'][$dep_name] = '*';

            }
        }

    }

}
