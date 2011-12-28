<?php
/*
Plugin Name: Toolbox
Plugin URI: http://playground.ebiene.de/toolbox-wordpress-plugin/
Description: Ersetzt oder erweitert die <em>functions.php</em> im Theme, indem Code-Schnipsel als Toolbox-Module angelegt und direkt im Plugin verwaltet werden.
Author: Sergej Müller
Author URI: http://wpseo.de
Version: 0.1
*/


/* Sicherheitsabfrage */
if ( !class_exists('WP') ) {
	header('Status: 403 Forbidden');
	header('HTTP/1.1 403 Forbidden');
	exit();
}


/**
* Toolbox
*/

final class Toolbox {
	
	
	/* Init */
	private static $plugin_path;
	private static $modules_path;
	

	/**
	* Konstruktor der Klasse
	*
	* @since   0.1
	* @change  0.1
	*/

  	public static function init()
  	{
		/* Optionen */
		$options = get_option('toolbox');
		
		/* Sicherheit */
		if ( !empty($options['secure']) ) {
			if ( (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) or (defined('DOING_CRON') && DOING_CRON) or (defined('DOING_AJAX') && DOING_AJAX) or (defined('XMLRPC_REQUEST') && XMLRPC_REQUEST) ) {
				return;
			}
		}
		
		/* Init */
		self::$plugin_path = plugin_basename(__FILE__);
		self::$modules_path = plugin_dir_path(__FILE__). 'modules/';
		
		/* Module einbinden */
		self::_require_modules($options);
		
		/* Backend */
		if ( is_admin() ) {
			add_action(
				'wpmu_new_blog',
				array(
					__CLASS__,
					'install_later'
				)
			);
			add_action(
				'delete_blog',
				array(
					__CLASS__,
					'uninstall_later'
				)
			);
			add_action(
				'admin_init',
				array(
					__CLASS__,
					'register_settings'
				)
			);
			add_action(
				'admin_menu',
				array(
					__CLASS__,
					'add_page'
				)
			);
			
			add_filter(
				'plugin_row_meta',
				array(
					__CLASS__,
					'row_meta'
				),
				10,
				2
			);
			add_filter(
				'plugin_action_links_' .self::$plugin_path,
				array(
					__CLASS__,
					'action_links'
				)
			);
		}
	}
	
	
	/**
	* Installation des Plugins auch für MU-Blogs
	*
	* @since   0.1
	* @change  0.1
	*/

	public static function install()
	{
		/* Multisite & Network */
		if ( is_multisite() && !empty($_GET['networkwide']) ) {
			/* Blog-IDs */
			$ids = self::_get_blog_ids();

			/* Loopen */
			foreach ($ids as $id) {
				switch_to_blog( (int)$id );
				self::_install_backend();
			}

			/* Wechsel zurück */
			restore_current_blog();

		} else {
			self::_install_backend();
		}
	}


	/**
	* Installation des Plugins bei einem neuen MU-Blog
	*
	* @since   0.1
	* @change  0.1
	*/

	public static function install_later($id)
	{
		/* Kein Netzwerk-Plugin */
		if ( !is_plugin_active_for_network(self::$plugin_path) ) {
			return;
		}

		/* Wechsel */
		switch_to_blog( (int)$id );

		/* Installieren */
		self::_install_backend();

		/* Wechsel zurück */
		restore_current_blog();
	}


	/**
	* Eigentliche Installation der Option und der Tabelle
	*
	* @since   0.1
	* @change  0.1
	*/

	private static function _install_backend()
	{
		add_option(
			'toolbox',
			array(
				'modules' => array(),
				'secure'  => 1
			)
		);
	}


	/**
	* Uninstallation des Plugins pro MU-Blog
	*
	* @since   0.1
	* @change  0.1
	*/

	public static function uninstall()
	{
		/* Global */
		global $wpdb;

		/* Multisite & Network */
		if ( is_multisite() && !empty($_GET['networkwide']) ) {
			/* Alter Blog */
			$old = $wpdb->blogid;

			/* Blog-IDs */
			$ids = self::_get_blog_ids();

			/* Loopen */
			foreach ($ids as $id) {
				switch_to_blog($id);
				self::_uninstall_backend();
			}

			/* Wechsel zurück */
			switch_to_blog($old);
		} else {
			self::_uninstall_backend();
		}
	}


	/**
	* Uninstallation des Plugins bei MU & Network-Plugin
	*
	* @since   0.1
	* @change  0.1
	*/

