<?php
/*
Module Name: Bildoptimierung beim Upload
Module URI: http://playground.ebiene.de/wordpress-ysmushit-simple/
Description: Reduzierung der hochgeladenen Bilder in der Größe ohne Qualitätsverluste. [Backend]
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
function sm_optimize_image($data) {
	/* Upload-Ordner */
	$upload = wp_upload_dir();
	
	/* Original + WP-BugFix */
	$files = array(
		ltrim(
			str_replace(
				$upload['subdir'],
				'',
				'/' .$data['file']
			),
			'/'
		)
	);
	
	/* Thumbs hinzufügen */
	if ( !empty($data['sizes']) ) {
		foreach( $data['sizes'] as $size ) {
			array_push(
				$files,
				$size['file']
			);
		}
	}
	
	/* Files loopen */
	foreach ($files as $file) {
		/* URL erfragen */
		$response = wp_remote_get(
			sprintf(
				'http://www.smushit.com/ysmush.it/ws.php?img=%s',
				urlencode($upload['url']. '/' .$file)
			)
		);
		
		/* Fehler? */
		if ( is_wp_error($response) ) {
			return $data;
		}
		
		/* Dekodieren */
		$ysmush = json_decode(
			wp_remote_retrieve_body($response)
		);
		
		/* Überschreiben */
		if ($ysmush && !empty($ysmush->dest)) {
			@file_put_contents(
				$upload['path']. '/' .$file,
		    	@file_get_contents(
		      		urldecode($ysmush->dest)
		    	)
		  	);
		}
	}
	
	return $data;
}


add_filter(
  'wp_generate_attachment_metadata',
  'sm_optimize_image'
);