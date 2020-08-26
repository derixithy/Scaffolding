<?php

class Router
{

	protected $routes = [];
	protected $params;
	protected $routeFound = false;
	protected $routePrepend;
	protected $routeAppend;
	protected $filters = [
		':string' => '([a-zA-Z]+)',
		':number' => '([0-9]+)',
		':alpha'  => '([a-zA-Z0-9-_]+)'
	];

		public function __construct( $prepend = false, $append = false )
		{
			if ( is_string($prepend) )
				$this->routePrepend = $prepend;

			if ( is_string($append) )
				$this->routeAppend = $append;
		}



	public function addFilter( string $identifier, string $regex ) : Router
	{
		$this->filters[":$identifier"] = $regex;

		return $this;
	}

	public function addRoute( string $path, string $handler ) : Router
	{

		$this->routes[$path] = $handler;

		return $this;
	}

	public function dispatch( $path )
	{
		Hook::fire('route::dispatch', $path);

		return $this->execute($path);
	}

	public function found(): bool
	{
		return $this->routeFound;
	}

	private function execute( $path )
	{
		// Do not call before routes are populated
		if ( empty($this->routes) )
			throw new Exception("Router: No routes found");


		// Check if route exist, and get output
		$routeOuput = $this->parse( $path );

		// Everything not false is success
		$this->routeFound = ( $routeOuput !== false );

		// on success return route output
		if ( $this->routeFound )
			return $routeOuput;


		// Still here? Load 404 page
		if ( isset( $this->routes[404] ) )
		{
			$this->routeFound = false;
			return $this->handler( $this->routes[404] );

			// On success return output
		}


		throw new Exception("Router: No 404 page set");

	}

	public function parameters()
	{
		return $this->params;
	}

	public function dump()
	{
		var_dump($this->routes);
	}

	protected function parse( string $route )
	{
		// Check if path already defined - no regex
		// stop if handler returned non-false
		if ( isset($this->routes[$route]) )
		{
			$handlerOutput = $this->handler( $this->routes[$route] );

			if ( $handlerOutput !== false )
				return $handlerOutput;
		}


		// try to find a matching route
		foreach ($this->routes as $path => $handler) {
			// change placeholders for regex
			$path = strtr($path, $this->filters);

			if ( preg_match('#^/?'.$path.'/?$#', $route, $matches) ) {
				unset ($matches[0]);

				// save the parameters to grab later
				$this->params = $matches;
				$handlerOutput = $this->handler( $handler, $matches );
				if ( $handlerOutput !== false ) {
					return $handlerOutput;
					break;
				}
			}
		}

		return false;
	}

	protected function handler ( string $handler, array $arguments = [] )
	{

		// Get method and set classname
		$requestMethod = strtolower($_SERVER['REQUEST_METHOD']);
		$handler = $this->routePrepend.$handler.$this->routeAppend;

		// call method
		if ( method_exists($handler, $requestMethod))
			return (new $handler)->$requestMethod(...$arguments);

		// call map
		if ( method_exists($handler, 'map'))
			return (new $handler)->map(...$arguments);


		return false; // failed
	}
}
