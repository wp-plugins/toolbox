<?php
/*
Module Name: Verlinkung der Artikel via Shortcode
Module URI: http://playground.ebiene.de/wordpress-shortcode-links/
Description: Zukunftssichere Verlinkung der Artikel untereinander mithilfe der PostID. [Frontend]
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
add_shortcode(
	'post',
	function($atts, $data) {
		return sprintf(
			'<a href="%s" title="%s">%s</a>',
			get_permalink($atts['id']),
			esc_attr(get_the_title($atts['id'])),
			$data
		);
	}
);