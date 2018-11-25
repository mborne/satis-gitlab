<?php

namespace MBO\RemoteGit;

/**
 * Git connection options
 * 
 * @author mborne
 */
class ClientOptions {

    /**
     * Base URL (ex : https://gitlab.com)
     *
     * @var string
     */
    private $url ;

    /**
     * Access token
     * 
     * @var string
     */
    private $token ;

    /**
     * Bypass SSL certificate checks for self signed certificates
     *
     * @var boolean
     */
    private $unsafeSsl;

    public function __construct()
    {
        $this->unsafeSsl = false;
    }

    /**
     * @return  string
     */ 
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set URL
     *
     * @param  string  $url
     *
     * @return  self
     */ 
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Is token defined?
     *
     * @return boolean
     */
    public function hasToken(){
        return ! empty($this->token);
    }

    /**
     * Get access token
     *
     * @return  string
     */ 
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Set access token
     *
     * @param  string  $token  Access token
     *
     * @return  self
     */ 
    public function setToken($token)
    {
        $this->token = $token;

        return $this;
    }

    /**
     * @return  boolean
     */ 
    public function isUnsafeSsl()
    {
        return $this->unsafeSsl;
    }

    /**
     * Set unsafeSsl
     *
     * @param  boolean  $unsafeSsl 
     *
     * @return  self
     */ 
    public function setUnsafeSsl($unsafeSsl)
    {
        $this->unsafeSsl = $unsafeSsl;

        return $this;
    }
}
