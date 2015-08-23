<?php

error_reporting(E_ALL);

include 'lib/lib.php';

function __autoload($class)
{
	$file_path = 'lib/'.$class.'.php';

	if (file_exists($file_path) == false) {
		$file_path = 'src/'.$class.'.php';
	}

	require_once $file_path;
}


$route = new Route;

$route->addClassResource(
	[
		'/Logs'
	]
);


$route->submit();