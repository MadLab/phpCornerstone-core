<?php

namespace MadLab\Cornerstone\Components\TemplateBridges;

class SmartyTemplateBridge implements TemplateBridgeInterface{

    private $smarty;
    public function __construct($smarty){
        $this->smarty = $smarty;
    }

    public function set($key, $value){
        $this->smarty->assign($key, $value);
    }

    public function process($view){
        $this->smarty->fetch($view);
    }

    public function display($view){
        $this->smarty->display($view . '.tpl');
    }
}