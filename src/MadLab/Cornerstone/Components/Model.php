<?php

namespace MadLab\Cornerstone\Components;

use MadLab\Cornerstone\App;

abstract class Model
{

    public function resolve($dependency){
        return App::getInstance()->resolve($dependency);
    }
}