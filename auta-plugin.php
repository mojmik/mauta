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
   
//pridat nahravani pres csv

new AutaPlugin(); 



class AutaPlugin {
	public static $pluginName="Auta plugin";
	public static $prefix="mauta_";
	private static $customPost;
	public static $customPostType="mauta";
	public static $textDomain="mauta-plugin";
	
	public function __construct() {			
		register_activation_hook( __FILE__, 'AutaPlugin::auta_plugin_install' );
		add_action( 'admin_menu', 'AutaPlugin::mauta_post_actions_menu' ); 
		AutaPlugin::$customPost=new AutaCustomPost(AutaPlugin::$customPostType); 						
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

	}
	 
	function mauta_post_actions_menu() {    
		//adds menu item
		$page_title = AutaPlugin::$pluginName.' - settings';   
		$menu_title = AutaPlugin::$pluginName.'';   
		$capability = 'manage_options';   
		$menu_slug  = 'mauta-plugin-settings';   
		$function   = 'AutaPlugin::mauta_plugin_actions_page';   
		$icon_url   = 'dashicons-media-code';   
		$position   = 4;    
		add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position ); 
	}
	function mauta_plugin_actions_page() {
	  //renders menu actions & settings page in backend
	  ?>
	  <h1>Pluing settings and actions below</h1>
	  <?php
	  $setUrl = [
					["recreate",add_query_arg( 'do', 'recreate'),"let this plugin know about its custom fields"],
					["refresh",add_query_arg( 'do', 'refresh'),"not implemented"],				
					["ajax frontend",add_query_arg( 'do', 'ajax'),"populate fields for ajax frontend filtering"],
					["csv import",add_query_arg( 'do', 'csv'),"import csv file"]
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
	  AutaPlugin::$customPost->autaFields->printFields();
	  AutaPlugin::$customPost->autaFields->printNewField();
	  if ($do=="recreate") {		    
		AutaPlugin::$customPost->autaFields->makeTable("fields");
		AutaPlugin::$customPost->autaFields->saveFields("fields");
	  }	  
	  if ($do=="ajax") {	
		AutaPlugin::$customPost->autaFields->makeTable("ajax");
		AutaPlugin::$customPost->autaFields->saveFields("ajax");
	  }
	  if ($do=="csv") {
		$importCSV=new ImportCSV();
		$importCSV->loadCsvFile(plugin_dir_path( __FILE__ )."recsout.txt","csvtab","^","",null,true,"cp852");		  
		$importCSV->createPostsFromTable("csvtab");	
	  }
	}
	function logWrite($val) {
	 file_put_contents(plugin_dir_path( __FILE__ ) . "log.txt",date("d-m-Y h:i:s")." ".$val."\n",FILE_APPEND | LOCK_EX);
	}
}
   

class AutaCustomPost {	
	public $autaFields;
	public $customPostType;
	 public function __construct($postType="") {	
		 $this->customPostType=$postType;
		 add_action( 'init', [$this,'custom_post_type'] , 0 );
		 
		 //admin
		 add_action('admin_menu' , [$this,'add_to_admin_menu']); 
		 
		 //init custom fields
		 $this->autaFields = new AutaFields($this->customPostType);		 
		 
		 add_action( 'admin_enqueue_scripts', [$this,'mJsEnqueue'] );			 
	 }
	
