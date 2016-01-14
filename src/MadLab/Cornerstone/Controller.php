<?php

namespace MadLab\Cornerstone;


class Controller
{

	protected $container;
	protected $template;
	protected $view;

	function __construct()
	{
		global $container;
		$this->container = $container;

	}

	public function require (\string $service)
	{
		return $this->container[$service];
	}

	public function render($view, $params)
	{
		$twig = $this->require('Twig');
		return $twig->render($view . '.twig', $params);
	}

}
