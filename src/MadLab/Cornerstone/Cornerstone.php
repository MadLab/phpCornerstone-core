<?php

namespace MadLab\Cornerstone;

use Dotenv\Dotenv;
use FastRoute\Dispatcher;
use Pimple\Container;
use Symfony\Component\HttpFoundation\Request;

class Cornerstone
{
	public static $instance;
	private $path;
	private $container;
	private $dispatcher;

	/**
	 * Cornerstone constructor.
	 * @param string $path
	 */
	public function __construct(\String $path)
	{
		$this->path = $path;
	}

	/**
	 * Framework instance factory. Will return the active framework instance, or create one if it doesn't exist
	 * @param string $path
	 * @return Cornerstone
	 */
	public static function getInstance(\String $path)
	{
		if (!isset(self::$instance)) {
			self::$instance = new Cornerstone($path);
		}
		return self::$instance;
	}

	/**
	 * @param array $requiredVars
	 */
	public function getEnvironment(array $requiredVars = [])
	{
		$envPath = $this->path . '/config';

		$dotEnv = new Dotenv($envPath);
		if (file_exists($envPath . '/.env')) {
			$dotEnv->load();
		}
		$dotEnv->required($requiredVars);
	}

	/**
	 * @param $container
	 */
	public function setDIContainer(Container $container)
	{
		$this->container = $container;
	}

	public function setDispatcher(Dispatcher $dispatcher)
	{
		$this->dispatcher = $dispatcher;
	}

	public function run()
	{
		$request = Request::createFromGlobals();
		$response = $this->dispatcher->dispatch($request->getMethod(), $request->getPathInfo());
		$response->send();
	}
}