	 function mJsEnqueue() {		
		wp_enqueue_script( 'autapluginjs', plugin_dir_url( __FILE__ ) . 'auta-plugin.js', array('jquery') );		
	 }

	 	
	/*
	* Creating a function to create our CPT
	*/
	function custom_post_type() {
	 $textDomain=AutaPlugin::$textDomain; //for If your theme is translation ready, and you want your custom post types to be translated, then you will need to mention text domain used by your theme.
	// Set UI labels for Custom Post Type
		$labels = array(
			'name'                => _x( 'Auta', 'Post Type General Name', $textDomain ),
			'singular_name'       => _x( 'Auto', 'Post Type Singular Name', $textDomain ),
			'menu_name'           => __( 'Auta', $textDomain ),
			'parent_item_colon'   => __( 'Nadřazené auto', $textDomain ),
			'all_items'           => __( 'Všechna auta', $textDomain ),
			'view_item'           => __( 'Zobrazit auto', $textDomain ),
			'add_new_item'        => __( 'Přidat auto', $textDomain ),
			'add_new'             => __( 'Přidat nové', $textDomain ),
			'edit_item'           => __( 'Upravovat auto', $textDomain ),
			'update_item'         => __( 'Aktualizovat auto', $textDomain ),
			'search_items'        => __( 'Hledat auto', $textDomain ),
			'not_found'           => __( 'Nenalezeno', $textDomain ),
			'not_found_in_trash'  => __( 'Nenalezeno v koši', $textDomain ),
		);
		 
	// Set other options for Custom Post Type
		 
		$args = array(
			'label'               => __( 'auta', $textDomain ),
			'description'         => __( 'Auta v nabídce', $textDomain ),
			'labels'              => $labels,
			// Features this CPT supports in Post Editor
			'supports'            => array( 'title', 'editor', 'excerpt', 'author', 'thumbnail', 'comments', 'revisions', 'custom-fields', ),
			// You can associate this CPT with a taxonomy or custom taxonomy. 
			'taxonomies'          => array( 'skupiny' ),
			/* A hierarchical CPT is like Pages and can have
			* Parent and child items. A non-hierarchical CPT
			* is like Posts.
			*/ 
			'hierarchical'        => false,
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_nav_menus'   => true,
			'show_in_admin_bar'   => true,
			'menu_position'       => 5,
			'can_export'          => true,
			'has_archive'         => true,
			'exclude_from_search' => false,
			'publicly_queryable'  => true,
			'capability_type'     => 'post',
			'show_in_rest' => true,
	 
		);
		 
		// Registering your Custom Post Type
		register_post_type( $this->customPostType, $args );
	 
	 }
	 
	function auta_menu_function() {
		echo 'ahoj auta';	
		return "neco";
	}

	 function add_to_admin_menu() {
		//add_submenu_page( string $parent_slug, string $page_title, string $menu_title, string $capability, string $menu_slug, callable $function = '', int $position = null )
		$parent_slug='edit.php?post_type='.$this->customPostType;
		$page_title='Auta admin';
		$menu_title='Auta';
		$capability='edit_posts';
		$menu_slug=basename(__FILE__);
		$function = [$this,'auta_menu_function'];
		add_submenu_page($parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function);
	} 
		
}

