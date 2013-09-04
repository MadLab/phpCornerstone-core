<?php

namespace MadLab\Cornerstone\Components\TemplateBridges;

interface TemplateBridgeInterface{

    public function set($key, $value);
    public function process($view);
    public function display($view);
}