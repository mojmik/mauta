<?php
   /*
   Plugin Name: mAuta plugin
   Plugin URI: http://ttj.cz
   description: Adds custom post option, adds custom fields to administration interface
  mAuta plugin
   Version: 1.2
   Author: Mik
   Author URI: http://ttj.cz
   License: GPL2
   */
   
// Include the core class.
define( 'MAUTA_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

require_once MAUTA_PLUGIN_PATH . '/AutaWP/autaplugin.php';

new AutaWP\AutaPlugin(); 


