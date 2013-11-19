<?php

namespace Madlab\Cornerstone;

use MadLab\Cornerstone\Components\TemplateBridges\TemplateBridgeInterface;

class Controller
{
    public $templateBridge;
    public $templateEnabled;
    public $app;
    protected $_args;

    public function __construct()
    {
        $this->app = App::getInstance();
    }

    public function setTemplateBridge(TemplateBridgeInterface $templateBridge)
    {
        $this->templateBridge = $templateBridge;
        $this->templateEnabled = true;
    }


    /**
     * Set the template to be displayed
     *
     * @param string $view path inside Pages folder to desired template
     */
    public function set_view($view)
    {
        $this->view = $view;
    }

    /**
     * Assigns a variable to the template, requires Smarty templating.
     *
     * @param string $key Key of variable to be accessed from template
     * @param string $val Data
     */
    public function set($key, $val)
    {
        if ($this->templateEnabled) {
            $this->templateBridge->set($key, $val);
        } else {
            throw new Exception('Template Handler Not Enabled');
        }
    }

    /**
     * Parses the given template, and returns string result
     *
     * @param string $template path to template in Pages folder to parse
     *
     * @return string result
     */
    public function process($template)
    {
        if ($this->templateEnabled) {
            $this->templateBridge->process($template);
        } else {
            throw new \Exception('Template Handler Not Enabled');
        }
    }

    /**
     * Automatically called at end of page execution. This will output the template to browser, if applicable.
     */
    public function __destruct()
    {
        if ($this->templateEnabled) {
            $this->display();
        }
    }

    /**
     * Outputs the associated template to browser
     */
    public function display()
    {

        if ($this->templateEnabled && property_exists($this, 'view')) {
            $this->templateBridge->display($this->view);
        }
    }

    /**
     * Same as die(), except this will disable the Controllers template, so nothing else is displayed.
     *
     * @param string $alert message to output before die-ing.
     */
    protected function halt($alert = '')
    {
        $this->templateEnabled = false;
        die($alert);
    }


    /**
     * Assigns variables from URL Route matching to arguments array, to be accessed by get_arg()
     *
     * @param array $args
     */
    public function set_args($args)
    {
        $this->_args = $args;
    }

    /**
     * Retrieves a variable from arguments array, typically populated from URL Route matching
     *
     * @param string $arg The key of the argument to retrieve
     *
     * @return mixed
     */
    protected function get_arg($arg)
    {
        if (is_array($this->_args) && isset($this->_args[$arg])) {
            return $this->_args[$arg];
        }
        return false;
    }
}
