<?php

namespace MadLab\Cornerstone;

use MadLab\Cornerstone\Components\Router;
use MadLab\Cornerstone\Components\SessionBridges\SessionBridgeInterface;
use MadLab\Cornerstone\Components\TemplateBridges\TemplateBridgeInterface;
use Controller;
class App
{
    public static $instance;
    public $path;
    public $config;
    public $dependencies;
    public $session;
    public $template;

    public function __construct($path = false)
    {
        $this->path = $path;
        $this->loadConfig();
    }

    /**
     * Framework instance factory. Will return the active framework instance, or create one if it doesn't exist
     * @static getInstance
     * @return App Instance object
     */
    public static function getInstance($path = false)
    {
        if (!isset(self::$instance)) {
            $c = __CLASS__;
            self::$instance = new $c($path);
        }
        return self::$instance;
    }


    public function run()
    {
        $route = $this->findCustomRoute();

        $this->domain = $_SERVER['HTTP_HOST'];
        $this->url = $_SERVER['REQUEST_URI'];
        $this->args = $_GET;


        if ($route) {
            $path = $route->controller;
            $this->args = $route->pathVariables;
        } else {
            $subdomainFolder = "";
            $subdomain = str_replace($this->config->NAKED_DOMAIN, '', $this->domain);
            if (substr($subdomain, -1) == '.') {
                $subdomain = substr($subdomain, 0, -1);
                $subdomainFolder = '_' . $subdomain . '_/';
            }

            $path = \MadLab\Cornerstone\Utilities\Url::convertUrlToPath($this->url);
            $path = $subdomainFolder . $path;
        }

        if (empty($path)) {
            $controllerPath = 'pages/';
        } elseif (is_dir('pages/' . $path) && is_readable('pages/' . $path . '/controller.php')) {
            $controllerPath = 'pages/' . $path . '/';
        } elseif (is_readable('pages/' . $path)) {
            $file = 'pages/' . $path;

            $filePathInfo = pathinfo($file);
            $fileExtention = $filePathInfo['extension'];
            switch ($fileExtention) {
                case 'css':
                    header("Content-type: text/css");
                    break;
                case 'js':
                    header("Content-type: text/javascript");
                    break;
                default:
                    if (function_exists('finfo_open')) {
                        $finfo = finfo_open(FILEINFO_MIME_TYPE); // return mime type ala mimetype extension
                        header("Content-type: " . finfo_file($finfo, $file));
                        finfo_close($finfo);
                    } elseif (function_exists('mime_content_type')) {
                        header("Content-type: " . mime_content_type($file));
                    } else {
                        header("Content-type: text/plain");
                    }
            }
            readfile($file);
            die();
        } else {
            throw new \Exception("404 Not Found");
        }

        include $controllerPath . 'Controller.php';
        $controller = new Controller();
        $controller->set_args($this->args);

        //todo: add pre controller hook which adds session dependency
        $controller->session = $this->getSessionHandler();
        if ($controller->templateEnabled !== false && $this->template instanceof TemplateBridgeInterface) {
            $controller->setTemplateBridge($this->template);
            $controller->view = $controllerPath . 'view';
        }
        $controller->get();

    }

    private function loadConfig()
    {
        $this->config = new Components\Config();
        $this->config->loadDirectory($this->path . "/config");
    }

    public function addDependency($name, $object)
    {
        $this->dependencies[$name] = $object;
    }

    public function resolve($dependency)
    {
        return $this->dependencies[$dependency];
    }

    public function setSessionHandler(SessionBridgeInterface $session)
    {
        $this->session = $session;
    }

    public function getSessionHandler()
    {
        if ($this->session instanceof SessionBridgeInterface) {
            return $this->session;
        } else {
            throw new Exception('No Session Handler Initialized');
        }
    }

    public function setTemplateHandler(TemplateBridgeInterface $template)
    {
        $this->template = $template;
    }

    /**
     * Attempts to match the current URL to a custom route in the routes.php file
     * @return boolean|Route returns the matching Route if found, false otherwise
     */
    private function findCustomRoute()
    {
        include('routes.php');
        foreach (Router::$routes as $route) {
            if ($route->matchUrl($_SERVER['HTTP_HOST'], $_SERVER['REQUEST_URI'])) {
                return $route;
            }
        }
        return false;
    }


    public static function redirect($location, $status = false)
    {
        if ($status == '301') {
            header("HTTP/1.1 301 Moved Permanently");
        } elseif ($status == '302') {
            header("HTTP/1.1 302 Moved Temporarily");
        }
        header('Location: ' . $location);
        die();
    }

}