class AutaFields {
	public $fieldsList=array();
	private $postCustomFields;	
	public $customPostType;
	public function __construct($postType) {			
		add_action( 'add_meta_boxes_mauta', [$this,'mauta_metaboxes'] );		
		add_action( 'save_post_mauta', [$this,'mauta_save_post'] ); 
		$this->customPostType=$postType;
		$forcePrepopulate=false; //set to true for premade custom fields
		if ($forcePrepopulate) {
			$this->fieldsList[] = $this->createField(AutaPlugin::$prefix."razeni","text","=","Razeni");
			$this->fieldsList[] = $this->createField(AutaPlugin::$prefix."pohon","text","=","Pohon");
			$this->fieldsList[] = $this->createField(AutaPlugin::$prefix."skupina","text","=","Skupina");
			$this->fieldsList[] = $this->createField(AutaPlugin::$prefix."znacka","select","=","znacka",["---","Skoda","VW","Mercedes"]);
			$this->fieldsList[] = $this->createField(AutaPlugin::$prefix."cenaden","text","<","Cena - den");
			$this->fieldsList[] = $this->createField(AutaPlugin::$prefix."cenamesic","text","<","Cena - mesic");
			$this->fieldsList[] = $this->createField(AutaPlugin::$prefix."cenarok","text","<","Cena - rok");
		}
		else $this->loadFromSQL();
	}
	public function loadFromSQL($tabName="fields") {
		global $wpdb;		
		$tableName=AutaPlugin::getTable($tabName);
		$query = "SELECT * FROM `{$tableName}` ORDER BY `sortorder`";	
		foreach( $wpdb->get_results($query) as $key => $row) {		
			$this->fieldsList[] = $this->createField($row->name,$row->type,$row->compare,$row->title,$row->value,$row->sortorder);
			$load=true;
		}	
		return $load;
	}
	public function procEdit() {
		//edit field
		if (isset($_POST["editField"])) {			
			$name=filter_input( INPUT_POST, "name", FILTER_SANITIZE_STRING );
			$type=filter_input( INPUT_POST, "type", FILTER_SANITIZE_STRING );
			$compare=filter_input( INPUT_POST, "compare", FILTER_SANITIZE_STRING );
			$title=filter_input( INPUT_POST, "title", FILTER_SANITIZE_STRING );
			$options=filter_input( INPUT_POST, "options", FILTER_SANITIZE_STRING );
			$sortorder=filter_input( INPUT_POST, "sortorder", FILTER_SANITIZE_STRING );
			foreach ($this->fieldsList as $f) {	
			 if ($f->name == $name) {
				$f->type=$type; 
				$f->compare=$compare;
				$f->title=$title;
				$f->options=$options;
				$f->sortorder=$sortorder;
				$f->saveToSQL();
				echo "changed $name";
			 }
			}
		}
		
		//new field
		if (isset($_POST["newField"])) {			
			$name=filter_input( INPUT_POST, "name", FILTER_SANITIZE_STRING );
			$type=filter_input( INPUT_POST, "type", FILTER_SANITIZE_STRING );
			$compare=filter_input( INPUT_POST, "compare", FILTER_SANITIZE_STRING );
			$title=filter_input( INPUT_POST, "title", FILTER_SANITIZE_STRING );
			$options=filter_input( INPUT_POST, "options", FILTER_SANITIZE_STRING );			
			$sortorder=filter_input( INPUT_POST, "sortorder", FILTER_SANITIZE_STRING );
			 if ($name != "") {			
				$f = $this->createField(AutaPlugin::$prefix.$name,$type,$compare,$title,$options,$sortorder);
				$this->fieldsList[] = $f;
				$f->saveToSQL();				
				echo "created $name";
			 }
		}
		
		//delete field
		if (isset($_POST["deleteField"])) {			
			$name=filter_input( INPUT_POST, "name", FILTER_SANITIZE_STRING );
			foreach ($this->fieldsList as $key=>$f) {	
			 if ($f->name == $name) {
				$index=$key;				
				$f->saveToSQL("fields",1);
				break;				
			 }
			}
			//remove from array of objects
			if (isset($index)) {
				unset($this->fieldsList[$index]);				
				echo "deleted $name $key";
			}
		}
	}
	public function printFields() {
	?>
	<h2>Edit fields</h2>
	<?php
		foreach ($this->fieldsList as $f) {		  
		  $out.=$f->printFieldEdit();
		  //echo "<br />";
		}
		return $out;
    }
	 public function printNewField() {
	 ?>
	 <h2>New field</h2>
	 <form method='post'>
		<label>name</label><input type='text' name='name' value='' />
		<label>type</label><input type='text' name='type' value='' />
		<label>compare filter</label><input type='text' name='compare' value='=' />
		<label>options (split with ;)</label><input type='text' name='options' value='' />
		<label>title</label><input type='text' name='title' value='' />		
		<input name='newField' type='hidden' value='edit' />
		<input type='submit' value='create' />
	</form>
	 <?php
 }
	function createField($name,$type,$compare,$title,$options="",$sortOrder="") {
		return new AutaField($name,$type,$title,$options,$this->customPostType,$compare,$sortOrder);
	}
	function mauta_metaboxes( ) {
		global $wp_meta_boxes;
		global $post;
		/*
		https://developer.wordpress.org/reference/functions/add_meta_box/
		add_meta_box( string $id, string $title, callable $callback, string|array|WP_Screen $screen = null, string $context = 'advanced', string $priority = 'default', array $callback_args = null )
		*/
				
		$custom = get_post_custom($post->ID);
		
		foreach ($this->fieldsList as $f) {
		  $val=$custom[$f->id][0];	
		  $f->addMetaBox($val);	
		}
		
		add_meta_box("addanotheritem", __( 'Add another', 'textdomain' ), [$this,'addanother_metabox'], 'mauta', 'side', 'high');  
		
		//
	}	
	function addanother_metabox() {		
		$urlSave=add_query_arg( 'msave', '1');
		$urlNew="'./post-new.php?post_type=".AutaPlugin::$customPostType."'";								
		/*
		aria-expanded (chybi) -> aktualizovat
		
		aria-expanded="false" -> publikovat
		aria-expanded="true" -> publikovat2
		*/
		
		/*
		<script>		
		 function saveAndAdd() {
			var $butt=jQuery('button.editor-post-publish-button__button');
			
			jQuery('button.editor-post-publish-button__button').click();  
            setTimeout(function() {				
        				window.location=<?= $urlNew;?>;
        			}, 2000);	
            return false;  
		 }
		</script>
		*/
		?>
		
		<button onclick='javascipt:saveAndAdd();'>Add another</a>				 
		<?php
	}
	function mauta_save_post()	{
		if(empty($_POST)) return; //tackle trigger by add new 
		global $post;
		foreach ($this->fieldsList as $f) {
		  $f->saveField();	
		}			
	}   
	function saveFields($destinationTab="fields")	{		
		foreach ($this->fieldsList as $f) {
		  echo $f->saveToSQL($destinationTab);	
		}			
	}   
	function makeTable($tabName="fields") {
		global $wpdb;
		$tableName=AutaPlugin::getTable($tabName);
		$wpdb->query( "DROP TABLE IF EXISTS {$tableName}");
		
		$sql = "CREATE TABLE {$tableName} (
		  id mediumint(9) NOT NULL AUTO_INCREMENT,	
		  name tinytext,
		  value text,
		  title text,
		  type tinytext,
		  compare tinytext,
		  valMin text,
		  valMax text,
		  postType tinytext,
		  sortorder smallint,
		  PRIMARY KEY  (id)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		maybe_create_table($tableName, $sql );
		$wpdb->query("TRUNCATE TABLE `{$tableName}`");
		echo $tableName." created";
	}
	
}

