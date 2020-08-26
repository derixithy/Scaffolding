<?php


class Strip extends Dossier {
	protected string $original;

	public function __construct( $item )
	{
		parent::__construct( $item );

		$this->original = $this->item;
	}

	public function raw()
	{
		return $this->original;
	}

	public function xss( $item = null)
	{
		if ( ! $item )
			$item = $this->item;
	
		return htmlspecialchars(
			strip_tags( $item )
		);
	}
}