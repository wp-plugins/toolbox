<?php
/*
Module Name: Optimierung der Datenbanktabellen
Description: Wöchentliche Anwendung des MySQL-Befehls OPTIMIZE TABLE auf Tabellen. [Backend]
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
function sm_optimize_db() {
	/* Global */
	global $wpdb;
	
	/* Tabellen */
	$tables = $wpdb->tables();
	
	/* Loop */
	foreach ( $tables as $table ) {
		$wpdb->query("OPTIMIZE TABLE `$table`");
	}
}

/* Cronjob */
if ( ! get_transient('sm_optimized_db') ) {
	set_transient(
	   'sm_optimized_db',
	   'ilovesweta',
	   60 * 60 * 24 * 7 // 1 Woche
	 );
	 
	add_action(
		'admin_init',
		'sm_optimize_db'
	);
}