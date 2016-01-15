<?php

namespace MadLab\Cornerstone;

class Controller
{

	protected $container;
	protected $template;
	protected $view;

	function __construct()
	{
		$cornerstone = Cornerstone::getInstance();
		$this->template = $cornerstone->getTemplateEngine();
		$this->container = $cornerstone->getDIContainer();
	}

	public function require (\string $service)
	{
		return $this->container[$service];
	}

	public function render(\string $view, array $params = [])
	{


		foreach ($params as $key => $value) {
			$this->template->set($key, $value);
		}
		return $this->template->process($view);
	}
}