	public static function uninstall_later($id)
	{
		/* Kein Netzwerk-Plugin */
		if ( !is_plugin_active_for_network(self::$plugin_path) ) {
			return;
		}

		/* Wechsel */
		switch_to_blog( (int)$id );

		/* Installieren */
		self::_uninstall_backend();

		/* Wechsel zurück */
		restore_current_blog();
	}


	/**
	* Eigentliche Deinstallation des Plugins
	*
	* @since   0.1
	* @change  0.1
	*/

	private static function _uninstall_backend()
	{
		delete_option('toolbox');
	}
	
	
	/**
	* Rückgabe der IDs installierter Blogs
	*
	* @since   0.1
	* @change  0.1
	*
	* @return  array  Blog-IDs
	*/
	
	private static function _get_blog_ids()
	{
		/* Global */
		global $wpdb;
		
		return $wpdb->get_col(
			$wpdb->prepare("SELECT blog_id FROM `$wpdb->blogs`")
		);
	}
	
	
	/**
	* Hinzufügen der Action-Links (Einstellungen links)
	*
	* @since   0.1
	* @change  0.1
	*/

	public static function action_links($data)
	{
		/* Rechte */
		if ( !current_user_can('manage_options') ) {
			return $data;
		}

		return array_merge(
			$data,
			array(
				sprintf(
					'<a href="%s">%s</a>',
					add_query_arg(
						array(
							'page' => 'toolbox'
						),
						admin_url('options-general.php')
					),
					__('Settings')
				)
			)
		);
	}


	/**
	* Meta-Links zum Plugin
	*
	* @since   0.1
	* @change  0.1
	*
	* @param   array   $data  Bereits vorhandene Links
	* @param   string  $page  Aktuelle Seite
	* @return  array   $data  Modifizierte Links
	*/

	public static function row_meta($data, $page)
	{
		/* Rechte */
		if ( $page != self::$plugin_path ) {
			return $data;
		}
		
		return array_merge(
			$data,
			array(
				'<a href="http://flattr.com/profile/sergej.mueller" target="_blank">Plugin flattern</a>',
				'<a href="https://plus.google.com/110569673423509816572" target="_blank">Auf Google+ folgen</a>'
			)
		);
	}
	
	
	/**
	* Filtert Module je nach Bereich
	*
	* @since   0.1
	* @change  0.1
	*
	* @param   array  $modules  Alle Module
	* @return  array  $modules  Gefilterte Module
	*/
	
	private static function _filter_modules($modules)
	{
		if ( is_admin() ) {
			return array_filter(
				$modules,
				create_function('$o', 'return $o == 1 or $o == 3;')
			);
		} else {
			return array_filter(
				$modules,
				create_function('$o', 'return $o == 2 or $o == 3;')
			);
		}
	}
	
	
	/**
	* Lädt Module je nach Bereich nach
	*
	* @since   0.1
	* @change  0.1
	*
	* @param   array  $options  Plugin-Optionen
	*/
	
	private static function _require_modules($options)
	{
		/* Leer */
		if ( empty($options['modules']) ) {
			return;
		}
		
		/* Module filtern */
		if ( ! $modules = self::_filter_modules($options['modules']) ) {
			return;
		}

		/* Module loopen */
		foreach ( $modules as $module => $time) {
			$file = self::$modules_path . self::_clean_module($module);
			
			if ( is_readable($file) ) {
				require_once($file);
			} else {
				unset($options['modules'][$module]);
			}
		}
		
		/* Update */
		if ( count($modules) != count($options['modules']) ) {
			update_option('toolbox', $options);
		}
	}
	
	
	/**
	* Bereinigt den Modulnamen
	*
	* @since   0.1
	* @change  0.1
	*
	* @param   string  $str  Unbehandelter Name
	* @return  string  $str  Bereinigter Name
	*/
	
	private static function _clean_module($str)
	{
		return (string)preg_replace('/[^a-z0-9-_\.]/i', '', $str );
	}
	
	
	/**
	* Gibt verfügbare Module zurück
	*
	* @since   0.1
	* @change  0.1
	*
	* @return  array  $modules  Array mit gefundenen Modulen
	*/
	
