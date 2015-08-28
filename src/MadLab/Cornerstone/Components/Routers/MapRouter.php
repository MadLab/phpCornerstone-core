<?php

namespace MadLab\Cornerstone\Components\Routers;
use MadLab\Cornerstone\Components\Config;

class MapRouter
{
    public $subdomain = false;
    public $path;
    public $controller;
    public $regexArray;
    public $pathVariables;
    private $routes = array();

    public function __construct($domain, $subdomain = false){
        $this->domain = $domain;
        if($subdomain){
            $this->subdomain = $subdomain;
        }
        else{
            $this->subdomain = Config::get('DEFAULT_SUBDOMAIN');
        }
    }

    public function addRoute($path, $controller, $regexArray = array())
    {

        $route = new MapRouter($this->domain);

        $route->path = $path;
        $route->controller = $controller;
        $route->regexArray = $regexArray;
        $route->subdomain = $this->subdomain;
        $this->routes[] = $route;
    }

    /**
     * Tests if this Route matches the given domain and path
     *
     * @param string $domain
     * @param string $path
     *
     * @return boolean
     */
    public function findRoute()
    {
        $subdomain = str_replace($this->domain, '', $_SERVER['HTTP_HOST']);
        if (substr($subdomain, -1) == '.') {
            $subdomain = substr($subdomain, 0, -1);
        }

        foreach($this->routes as $route){

            $subdomainMatch = $route->matchSubdomain($subdomain);

            if ($subdomainMatch) {

                if (substr($_SERVER['REQUEST_URI'], 0, 1) == '/') {
                    $path = substr($_SERVER['REQUEST_URI'], 1);
                }

                $pathMatch = $route->matchPath($path);
                if($pathMatch){
                    $this->controller = $route->controller;
                    $this->pathVariables = $route->pathVariables;
                    return true;
                }
            }
        }
        return false;
    }

    public function getDetails(){
        $return = new \StdClass();

        $return->type = 'controller';
        $return->controller = $this->controller . '/';
        $return->variables = $this->pathVariables;


        return $return;
    }

    /**
     * Tests if this Route matches the given subdomain
     *
     * @param string $subdomain
     *
     * @return boolean
     */
    private function matchSubdomain($subdomain)
    {

        preg_match_all("|<([-_a-zA-Z0-9]+)>|", $this->subdomain, $namedParameterMatches);
        if ($namedParameterMatches[0] && $subdomain != Config::get('DEFAULT_SUBDOMAIN')) {
            $subdomainRegex = $this->subdomain;
            $pathVariables = array();
            foreach ($namedParameterMatches[1] as $capture) {
                $pathVariables[] = $capture;
                if (array_key_exists($capture, $this->regexArray)) {
                    $subdomainRegex = str_replace('<' . $capture . '>', $this->regexArray[$capture], $subdomainRegex);
                } else {
                    $subdomainRegex = str_replace('<' . $capture . '>', "([^\.]+)", $subdomainRegex);
                }
            }
            if (preg_match('|^' . $subdomainRegex . '$|', $subdomain, $subdomainMatches)) {
                array_shift($subdomainMatches);
                foreach ($subdomainMatches as $match) {

                    $this->pathVariables[array_shift($pathVariables)] = $match;
                }
                return true;
            }
        } elseif ($subdomain == $this->subdomain) {
            return true;
        }
        return false;
    }

    /**
     * Tests if this Route matches the given path
     *
     * @param string $path
     *
     * @return boolean
     */
    private function matchPath($path)
    {
        list($basePath, $querystring) = array_pad(explode('?', $path, 2), 2, null);

        if ($this->path == '*') {
            $subdomainPath = UrlHelper::convertUrlToPath($path);
            $testPath = $this->controller . '/' . $subdomainPath;
            if (is_dir($testPath) && is_readable($testPath . '/controller.php')) {
                $this->controller = $testPath;
                return true;
            }
        }

        preg_match_all("#<([-_a-zA-Z0-9]+)>#", $this->path, $namedParameterMatches);
        if ($namedParameterMatches[0]) {
            $pathRegex = $this->path;

            $pathVariables = array();
            foreach ($namedParameterMatches[1] as $capture) {
                $pathVariables[] = $capture;
                if (array_key_exists($capture, $this->regexArray)) {
                    $pathRegex = str_replace('<' . $capture . '>', $this->regexArray[$capture], $pathRegex);
                } else {
                    $pathRegex = str_replace('<' . $capture . '>', "([^\.\?\/]+)", $pathRegex);
                }
            }

            //try matching url ignoring querystring
            if (preg_match('#^' . $pathRegex . '$#', $basePath, $pathMatches)) {
                array_shift($pathMatches);
                foreach ($pathMatches as $match) {
                    $this->pathVariables[array_shift($pathVariables)] = $match;
                }
                return true;
            }

            //try again using querystring
            if (preg_match('#^' . $pathRegex . '$#', $path, $pathMatches)) {
                array_shift($pathMatches);
                foreach ($pathMatches as $match) {
                    $this->pathVariables[array_shift($pathVariables)] = $match;
                }
                return true;
            }
        } elseif ($this->path == $path || $this->path == $basePath) {
            return true;
        } elseif (empty($this->path) && empty($basePath)) {
            return true;
        }
        return false;
    }
}