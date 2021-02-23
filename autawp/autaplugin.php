<?php
namespace AutaWP;

class AutaPlugin {
	public static $pluginName="Auta plugin";
	public static $prefix="mauta_";
	private static $customPost;
	public static $customPostType="mauta";
	public static $textDomain="mauta-plugin";	
	private static $mainPath="";
        
    function mLoadClass($class) {	
		if (strpos($class,"AutaWP")!==0) return;
		$path=MAUTA_PLUGIN_PATH.str_replace("\\","/",strtolower("$class.php"));		
        require($path);
    }

	
	public function __construct() {			
        spl_autoload_register([$this,"mLoadClass"]);
		register_activation_hook( __FILE__, __NAMESPACE__ . '\\AutaPlugin::auta_plugin_install' );
		add_action( 'admin_menu', __NAMESPACE__ . '\\AutaPlugin::mauta_post_actions_menu' ); 
		AutaPlugin::$customPost=new AutaCustomPost(AutaPlugin::$customPostType); 								
	}
	function initWP() {
		add_action( 'admin_enqueue_scripts', [$this,'mautaEnqueueStyle'], 11);
	}
	function mautaEnqueueStyle() {				
		$mStyles=[
			 'mauta' => ['src' => plugin_dir_url( __FILE__ ) . 'mauta.css']			 
		];
		
		foreach ($mStyles as $key => $value) {
			$src = (isset($value["src"])) ? $value["src"] : $value["srcCdn"];
			$key = 'autawp-' . $key;
			wp_register_style($key, $src);
			wp_enqueue_style($key);
		}
	}

	public static function getTable($tab) {
	  global $wpdb;	
	  if ($tab=="main") return $wpdb->prefix.AutaPlugin::$prefix."plugin_main";
	  if ($tab=="fields") return $wpdb->prefix.AutaPlugin::$prefix."fields";
	  if ($tab=="ajax") return $wpdb->prefix."majax_fields";
	}
	function auta_plugin_install() {
		global $wpdb;	
		echo "installing..";
		$table_name = AutaPlugin::getTable("main"); 
		
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
		  id mediumint(9) NOT NULL AUTO_INCREMENT,
		  time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		  name tinytext NOT NULL,
		  text text NOT NULL,
		  url varchar(55) DEFAULT '' NOT NULL,
		  PRIMARY KEY  (id)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
		
		$welcome_name = 'Mr. WordPress';
		$welcome_text = 'Congratulations, you just completed the installation!';
		
		AutaPlugin::$customPost->autaFields->makeTable("fields");

		$wpdb->insert( 
			$table_name, 
			array( 
				'time' => current_time( 'mysql' ), 
				'name' => $welcome_name, 
				'text' => $welcome_text, 
			) 
		);		
		AutaPlugin::$customPost->autaFields->saveFields("fields");
	}
	 
	function mauta_post_actions_menu() {    
		//adds menu item
		$page_title = AutaPlugin::$pluginName.' - settings';   
		$menu_title = AutaPlugin::$pluginName.'';   
		$capability = 'manage_options';   
		$menu_slug  = 'mauta-plugin-settings';   
		$function   = 'AutaWP\AutaPlugin::mauta_plugin_actions_page';   
		$icon_url   = 'dashicons-media-code';   
		$position   = 5;    
		add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position ); 
	}

	function mauta_plugin_actions_page() {
	  //renders menu actions & settings page in backend
	  ?>
	  <h1>Pluing settings and actions below</h1>
	  <?php
	  $setUrl = [
					["recreate",add_query_arg( 'do', 'recreate'),"remove all"],
					["refresh",add_query_arg( 'do', 'refresh'),"not implemented"],				
					["ajax frontend",add_query_arg( 'do', 'ajax'),"populate fields for ajax frontend filtering"],		
					["gen min max",add_query_arg( 'do', 'minmax'),"generate min/max of current rows for frontend filtering"]		
				];
	  ?>
	  <ul>
	  <?php	 
	  foreach ($setUrl as $s) { 
	  ?>
		  <li><a href='<?= $s[1]?>'><?= $s[0]?></a><br /><?= $s[2]?></li>		  		  
	  <?php
	  }
	  ?>
	  </ul>
	  <?php	  
	  $do=filter_input( INPUT_GET, "do", FILTER_SANITIZE_STRING );
	  AutaPlugin::$customPost->autaFields->procEdit();
	  AutaPlugin::$customPost->autaFields->printNewField();
	  AutaPlugin::$customPost->autaFields->printFields();	  
	  if ($do=="recreate") {		    
		AutaPlugin::$customPost->autaFields->makeTable("fields");
		AutaPlugin::$customPost->autaFields->saveFields("fields");
	  }	  
	  if ($do=="ajax") {	
		AutaPlugin::$customPost->autaFields->makeTable("ajax");
		AutaPlugin::$customPost->autaFields->saveFields("ajax");
	  }	
	  if ($do=="minmax") {	
		AutaPlugin::$customPost->autaFields->initMinMax();
		AutaPlugin::$customPost->autaFields->saveFields("ajax");
	  }	
	}
	static function logWrite($val) {
	 file_put_contents(plugin_dir_path( __FILE__ ) . "log.txt",date("d-m-Y h:i:s")." ".$val."\n",FILE_APPEND | LOCK_EX);
	}
}
