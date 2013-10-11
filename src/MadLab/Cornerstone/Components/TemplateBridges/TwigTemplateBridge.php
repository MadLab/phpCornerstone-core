<?php

namespace MadLab\Cornerstone\Components\TemplateBridges;

class TwigTemplateBridge implements TemplateBridgeInterface{

    private $twig;
    private $context;
    public function __construct($twig){
        $this->twig = $twig;
        $this->context = array();
    }

    public function set($key, $value){
        $this->context[$key] = $value;
    }

    public function process($view){
        return $this->twig->render($view . '.twig', $this->context);
    }

    public function display($view){
        echo $this->process($view);
    }
}