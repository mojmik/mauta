<?php
namespace AutaWP;

class AutaFields {
	public $fieldsList=array();
	private $postCustomFields;	
	public $customPostType;
	public function __construct($postType) {					
		add_action( 'add_meta_boxes_'.$postType, [$this,'mauta_metaboxes'] );		
		add_action( 'save_post_'.$postType, [$this,'mauta_save_post'] ); 
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
		$tableName=AutaPlugin::getTable($tabName,$this->customPostType);
		$query = "SELECT * FROM `{$tableName}` ORDER BY `filterorder`";	
		foreach( $wpdb->get_results($query) as $key => $row) {					
			$this->fieldsList[] = $this->createField($row->name,$row->type,$row->compare,$row->title,$row->value,$row->filterorder,$row->displayorder,$row->icon,$row->fieldformat);
			$load=true;
		}	
		return $load;
	}
	public function procEdit() {
		//edit field
		$name=filter_input( INPUT_POST, "name", FILTER_SANITIZE_STRING );
		$type=filter_input( INPUT_POST, "type", FILTER_SANITIZE_STRING );
		$compare=filter_input( INPUT_POST, "compare", FILTER_SANITIZE_STRING );
		$title=filter_input( INPUT_POST, "title", FILTER_SANITIZE_STRING );
		$options=filter_input( INPUT_POST, "options", FILTER_SANITIZE_STRING );
		$filterorder=filter_input( INPUT_POST, "filterorder", FILTER_SANITIZE_STRING );
		$displayorder=filter_input( INPUT_POST, "displayorder", FILTER_SANITIZE_STRING );
		$icon=filter_input( INPUT_POST, "icon", FILTER_SANITIZE_STRING );
		$fieldformat=filter_input( INPUT_POST, "fieldformat", FILTER_SANITIZE_STRING );
		
		if (isset($_POST["editField"])) {						
			foreach ($this->fieldsList as $f) {	
			 if ($f->name == $name) {
				$f->type=$type; 
				$f->compare=$compare;
				$f->title=$title;
				$f->options=$options;
				$f->filterorder=$filterorder;
				$f->displayorder=$displayorder;
				$f->icon=$icon;
				$f->fieldformat=$fieldformat;
				$f->saveToSQL();
				echo "changed $name";
			 }
			}
		}
		
		//new field
		if (isset($_POST["newField"])) {			
				//create table if not exists
			 	$newName=AutaPlugin::$prefix.sanitize_title($title);
				$f = $this->createField($newName,$type,$compare,$title,$options,$filterorder,$displayorder,$icon,$fieldformat);
				$this->fieldsList[] = $f;
				$f->saveToSQL();				
				echo "created $name";
			 
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
 	 $out="";
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
	 <form class='editFieldRow' method='post'>
		<div><div><label>name</label></div><input disabled type='text' name='name' value='' /></div>
		<div><div><label>title</label></div><input type='text' name='title' value='' /></div>	
		<div><div><label>type</label></div><input type='text' name='type' value='' /></div>
		<div><div><label>compare filter</label></div><input type='text' name='compare' value='=' /></div>
		<div><div><label>options (split with ;)</label></div><input type='text' name='options' value='' /></div>			
		<div><input name='newField' type='hidden' value='edit' /><input type='submit' value='create' /></div>
	</form>
	 <?php
 }
	function createField($name,$type,$compare,$title,$options="",$filterorder="",$displayorder="",$icon="",$fieldformat="") {
		return new AutaField($name,$type,$title,$options,$this->customPostType,$compare,$filterorder,$displayorder,$icon,$fieldformat);
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
		  //echo "aaacust: {$f->name} ".$custom[$f->name][0];
		  $val=$custom[$f->name][0];	
		  $f->addMetaBox($val);	
		}
		
		add_meta_box("addanotheritem", __( 'Add another', 'textdomain' ), [$this,'addanother_metabox'], $this->customPostType, 'side', 'low');  
		
		//
	}	
	function addanother_metabox() {		
		$urlSave=add_query_arg( 'msave', '1');
		$urlNew="'./post-new.php?post_type=".AutaPlugin::$customPostType."'";										
		?>
		
		<button onclick='javascipt:saveAndAdd();'>Add another</a>				 
		<?php
	}
	function mauta_save_post()	{		
		if(empty($_POST)) return; //tackle trigger by add new 
		global $post;
		AutaCustomPost::sendMessageToMajax("deletecache");
		foreach ($this->fieldsList as $f) {
		  $f->saveField();	
		}			
	}   
	function saveFields($destinationTab="fields")	{		
		foreach ($this->fieldsList as $f) {			
		  $f->saveToSQL($destinationTab);	
		}			
	}   
	function makeTable($tabName="fields",$drop=false) {
		global $wpdb;
		$tableName=AutaPlugin::getTable($tabName,$this->customPostType);
		if(!$drop && $wpdb->get_var("SHOW TABLES LIKE '$tableName'") == $tableName) {
		 //table exists and not drop
		 return true;
		}		
		$wpdb->query( "DROP TABLE IF EXISTS {$tableName}");
		$charset_collate = $wpdb->get_charset_collate();
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
		  filterorder smallint,
		  displayorder smallint,
		  icon text,
		  fieldformat text,
		  PRIMARY KEY  (id)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		maybe_create_table($tableName, $sql );
		$wpdb->query("TRUNCATE TABLE `{$tableName}`");		
	}
	function initMinMax() {		
		foreach ($this->fieldsList as $f) {
		 echo "<br />".$f->name." min:".$f->getValMin();
		 echo " max:".$f->getValMax();
		}		
	}
}
