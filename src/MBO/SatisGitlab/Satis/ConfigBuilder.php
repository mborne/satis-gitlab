<?php

namespace MBO\SatisGitlab\Satis;

/**
 * Incremental satis configuration builder
 * 
 * @author mborne
 */
class ConfigBuilder {

    /**
     * resulting configuration
     */
    protected $config ;

    /**
     * Init configuration with a template
     * @param $templatePath string path to the template
     */
    public function __construct( $templatePath = null )
    {
        if ( empty($templatePath) ){
            $templatePath = dirname(__FILE__).'/../Resources/default-template.json';
        }
        $this->config = json_decode(file_get_contents($templatePath),true);
    }

    /**
     * Get resulting configuration
     */
    public function getConfig(){
        return $this->config;
    }

    /**
     * Set name
     */
    public function setName($name){
        $this->config['name'] = $name;

        return $this;
    }

    /**
     * Set homepage
     * @return $self
     */
    public function setHomepage($homepage){
        $this->config['homepage'] = $homepage;

        return $this;
    }

    /**
     * Turn on mirror mode
     * @return $self
     */
    public function enableArchive(){
        $this->config['archive'] = array(
            'directory' => 'dist',
            'format' => 'tar',
            'skip-dev' => true
        );
    }

    /**
     * Add gitlab domain to config
     * @return $self
     */
    public function addGitlabDomain($gitlabDomain){
        if ( ! isset($this->config['config']) ){
            $this->config['config'] = array();
        }
        if ( ! isset($this->config['config']['gitlab-domains']) ){
            $this->config['config']['gitlab-domains'] = array();
        }

        $this->config['config']['gitlab-domains'][] = $gitlabDomain ;

        return $this;
    }

    /**
     * Add gitlab token
     * 
     * TODO : Ensure addGitlabDomain is invoked?
     * 
     * @return $self
     */
    public function addGitlabToken($gitlabDomain, $gitlabAuthToken){
        if ( ! isset($this->config['config']['gitlab-token']) ){
            $this->config['config']['gitlab-token'] = array();
        }
        $this->config['config']['gitlab-token'][$gitlabDomain] = $gitlabAuthToken;

        return $this;
    }


    /**
     * Add a repository to satis
     *  
     * @param string $projectName "{vendorName}/{componentName}"
     * @param string $projectUrl
     * @param boolean $unsafeSsl allows to disable ssl checks 
     * 
     * @return $self
     */
    public function addRepository(
        $projectName,
        $projectUrl,
        $unsafeSsl = false
    ){
        if ( ! isset($this->config['repositories']) ){
            $this->config['repositories'] = array();
        }

        $repository = array(
            'type' => 'vcs',
            'url' => $projectUrl
        );

        if ( $unsafeSsl ){
            $repository['options'] = [
                "ssl" => [
                    "verify_peer" => false,
                    "verify_peer_name" => false,
                    "allow_self_signed" => true
                ]
            ];
        }

        $this->config['repositories'][] = $repository ;
        $this->config['require'][$projectName] = '*';
    }

}