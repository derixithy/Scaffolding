<?php


class Pathway extends Dossier { // Directory already in use :(


	public function __construct( string $item )
	{
		$this->item = realpath( $item );

		if( ! $this->item )
			throw new Exception("Error Processing Pathway: $item", 1);
			
	}

	public function file( $filename )
	{
		return new File( $this->item.DS.$filename );
	}
}