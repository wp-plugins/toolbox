<?php
/*
Module Name: Verschlankung der Admin Bar
Module URI: https://plus.google.com/110569673423509816572/posts/eFtCpa2AH1L
Description: Entfernung ungewünschter Menüeinträge aus der oberen Admin Bar. [Backend]
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
function customize_admin_bar() {
	global $wp_admin_bar;
	
	if ( !is_admin_bar_showing() ) {
		return;
	}
	
	$wp_admin_bar->remove_menu('wp-logo');
	$wp_admin_bar->remove_menu('comments');
	$wp_admin_bar->remove_menu('appearance');
	$wp_admin_bar->remove_menu('view-site');
	$wp_admin_bar->remove_menu('new-content');
	$wp_admin_bar->remove_menu('my-account');
}

add_action(
	'wp_before_admin_bar_render',
	'customize_admin_bar'
);