<?php

class Decoration extends Decorator {
	protected string $file;
	protected string $path;

	public function __construct( string $name )
	{
		$this->file = Path::get('root').$name.'.phtml';

		if ( ! file_exists( $this->file ) )
			throw new Exception("Decoration: Error loading template file ($name)");
	}

	public function asset( $filename )
	{
		return Url::get('asset/'.$filename);
		// Route::url();
	}

	public function css( $filename )
	{
		return Url::get('css/'.$filename.'.css');
	}

	public function js( $filename )
	{
		return Url::get('js/'.$filename.'.js');
	}

	public function font( $filename )
	{
		return Url::get('font/'.$filename);
	}
}