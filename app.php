<?php
declare(strict_types = 1);
define('DS', DIRECTORY_SEPARATOR);



/*
 *						App
 *
 *************************************************************/

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
		$this->addPath('Library/');
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
			$file = $path.DS.$class.'.php';

			if ( file_exists( $file ) ) {
				require $file; // load class file

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



/*
 *						Repositories
 *
 *************************************************************/

class Decorator {

	private function output() : string
	{
		// extract variables from array, for use in template
		extract( Template::export() );


		ob_start(); // get output
		include $this->file;

		return ob_get_clean(); // return output
	}

	public function print() : void
	{
		echo $this->output();
	}

	public function __toString() : string
	{
		return $this->output();
	}
}


class Dossier {
	protected string $item;


	public function __construct( string $item )
	{
		if ( $item instanceof Dossier )
			$item = $item->string();

		$this->item = $item;
	}

	public function __toString() : String
	{
		return $this->string();
	}

	public function string() : String
	{
		return $this->item;
	} 
}





/*
 *						REGISTRIES
 *
 *************************************************************/

class Registry extends ArrayObject {
	//protected $_data;

	public function __construct( array $data = [] )
	{
		$this->import( $data );
		$this->setFlags( self::ARRAY_AS_PROPS);
	}

	final public function offsetSet( $offset, $value )
	{
		if ( ! $this->isValidItem( $value ) )
			return;

		$value = $this->processItem( $value );

		$value = is_array( $value ) ? new self( $value ) : $value;


		return parent::offsetSet( $offset, $value );
	}

	public function import( array $data )
	{
		foreach( $data as $index => $value ) {
			$this->offsetSet( $index, $value );
		}
	}

	public function export( bool $recursion = false)
	{
		if ( $recursion === true )
			return $this->getArrayCopy();

		return array_map( function( $item )
		{
			return (is_object($item) and $item instanceof Registry)
				? $item->getArray(true)
				: $item;
		}, $this->getArrayCopy() );
	}

	public function set( string $index, $value )
	{
		return $this->offsetSet( $index, $value );
	}

	public function get( string $index = null )
	{
		if ( $index === null )
			return $this;

		return 
			$this->offsetExists( $index )
			? $this->offsetGet( $index )
			: null;
	}

	public function unset( string $index )
	{
		return $this->offsetUnset( $index );
	}

	public function isset( string $index )
	{
		return $this->offsetExists( $index );
	}

	public function has( string $value )
	{
		return in_array( $value, $this->getArrayCopy() );
	}



	protected function isValidItem( $item ) : bool
	{
		return true;
	}

	protected function processItem( $item )
	{
		return $item;
	}



    final public function __set ( string $name , $value ) : void
    {
        $this->offsetSet( $name, $value );
    }

    final public function __get ( string $name )
    {
        return $this->offsetGet( $name );
    }

    final public function __isset ( string $name ) : bool
    {
        return $this->offsetExists( $name );
    }

    final public function __unset ( string $name ) : void
    {
        $this->offsetUnset( $name );
    }

	public function __call( string $method, array $arguments )
	{
		return $this->call( $method, $arguments );
	}



	public function call( string $method, array $arguments )
	{
		if ( $this->isset( $method ) )
			if( is_callable( [$this, $method] ) )
				return call_user_func_array( [$this,$method], $arguments );

		throw new BadMethodCallException(static::class."::$method is not a valid function");
	}

	public function id()
	{
		return static::class;
	}

	public function dump() {
		var_dump($this->export());
	}
}



class Method extends Registry {
	public function __construct()
	{
		parent::__construct([
			'get' => $_GET,
			'post' => $_POST
		]);
	}
	public function get( string $key = null )
	{
		return $this->from('get', $key);
	}

	public function post( string $key = null )
	{
		return $this->from('post', $key);
	}

	private function from( string $method, string $key = null)
	{
		if ( $key === null )
			return $this->$method;

		return $this->$method->isset($key) ? $this->$method->$key : null;
	}
}


class Stencil extends Registry {

	// Set template decorator/parser
	//private string $decorator = 'Decoration';

	public function decoration( string $file ) : Decoration
	{
		return new Decoration( $file );
	}

	public function print( string $file )
	{
		return $this->decoration( $file )->print();
	}

	public function output( string $file )
	{
		return $this->decoration( $file )->output();
	}/*

	public function setDecorator( string $name )
	{
		if ( ! is_callable( $name ) and is_subclass_of('Decorator', $name))
			throw new Exception(__CLASS__.": $name is not a valid decoration class");
			
		$this->decorator = $name;
	}*/

}


class Folder extends Registry {
	protected function isValidItem( $item ) : Bool
	{
		return is_string( $item );
	}

	protected function processItem( $item ) : Dossier
	{
		return new Pathway( $item );
	}
}





/*
 *						FACADES
 *
 *************************************************************/

trait Facade
{
	protected static $instance;

	protected function __construct() {}
	protected function __clone() {}

	public function __wakeup() {
		throw new Exception("Cannot unserialize singleton", 1);
	}

	protected static function createInstance() : Registry
	{
		return new Registry;
	}

	final public static function instance( $arguments ) : Registry
	{
		if ( ! self::$instance ) {
			self::$instance = self::createInstance( ...$arguments );

			self::init( self::$instance );
		}

		return self::$instance;
	}

	protected static function init() : void {}

	public static function __callStatic( string $method, array $arguments )
	{
		return call_user_func_array([self::instance($arguments), $method], $arguments);
	}
}


class Config {
	use Facade;

	protected static function init() : void
	{
		$configPath = Path::get('config')->file(static::class.'.ini');
		var_dump(
			parse_ini_file($configPath->realpath(), true)
		);

		if ( $configPath )
			self::instance()->import(
				parse_ini_file($configPath->string(), true) ?: []
			);
	}
}


class Path {
	use Facade;

	public function createInstance() : Folder
	{
		return new Folder;
	}

	public function init( $self )
	{
		foreach( // Attempt to load default path's
			[
				'root'     => '.',
				'App'      => 'App',
				'template' => 'App/Template',
				'Config'   => 'App/Config'
			] as $name => $path
		)

		try {
			$self->set($name, $path);
		}

		catch ( Exception $e ) {
			log("Path: Could not set default $path path");
		}
	}
}



class Template {
	use Facade;

	public function createInstance() : Stencil
	{
		return new Stencil;
	}
}



class Route {
	use Facade;

	public function createInstance() : Router
	{
		return new Router;
	}
}

class Request {
	use Facade;

	public function createInstance() : Method
	{
		return new Method;
	}
}


class Sanitize {
	use Facade;

	public function createInstance( $item ) : Strip
	{
		return new Strip( $item );
	}
}




/*
 *						TODO
 *
 *************************************************************/

class Database {}

trait Table {} // database table wrapper


class Output {}

class Url {}