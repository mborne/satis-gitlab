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
 * Generate SATIS configuration scanning gitlab repositories
 *
 * @author MBorne
 */
class GitlabToConfigCommand extends Command {

    const PER_PAGE = 50;
    const MAX_PAGES = 10000;

    protected function configure() {
        $this
            // the name of the command (the part after "bin/console")
            ->setName('gitlab-to-config')

            // the short description shown while running "php bin/console list"
            ->setDescription('generate satis configuration scanning gitlab repositories')
            ->setHelp('look for composer.json in default gitlab branche, extract project name and register them in SATIS configuration')
            ->addArgument('gitlab-url', InputArgument::REQUIRED)
            ->addArgument('gitlab-token')
            
            ->addOption('output', 'O', InputOption::VALUE_REQUIRED, 'output config file', 'satis.json')
            ->addOption('homepage', null, InputOption::VALUE_REQUIRED, 'satis homepage', 'http://satis.example.org/satis/')
            ->addOption('archive', null, InputOption::VALUE_NONE, 'enable archive mirroring')
            ->addOption('additional-config', null, InputOption::VALUE_OPTIONAL, 'Config prototype, that will be added to the generated config')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        /*
         * parameters
         */
        $gitlabUrl = $input->getArgument('gitlab-url');
        $gitlabAuthToken = $input->getArgument('gitlab-token');
        $outputFile = $input->getOption('output');
        $additionalConfigFile = $input->getOption('additional-config');
        $homepage = $input->getOption('homepage');
        
        /*
         * prepare satis config
         */
        $satis = array();
        $satis['name'] = "SATIS repository";
        $satis['homepage'] = $homepage;
        $satis['repositories'] = array();
        /* packagist */
        $satis['repositories'][] = array(
            'type' => 'composer',
            'url' => 'https://packagist.org'
        );
        $satis['require'] = array();
        $satis['require-dependencies'] = true;
        /* mirroring */
        if ( $input->getOption('archive') ){
            $satis['archive'] = array(
                'directory' => 'dist',
                'format' => 'tar',
                'skip-dev' => true
            );
        }

        $client = $this->createGitlabClient($gitlabUrl, $gitlabAuthToken);

        /*
         * SCAN projects
         */
        for ($page = 1; $page <= self::MAX_PAGES; $page++) {
            $projects = $client->projects()->all(array(
                'page' => $page, 
                'per_page' => self::PER_PAGE
            ));
            if ( empty($projects) ){
                break;
            }
            foreach ($projects as $project) {
                $projectUrl = $project['http_url_to_repo'];
                try {
                    $json = $client->repositoryFiles()->getRawFile($project['id'], 'composer.json', $project['default_branch']);
                    $composer = json_decode($json, true);

                    $projectName = isset($composer['name']) ? $composer['name'] : null;
                    if (is_null($projectName)) {
                        $this->displayProjectInfo($output,$project,'<error>name not defined in composer.json</error>');
                        continue;
                    }

                    $satis['repositories'][] = array(
                        'type' => 'vcs',
                        'url' => $projectUrl
                    );
                    $satis['require'][$projectName] = '*';
                    $this->displayProjectInfo($output,$project,
                        "<info>$projectName:*</info>"
                    );
                } catch (\Exception $e) {
                    $this->displayProjectInfo($output,$project,
                        'composer.json not found',
                        OutputInterface::VERBOSITY_VERBOSE
                    );
                }
            }
        }

        if ($additionalConfigFile) {
            $satis = $this->mergeWithAdditionalConfig($additionalConfigFile, $satis);
        }

        $output->writeln("<info>generate satis configuration file : $outputFile</info>");
        $result = json_encode($satis, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        file_put_contents($outputFile, $result);
    }

    /**
     * display project information
     */
    protected function displayProjectInfo(
        OutputInterface $output, 
        array $project, 
        $message,
        $verbosity = OutputInterface::VERBOSITY_NORMAL
    ){
        $output->writeln(sprintf(
            '%s (branch %s) : %s',
            $project['name_with_namespace'],
            $project['default_branch'],
            $message
        ),$verbosity);
    }
    
    
    /**
     * Create gitlab client
     * @param string $gitlabUrl
     * @param string $gitlabAuthToken
     * @return \Gitlab\Client
     */
    protected function createGitlabClient($gitlabUrl, $gitlabAuthToken) {
        /*
         * create client with ssl verify disabled
         */
        $guzzleClient = new \GuzzleHttp\Client(array(
            'verify' => false
        ));
        $httpClient = new \Http\Adapter\Guzzle6\Client($guzzleClient);
        $httpClientBuilder = new \Gitlab\HttpClient\Builder($httpClient);

        $client = new \Gitlab\Client($httpClientBuilder);
        $client->setUrl($gitlabUrl);

        /*
         * gitlab authentification
         */
        $client
            ->authenticate($gitlabAuthToken, \Gitlab\Client::AUTH_URL_TOKEN)
        ;
        
        return $client;
    }

    /**
     * Recursively merges $satis config with config from $additionalConfigFile
     *
     * @param string $additionalConfigFile
     * @param array $satis
     * @return array
     */
    protected function mergeWithAdditionalConfig($additionalConfigFile, $satis)
    {
        $fileContent = file_get_contents($additionalConfigFile);
        $config = json_decode($fileContent, true);

        return array_merge_recursive($satis, $config);
    }
}