	private static function _list_modules()
	{
		/* Auslesen */
		$files = glob(self::$modules_path. '*.php', GLOB_NOSORT|GLOB_ERR);
		
		/* Leer */
		if ( empty($files) ) {
			return false;
		}
		
		/* Init */
		$modules = array();
		
		/* Loopen */
		foreach ($files as $file) {
			/* Modul */
			$module = str_replace(self::$modules_path, '', $file);
			
			/* Sicherheitsabgleich */
			if ( $module == self::_clean_module($module) ) {
				/* Metadaten */
				$meta = self::_read_metadata($file);
				
				$modules[] = array(
					'ident' => $module,
					'name'  => ( empty($meta['name']) ? $module : $meta['name'] ),
					'desc'  => ( empty($meta['desc']) ? '' : $meta['desc'] ),
					'link'  => ( empty($meta['link']) ? '' : '[<a href="' .esc_url($meta['link']). '" target="_blank">?</a>]' )
				);
			}
		}
		
		return $modules;
	}
	
	
	/**
	* Liest Metadaten eines Moduls ein
	*
	* @since   0.1
	* @change  0.1
	*
	* @param   string  $file  Moduldatei samt Pfad
	* @return  string         Ermittelter Modulname
	*/
	
	private static function _read_metadata($file)
	{
		return get_file_data( $file, array('name' => 'Module Name', 'desc' => 'Description', 'link' => 'Module URI') );
	}
	
	
	/**
	* Bereitet Module zum Speichern vor
	*
	* @since   0.1
	* @change  0.1
	*
	* @param   array  $modules  Array mit Modulen
	* @param   array  $status   Array mit Status-Codes
	* @return  array  $output   Zusammengebautes Array mit Modulen
	*/
	
	private static function _prepare_modules($modules, $status)
	{
		/* Leer */
		if ( empty($modules) or empty($status) or count($modules) != count($status) ) {
			return array();
		}
		
		$output = array();
		
		foreach ($modules as $key => $module) {
			if ( $module == self::_clean_module($module) ) {
				$output[$module] = $status[$key];
			}
		}

		return $output;
	}
	

	/**
	* Registrierung der Optionsseite
	*
	* @since   0.1
	* @change  0.1
	*/

	public static function add_page()
	{
		/* Anlegen */
		$page = add_options_page(
			'Toolbox',
			'<img src="' .plugins_url('toolbox/img/icon.png'). '" width="11" height="9" border="0" alt="Toolbox" />Toolbox',
			'manage_options',
			'toolbox',
			array(
				__CLASS__,
				'options_page'
			)
		);
		
		/* CSS */
		add_action(
			'admin_print_styles-' .$page,
			array(
				__CLASS__,
				'add_css'
			)
		);
		
		/* Hilfe */
		add_action(
			'load-' .$page,
			array(
				__CLASS__,
				'add_help'
			)
		);
	}
	
	
	/**
	* Einbindung von CSS
	*
	* @since   0.1
	* @change  0.1
	*/

	public static function add_css()
	{
		/* Infos auslesen */
		$data = get_plugin_data(__FILE__);
		
		/* CSS registrieren */
		wp_register_style(
			'toolbox_css',
			plugins_url('css/style.css', __FILE__),
			array(),
			$data['Version']
		);

		/* CSS einbinden */
		wp_enqueue_style('toolbox_css');
	}
	
	
	/**
	* Hilfe-Tab oben rechts
	*
	* @since   0.1
	* @change  0.1
	*/
	
