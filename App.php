<?php


class App {
	private static $instance;
	private array $hook;
	private array $path;
	private array $callable;


	private function __clone() {}
	public function __wakeup() {
		throw new Exception("Cannot unserialize singleton", 1);
	}


	private function __construct()
	{
		// Autoload current path
		$this->addPath('.');
	}

	public static function instance()
	{
		if ( ! self::$instance )
			self::$instance = new App();

		return self::$instance;
	}



	/*
	 * Hook
	 *****************************************/
	public function __hook( string $name, callable $function ) : App
	{
		$this->hook[$name][] = $function;

		return $this;
	}


	public function __fire( string $name, ...$arguments ) : App
	{
		if ( isset( $this->hook[$name] ) )
			foreach ( $this->hook[$name] as $function )
				call_user_func_array( $function, [$arguments]);

		return $this;
	}



	/*
	 * Autoload
	 *****************************************/
	public function __register() : App
	{
		spl_autoload_register( [$this, 'autoload'] );

		return $this;
	}

	public function __loadFrom( string $path ) : App
	{
		$this->addPath( $path );

		return $this;
	}

	public function __loadFirst( string $path ) : App
	{
		$this->addPath( $path, true );

		return $this;
	}


	private function autoload( string $class )
	{
		$class = $this->sanitzeClass( $class );

		foreach ( $this->path as $path ) {
			$file = $path.$class.'.php';

			if ( file_exists( $file ) ) {
				require $path; // load class file

				break; // Stop searching
			}
		}
	}

	private function sanitzeClass( string $class ) : string
	{
		$class = ltrim($class, '\\');

		// Remove underscores and ucfirst
		$class = implode('',
			array_map('ucfirst',
				explode('_', $class)
			)
		);

		// Change to correct Directory separator
		$class = str_replace('\\', DS, $class);

		return $class;
	}

	private function addPath( string $path, bool $reverse = false ) : void
	{
		// Get full path
		$path = realpath( $path );

		if ( ! $path ) // If path does not exist, stop.
			return;
			
		// reverse array if path must be the first item.
		if ( $reverse )
			$this->path = array_reverse( $this->$path );

		$this->path[] = $path;
			
		// reverse array back
		if ( $reverse )
			$this->paths = array_reverse( $this->$path );
	}

	public static function __callStatic( string $method, array $arguments )
	{
		if (
			method_exists( self::instance(), "__$method") and
			(new ReflectionMethod(self::instance(), "__$method"))->isPublic()
		)
			return call_user_func_array([self::instance(), "__$method"], $arguments);

		// We should error at this point
		throw new BadMethodCallException(static::class."::$method is not a valid function");
	}
}