<?php

namespace MBO\SatisGitlab\Command;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;

use MBO\SatisGitlab\Satis\ConfigBuilder;
use GuzzleHttp\Client as GuzzleHttpClient;

use MBO\RemoteGit\ClientFactory;
use MBO\RemoteGit\ClientInterface;
use MBO\RemoteGit\FindOptions;
use MBO\RemoteGit\ProjectInterface;
use MBO\RemoteGit\ClientOptions;
use MBO\RemoteGit\Filter\FilterCollection;
use MBO\RemoteGit\Filter\IgnoreRegexpFilter;
use MBO\RemoteGit\Filter\ComposerProjectFilter;
use MBO\RemoteGit\Filter\RequiredFileFilter;

use MBO\SatisGitlab\GitFilter\GitlabNamespaceFilter;

/**
 * Generate SATIS configuration scanning gitlab repositories
 *
 * @author mborne
 * @author roygoldman
 * @author ochorocho
 * @author fantoine
 * @author SilverFire
 * @author kaystrobach
 */
class GitlabToConfigCommand extends Command {

    protected function configure() {
        $templatePath = realpath( dirname(__FILE__).'/../Resources/default-template.json' );

        $this
            // the name of the command (the part after "bin/console")
            ->setName('gitlab-to-config')

            // the short description shown while running "php bin/console list"
            ->setDescription('generate satis configuration scanning gitlab repositories')
            ->setHelp('look for composer.json in default gitlab branche, extract project name and register them in SATIS configuration')
            
            /* 
             * Git client options 
             */
            ->addArgument('gitlab-url', InputArgument::REQUIRED)
            ->addArgument('gitlab-token')
            ->addOption('unsafe-ssl', null, InputOption::VALUE_NONE, 'allows to ignore SSL problems')

            /*
             * Project listing options (hosted git api level)
             */
            ->addOption('orgs', 'o', InputOption::VALUE_REQUIRED, 'Find projects according to given organization names')
            ->addOption('users', 'u', InputOption::VALUE_REQUIRED, 'Find projects according to given user names')
            ->addOption('projectFilter', 'p', InputOption::VALUE_OPTIONAL, 'filter for projects (deprecated : see organization and users)', null)

            /*
             * Project filters
             */
            ->addOption('ignore', 'i', InputOption::VALUE_REQUIRED, 'ignore project according to a regexp, for ex : "(^phpstorm|^typo3\/library)"', null)
            ->addOption('include-if-has-file',null,InputOption::VALUE_REQUIRED, 'include in satis config if project contains a given file, for ex : ".satisinclude"', null)
            ->addOption('project-type',null,InputOption::VALUE_REQUIRED, 'include in satis config if project is of a specified type, for ex : "library"', null)
            ->addOption('gitlab-namespace',null,InputOption::VALUE_REQUIRED, 'include in satis config if gitlab project namespace is in the list, for ex : "2,Diaspora" (deprecated : see organization and users)', null)
            /* 
             * satis config generation options 
             */
            // deep customization : template file extended with default configuration
            ->addOption('template', null, InputOption::VALUE_REQUIRED, 'template satis.json extended with gitlab repositories', $templatePath)

            // simple customization on default-template.json
            ->addOption('name', null, InputOption::VALUE_REQUIRED, 'satis repository name')
            ->addOption('homepage', null, InputOption::VALUE_REQUIRED, 'satis homepage')
            ->addOption('archive', null, InputOption::VALUE_NONE, 'enable archive mirroring')
            ->addOption('no-token', null, InputOption::VALUE_NONE, 'disable token writing in output configuration')

            /* 
             * output options
             */
            ->addOption('output', 'O', InputOption::VALUE_REQUIRED, 'output config file', 'satis.json')
        ;
    }

    /**
     * @{inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $logger = $this->createLogger($output);

        /*
         * Create git client according to parameters
         */
        $clientOptions = new ClientOptions();
        $clientOptions->setUrl($input->getArgument('gitlab-url'));
        $clientOptions->setToken($input->getArgument('gitlab-token'));
        
        if ( $input->getOption('unsafe-ssl') ){
            $clientOptions->setUnsafeSsl(true);
        }

        $client = ClientFactory::createClient(
            $clientOptions,
            $logger
        );

        $outputFile = $input->getOption('output');

        /*
         * Create repository listing filter (git level)
         */
        $findOptions = new FindOptions();
        /* orgs option */
        $orgs = $input->getOption('orgs');
        if ( ! empty($orgs) ){
            $findOptions->setOrganizations(explode(',',$orgs));
        }
        /* users option */
        $users = $input->getOption('users');
        if ( ! empty($users) ){
            $findOptions->setUsers(explode(',',$users));
        }

        /* projectFilter option */
        $projectFilter = $input->getOption('projectFilter');
        if ( ! empty($projectFilter) ) {
            $logger->info(sprintf("Project filter : %s...", $projectFilter));
            $findOptions->setSearch($projectFilter);
        }
        
