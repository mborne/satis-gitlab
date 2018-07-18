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
use MBO\SatisGitlab\Git\GitlabClient;
use MBO\SatisGitlab\Git\ProjectInterface;
use MBO\SatisGitlab\Git\ClientOptions;



/**
 * Generate SATIS configuration scanning gitlab repositories
 *
 * @author MBorne
 */
class GitlabToConfigCommand extends Command {

    const MAX_PAGES = 10000;

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

            /*
             * Project listing options
             */
            ->addOption('projectFilter', 'p', InputOption::VALUE_OPTIONAL, 'filter for projects', null)

            /* 
             * satis config generation options 
             */
            // deep customization : template file extended with default configuration
            ->addOption('template', null, InputOption::VALUE_REQUIRED, 'template satis.json extended with gitlab repositories', $templatePath)

            // simple customization
            ->addOption('homepage', null, InputOption::VALUE_REQUIRED, 'satis homepage')
            ->addOption('archive', null, InputOption::VALUE_NONE, 'enable archive mirroring')
            ->addOption('no-token', null, InputOption::VALUE_NONE, 'disable token writing in output configuration')

            /* 
             * output options
             */
            ->addOption('output', 'O', InputOption::VALUE_REQUIRED, 'output config file', 'satis.json')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $logger = $this->createLogger($output);

        /*
         * Create git client according to parameters
         */
        $clientOptions = new ClientOptions();
        $clientOptions->setUrl($input->getArgument('gitlab-url'));
        $clientOptions->setToken($input->getArgument('gitlab-token'));
        /*
         * TODO add option 
         * see https://github.com/mborne/satis-gitlab/issues/2
         */
        $clientOptions->setUnsafeSsl(true);
        $client = GitlabClient::createClient(
            $clientOptions,
            $logger
        );

        
        $outputFile = $input->getOption('output');
        $projectFilter = $input->getOption('projectFilter');

        /*
         * Create configuration builder
         */
        $templatePath = $input->getOption('template');
        $output->writeln(sprintf("<info>Loading template %s...</info>", $templatePath));
        $configBuilder = new ConfigBuilder($templatePath);

        /*
         * customize according to command line options
         */
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

        $findOptions = array();
        if ( ! empty($projectFilter) ) {
            $logger->info(sprintf("Project filter : %s...", $projectFilter));
            $findOptions['search'] = $projectFilter;
        }

        /*
         * Scan gitlab pages until no more projects are found
         */
        for ($page = 1; $page <= self::MAX_PAGES; $page++) {
            $findOptions['page'] = $page;
            $projects = $client->find($findOptions);
            if ( empty($projects) ){
                break;
            }
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
        }

        /*
         * Write resulting config
         */
        $satis = $configBuilder->getConfig();
        $logger->info("Generate satis configuration file : $outputFile");
        $result = json_encode($satis, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        file_put_contents($outputFile, $result);
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
