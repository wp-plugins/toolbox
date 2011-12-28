<?php
/*
Module Name: Filterung des Pingback-Headers
Description: Eliminierung des von WordPress gesendeten Pingback-Headers "X-Pingback". [Frontend]
Author: Sergej Müller
Author URI: http://ebiene.de
*/


/* Sicherheitsabfrage */
if ( !class_exists('Toolbox') ) {
	header('Status: 403 Forbidden');
	header('HTTP/1.1 403 Forbidden');
	exit();
}


/* Ab hier kann's los gehen */
add_filter(
	'wp_headers',
	create_function(
		'$h',
		'unset($h["X-Pingback"]); return $h;'
	)
);