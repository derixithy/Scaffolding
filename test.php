<?php

include 'app.php';


App::hook('test', function() {
	echo 'Success!';
});

App::fire('test');

App::register();

echo Path::get('library');

Path::set('public', 'Public');

$string = Sanitize::string( 'html<span>someth8ing</span>');
echo $string;

echo " | ".$string->xss();

// default permission, changes profile, own groups.
// editor role for group.
// see post [guest, user, friend, admin]

$users = ['read', 'write', 'create', 'delete'];
$read   = bindec('0001');
$edit   = bindec('0010'); $write = $edit;
$create = bindec('0100');
$delete = bindec('1000');


$user = bindec('0101'); // read create


class Can {
	static function do( $compare, $value ) {
		return $compare & $value;
	}

	static function not( $compare, $value ) {
		return ! self::do( $compare, $value );
	}
}

function show(...$arguments) {
	foreach( $arguments as $argument )
		echo "$argument <br />";
}

if ( Can::do( $user, $read ) ) show("Can read");
if ( Can::do( $user, $write ) ) show("Can write");
if ( Can::do( $user, $create ) ) show("Can create");
if ( Can::do( $user, $delete ) ) show("Can delete");
if ( Can::not( $user, $delete ) ) show("Can not delete");
if ( Can::do( $user, $read | $write ) ) show("Can read or write");