class AutaField {
 //private $thisPostCustom;
 public function __construct($name,$type="",$title="",$options="",$postType="",$compare="",$sortorder="") {
	  $this->name=$name;	 
	  $this->type=$type;	
	  $this->id=AutaPlugin::$prefix.$name;
	  $this->title=__($title);	 
	  $this->options=$options;	  
	  $this->customPostType=$postType;		 
	  $this->compare=$compare;
	  $this->sortorder=$sortorder;
 } 
 public function addMetaBox($val) {
	$this->val=$val;
	add_meta_box("postfunctiondiv{$this->id}", $this->title, [$this,'mauta_metabox_html'], 'mauta', 'side', 'high');  
 }
 function mauta_metabox_html() 	{				
		$val = isset($this->val)?$this->val:'';	
		if ($this->type=="select") {				
		$options=explode(";",$this->options);		
		?>
		<select name="<?php echo $this->name?>">		
		<?php		
		foreach ($options as $opt) {
		$selected=($val==$opt)?"selected='selected'":"";
		?>
			<option <?= $selected?> value="<?= $opt?>"><?= $opt?></option>
		<?php
		}
		?>
		</select>		
		<?php
		}
		else if ($this->type=="bool") {
		if ($val=="on") $checked="checked";			
		?>		 
		<input type='checkbox' name="<?php echo $this->name?>" <?php echo $checked; ?> />			
		<?php 
		}
		else {?>
		<input name="<?php echo $this->name?>" value="<?php echo $this->val; ?>" />		
		<?php
		}
 }
 function saveField() {
	global $post; 
	$val=$_POST[$this->name];
	if ($this->type=="bool" && $val=="on") $val=1;
    AutaPlugin::logWrite("save: {$this->name} {$val}");	
	update_post_meta($post->ID, $this->name, $_POST[$this->name]);
 }
 public function printFieldEdit() {
	 ?>
	 <form method='post' class='editFieldRow'>
	 <table class="widefat" cellspacing="0">
		<tr>		 
			<td><label>name</label><input type='text' readonly='true' name='name' value='<?= $this->name?>' /></td>
			<td><label>type</label><input type='text' name='type' value='<?= $this->type?>' /></td>
			<td><label>compare</label><input type='text' name='compare' value='<?= $this->compare?>' /></td>
			<td><label>options (split with ;)</label><input type='text' name='options' value='<?= $this->options?>' /></td>
			<td><label>title</label><input type='text' name='title' value='<?= $this->title?>' /></td>	
			<td><label>sortorder</label><input type='text' name='sortorder' value='<?= $this->sortorder?>' /></td>	
			<td><input name='editField' type='submit' value='edit' /><input name='deleteField' type='submit' value='delete' /></td>	
		</tr>
	 </table>	
	 </form>	
	 <?php
 }
  public function saveToSQL($tabName="fields",$deleteOnly=false) {
   global $wpdb;   
   
   if (is_array($this->options)) {	   
		$this->value=implode(";",$this->options);
		$this->compare="=";
   } 	  
   else $this->value=$this->options;
   $tableName=AutaPlugin::getTable($tabName);
   $query = "DELETE FROM `{$tableName}` WHERE `name` like '{$this->name}';";   
   $result = $wpdb->get_results($query);	
   if (!$deleteOnly) {   
	$query = "INSERT INTO `{$tableName}` ( `name`, `value`, `type`, `title`, `compare`, `valMin`, `valMax`, `postType`, `sortorder`) 
		VALUES ('{$this->name}', '{$this->value}', '{$this->type}', '{$this->title}', '{$this->compare}', '{$this->valMin}', '{$this->valMax}', '{$this->customPostType}', '{$this->sortorder}');";   
	$result = $wpdb->get_results($query);	 
	return "<br />{$this->name} saved $query";
   }
 }
}