	public static function add_help()
	{
		/* Screen */
		$screen = get_current_screen();
		
		/* Tabs */
		$screen->add_help_tab(
			array(
				'id'	  => 'toolbox_modules',
				'title'	  => 'Module',
				'content' => '<p>Die Kachel listet verfügbare Toolbox-Module auf, welche im Plugin-Ordner <em>modules</em> als PHP-Dateien abgelegt sind. Einzelne Module können unterschiedliche Zustände besitzen:</p>'.
							 '<ul>'.
							 	'<li><em>Deaktiviert</em><br />'.
							 	'Setzt das Modul auf inaktiv. Die Datei lädt nie. Standardeinstellung.</li>'.
							 	
							 	'<li><em>Laden nur im Backend</em><br />'.
							 	'Toolbox bindet das Modul ausschliesslich im Administrationsbereich ein. Nicht im Blog-Frontend.</li>'.
							 	
							 	'<li><em>Laden nur im Frontend</em><br />'.
							 	'Die Ausführung des Moduls erfolgt ausnahmsweise im Blog-Frontend. Das Backend ist davon ausgeschlossen.</li>'.
							 	
							 	'<li><em>Laden im Back- und Frontend</em><br />'.
							 	'Das Modul ist aktiv auf allen Blogseiten. Im Admin und Blogseiten.</li>'.
							 '</ul>'
			)
		);
		$screen->add_help_tab(
			array(
				'id'	  => 'toolbox_settings',
				'title'	  => 'Einstellungen',
				'content' => '<p><strong>Sicherheitsmodus aktiv</strong><br />'.
							 'Im aktiven Zustand verhindert die Option eine Ausführung der Module in folgenden Fällen und ist aus Sicherheitsgründen standardmässig eingeschaltet:</p>'.
							 '<ul>'.
							 	'<li>Cronjobs</li>'.
							 	'<li>AJAX-Anfragen</li>'.
							 	'<li>Autospeicherung</li>'.
							 	'<li>XMLRPC-Anfragen</li>'.
							 '</ul>'
			)
		);
		$screen->add_help_tab(
			array(
				'id'	  => 'toolbox_manual',
				'title'	  => 'Dokumentation',
				'content' => '<p>Ausführliche Dokumentation für das Toolbox-Plugin online verfügbar:</p>'.
							 '<p><a href="http://playground.ebiene.de/toolbox-wordpress-plugin/" target="_blank">http://playground.ebiene.de/toolbox-wordpress-plugin/</a></p>'
			)
		);
		
		/* Sidebar */
		$screen->set_help_sidebar(
			'<p><strong>Mehr zum Autor</strong></p>'.
			'<p><a href="https://plus.google.com/110569673423509816572/" target="_blank">Google+</a></p>'.
			'<p><a href="http://ebiene.de" target="_blank">Portfolio</a></p>'.
			'<p><a href="http://flattr.com/profile/sergej.mueller" target="_blank">Flattr</a></p>'
		);
	}
	
	
	/**
	* Registrierung der Optionen
	*
	* @since   0.1
	* @change  0.1
	*/

	public static function register_settings()
	{
		register_setting(
			'toolbox',
			'toolbox',
			array(
				__CLASS__,
				'validate_options'
			)
		);
	}


	/**
	* Validierung und Speicherung der Optionen
	*
	* @since   0.1
	* @change  0.1
	*
	* @param   array  $data  Array mit Formularwerten
	* @return  array         Array mit geprüften Werten
	*/

	public static function validate_options($data)
	{	
		return array(
			'secure'  => (int)(!empty($data['secure'])),
			'modules' => self::_prepare_modules( (array)$data['modules'], (array)$data['status'] )
		);
	}


	/**
	* Darstellung der Optionsseite
	*
	* @since   0.1
	* @change  0.1
	*/

	public static function options_page()
	{ ?>
		<div class="wrap" id="toolbox_main">
			<?php screen_icon('toolbox') ?>

			<h2>
				Toolbox
			</h2>

			<form method="post" action="options.php">
				<?php settings_fields('toolbox') ?>

				<?php $options = get_option('toolbox') ?>
				
				<div class="table rounded">
					<table class="form-table">
						<caption class="rounded">Module</caption>
						
						<?php if ( $modules = self::_list_modules() ) {
							foreach ($modules as $module) { ?>
								<tr>
									<th>
										<?php echo esc_html($module['name']) ?>
										<small><?php echo esc_html($module['desc']) ?> <?php echo $module['link'] ?></small>
										<input type="hidden" name="toolbox[modules][]" value="<?php echo esc_attr($module['ident']) ?>" />
									</th>
									<td>
										<select name="toolbox[status][]">
											<?php foreach ( array('Deaktiviert', 'Laden nur im Backend', 'Laden nur im Frontend', 'Laden im Back- und Frontend') as $k => $v ) { ?>
												<option value="<?php echo esc_attr($k) ?>" <?php selected(@$options['modules'][$module['ident']], $k) ?>><?php echo esc_html($v) ?></option>
											<?php } ?>
										</select>
									</td>
								</tr>
							<?php }
						} ?>
					</table>
				</div>
				
				<div class="table rounded">
					<table class="form-table">
						<caption class="rounded">Einstellungen</caption>
						
						<tr>
							<th>
								Sicherheitsmodus aktiv
							</th>
							<td>
								<input type="checkbox" name="toolbox[secure]" value="1" <?php checked('1', $options['secure']); ?> />
							</td>
						</tr>
					</table>
				</div>
				
				<p class="submit">
					<span class="help">Beachte die Hilfe<br />oben rechts</span>
					<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
				</p>
			</form>
		</div><?php
	}
}


/* Fire */
add_action(
	'plugins_loaded',
	array(
		'Toolbox',
		'init'
	),
	99
);

/* Install */
register_activation_hook(
	__FILE__,
	array(
		'Toolbox',
		'install'
	)
);

/* Uninstall */
register_uninstall_hook(
	__FILE__,
	array(
		'Toolbox',
		'uninstall'
	)
);