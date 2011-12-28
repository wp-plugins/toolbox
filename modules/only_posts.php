<?php
/*
Module Name: Begrenzung der Suche auf Beiträge
Description: Beschränkung der Blogsuche ausschließlich auf veröffentlichte Artikel. [Frontend]
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
	'pre_get_posts',
	function($query) {
		if ( $query->is_main_query() && $query->is_search ) {
			$query->set('post_type', 'post');
		}

		return $query;
	}
);