        /*
         * Create project filters according to input arguments
         */
        $filterCollection = new FilterCollection($logger);
        $findOptions->setFilter($filterCollection);

        /*
         * Filter according to "composer.json" file
         */
        $composerFilter = new ComposerProjectFilter($client,$logger);
        /* project-type option */
        if ( ! empty($input->getOption('project-type')) ){
            $composerFilter->setProjectType($input->getOption('project-type'));
        }
        $filterCollection->addFilter($composerFilter);


        /* include-if-has-file option (TODO : project listing level) */
        if ( ! empty($input->getOption('include-if-has-file')) ){
            $filterCollection->addFilter(new RequiredFileFilter(
                $client,
                $input->getOption('include-if-has-file'),
                $logger
            ));
        }

        /*
         * Filter according to git project properties
         */

        /* ignore option */
        if ( ! empty($input->getOption('ignore')) ){
            $filterCollection->addFilter(new IgnoreRegexpFilter(
                $input->getOption('ignore')
            ));
        }
        
        /* gitlab-namespace option */
        if ( ! empty($input->getOption('gitlab-namespace')) ){
            $filterCollection->addFilter(new GitlabNamespaceFilter(
                $input->getOption('gitlab-namespace')
            ));
        }

        /*
         * Create configuration builder
         */
        $templatePath = $input->getOption('template');
        $output->writeln(sprintf("<info>Loading template %s...</info>", $templatePath));
        $configBuilder = new ConfigBuilder($templatePath);

        /*
         * customize according to command line options
         */
        $name = $input->getOption('name');
        if ( ! empty($name) ){
            $configBuilder->setName($name);
        }

        $homepage = $input->getOption('homepage');
        if ( ! empty($homepage) ){
            $configBuilder->setHomepage($homepage);
        }

        // mirroring
        if ( $input->getOption('archive') ){
            $configBuilder->enableArchive();
        }

        /*
         * Register gitlab domain to enable composer gitlab-* authentications
         */
        $gitlabDomain = parse_url($clientOptions->getUrl(), PHP_URL_HOST);
        $configBuilder->addGitlabDomain($gitlabDomain);

        if ( ! $input->getOption('no-token') && $clientOptions->hasToken() ){
            $configBuilder->addGitlabToken(
                $gitlabDomain, 
                $clientOptions->getToken(),
                $clientOptions->isUnsafeSsl()
            );
        }

        /*
         * SCAN gitlab projects to find composer.json file in default branch
         */
        $logger->info(sprintf(
            "Listing gitlab repositories from %s...", 
            $clientOptions->getUrl()
        ));

        /*
         * Find projects
         */
        $projects = $client->find($findOptions);
        
        /* Generate SATIS configuration */
        $projectCount = 0;
        foreach ($projects as $project) {
            $projectUrl = $project->getHttpUrl();

            try {
                /* look for composer.json in default branch */
                $json = $client->getRawFile(
                    $project, 
                    'composer.json', 
                    $project->getDefaultBranch()
                );

                /* retrieve project name from composer.json content */
                $composer = json_decode($json, true);
                $projectName = isset($composer['name']) ? $composer['name'] : null;
                if (is_null($projectName)) {
                    $logger->error($this->createProjectMessage(
                        $project,
                        "name not defined in composer.json"
                    ));
                    continue;
                }

                /* add project to satis config */
                $projectCount++;
                $logger->info($this->createProjectMessage(
                    $project,
                    "$projectName:*"
                ));
                $configBuilder->addRepository(
                    $projectName, 
                    $projectUrl,
                    $clientOptions->isUnsafeSsl()
                );
            } catch (\Exception $e) {
                $logger->debug($e->getMessage());
                $logger->warning($this->createProjectMessage(
                    $project,
                    'composer.json not found'
                ));
            }
        }

        /* notify number of project found */
        if ( $projectCount == 0 ){
            $logger->error("No project found!");
        }else{
            $logger->info(sprintf(
                "Number of project found : %s",
                $projectCount
            ));
        }

        /*
         * Write resulting config
         */
        $satis = $configBuilder->getConfig();
        $logger->info("Generate satis configuration file : $outputFile");
        $result = json_encode($satis, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        file_put_contents($outputFile, $result);

        return Command::SUCCESS;
    }


    /**
     * Create message for a given project 
     */
    protected function createProjectMessage(
        ProjectInterface $project,
        $message
    ){
        return sprintf(
            '%s (branch %s) : %s',
            $project->getName(),
            $project->getDefaultBranch(),
            $message
        );
    }

    /**
     * Create console logger
     * @param OutputInterface $output
     * @return ConsoleLogger
     */
    protected function createLogger(OutputInterface $output){
        $verbosityLevelMap = array(
            LogLevel::NOTICE => OutputInterface::VERBOSITY_NORMAL,
            LogLevel::INFO   => OutputInterface::VERBOSITY_NORMAL,
        );
        return new ConsoleLogger($output,$verbosityLevelMap);
    }

}