class ImportCSV {
	public $fieldsList=array();
	private $postCustomFields;	
	public $customPostType;	
    private $settings=array();	
	public function __construct() {
		/*
		$this->csvSeparator=$sep;
		$this->csvEnclosed=$enc;
		$this->fileName=$file;
		*/
		$this->settings=[		
		 "createpost" => true,
		 "createmeta" => false,
		 "createcat" => false
		];
		
		$this->mapping=[
		 "post" => [
			 "post_title" => "neco",
			 "post_content" => "neco2",
			 "post_description" => "neco3",		 
		 ],
		 "meta" => []		 
		];
		
		
	}	
	public function createPostsFromTable($table,$metas=array()) {
		global $wpdb;		

		$results = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table"));		

		foreach ($results as $r) {
			//create post
			$postArr=array();
			if ($this->settings["createpost"]) {
				echo "!!!";
				foreach ($this->mapping["post"] as $key=>$field) {
				 $postArr[$key] = $r->$field;	
				}								
				$postArr["post_status"]="publish";
				$postArr["post_type"]=AutaPlugin::$customPostType;
				print_r($postArr);
				$postId=wp_insert_post($postArr);
				
				//create metas
				if ($this->settings["createmeta"]) {					
					foreach ($this->mapping["meta"] as $key=>$field) {
					 add_post_meta($postId,$key,$r->$field);
					}					
				}
				
				if ($this->settings["createcat"]) {
					//category?
					$wpdocs_cat = array('taxonomy' => 'hp_listing_category', 'cat_name' => $nameKat, 'category_description' => $nameKat, 'category_nicename' => $slugKat, 'category_parent' => $parentKatId);	 
					$wpdocs_cat_id = wp_insert_category($wpdocs_cat,false);
				}
			}
			
			
		}

	}
	private function fgetcsvUTF8(&$handle, $length, $separator = ';',$encoding="") {
		if (($buffer = fgets($handle, $length)) !== false)    {
			$buffer = $this->autoUTF($buffer);
			return str_getcsv($buffer, $separator);
		}
		return false;	
	}
	private function autoUTF($s,$encoding="") 	{
		if ($encoding=="cp1250") return iconv('WINDOWS-1250', 'UTF-8', $s);
		if ($encoding=="cp852") return iconv('CP852', 'UTF-8', $s);
		return $s;
	}
	public function loadCsvFile($file,$table,$sep="^",$enc='"',$skipCols=null,$createTable=false,$encoding="") {
		global $wpdb;
		$fh = fopen($file, "r"); 
		$queryO="INSERT INTO `$table` SET ";
		$filesize=filesize($file);
		$radek=0;
		while ($line = $this->fgetcsvUTF8($fh, 8000, $sep)) {		
			//utf8_encode			
			
			$lineNum++;
			if ($lineNum===1) {		
			 $mCols=$line;
			 if ($createTable) $this->createTable($table,$line);
			}
			else {			 			
				$n=0;
				foreach ($line as $mVal) {
					$colName=$mCols[$n];
					//echo "<br />colN:".$colName."-".$mVal.";";
					$mRow[$colName]=$mVal;
					$n++;
				}									
				$query=$this->getInsertQueryFromArray($table,$mRow,$skipCols);
				echo "<br />$query";
				$result = $wpdb->get_results($query);
				$mInserted++;			 
			}			
				 
		}
		
	}
	function createTable($tabName,$mCols) {	
		global $wpdb;	
		$wpdb->query( "DROP TABLE IF EXISTS {$tabName}");
		
		foreach ($mCols as $mCol) {
			$cols.="`$mCol` text,";
		}
		$sql = "CREATE TABLE $tabName (
		  id mediumint(9) NOT NULL AUTO_INCREMENT,
		  $cols
		  PRIMARY KEY  (id)
		) $charset_collate;";		
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}
	function getInsertQueryFromArray($table,$mArr,$skipCols) {
	 $query="INSERT INTO `$table` SET ";
	 $n=0;
	 foreach ($mArr as $colName => $mVal) {   
	   //echo "<br />colname:$colName value:$mVal";
	   if (!is_array($skipCols) || !in_array($colName,$skipCols)) {
		 if ($n>0) $query.=",";   
		 $query.="`$colName`='$mVal'";
		 $n++;
	  }
	 }
	 return $query;
	}

	public function loadFileLoadData($file,$sep="^",$enc='"') {
		//tohle nepude, protoze to je zakazany kvuli securiyu
		global $wpdb;
		$query="LOAD DATA LOCAL INFILE '{$file}' INTO TABLE sas FIELDS TERMINATED BY '{$sep}' ENCLOSED BY '{$enc}'
						IGNORE 1 LINES (@category,@temple) SET category = @category, temple = @temple;";
		echo "<div style='position:absolute;top:100px;left:600px;'>".$query."</div>";
						
		$wpdb->query(
                $wpdb->prepare(
                        "LOAD DATA LOCAL INFILE '{$file}' INTO TABLE sas FIELDS TERMINATED BY '{$sep}' ENCLOSED BY '{$enc}' 
						IGNORE 1 LINES (@category,@temple) SET category = @category, temple = @temple;"
                )
        );
	}
}