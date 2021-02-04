<?php
namespace AutaWP;

class AutaField {
 //private $thisPostCustom;
 public function __construct($name,$type="",$title="",$options="",$postType="",$compare="",$filterorder="",$displayorder="") {
	  $this->name=$name;	 
	  $this->type=$type;	
	  $this->id=AutaPlugin::$prefix.$name;
	  $this->title=__($title);	 
	  $this->options=$options;	  
	  $this->customPostType=$postType;		 
	  $this->compare=$compare;
	  $this->filterorder=$filterorder;
	  $this->displayorder=$displayorder;
 } 
 public function addMetaBox($val) {
	$this->val=$val;
	add_meta_box("postfunctiondiv{$this->name}", $this->title, [$this,'mauta_metabox_html'], 'mauta', 'side', 'high');  
 }
 function mauta_metabox_html() 	{				
		$val = isset($this->val)?$this->val:'';	
		//echo "aaaval:".$this->val;
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
		if ($val=="on" || $val=="1") $checked="checked";			
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
	 <form action='<?= remove_query_arg( 'do')?>' method='post' class='editFieldRow'>	 
	 <table class="widefat" cellspacing="0">
		<tr>		 
			<td><label>name</label><input type='text' readonly='true' name='name' value='<?= $this->name?>' /></td>
			<td><label>type</label><input type='text' name='type' value='<?= $this->type?>' /></td>
			<td><label>compare</label><input type='text' name='compare' value='<?= $this->compare?>' /></td>
			<td><label>options (split with ;)</label><input type='text' name='options' value='<?= $this->options?>' /></td>
			<td><label>title</label><input type='text' name='title' value='<?= $this->title?>' /></td>	
			<td><label>filterorder</label><input type='text' name='filterorder' value='<?= $this->filterorder?>' /></td>	
			<td><label>displayorder</label><input type='text' name='displayorder' value='<?= $this->displayorder?>' /></td>	
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
	$query = "INSERT INTO `{$tableName}` ( `name`, `value`, `type`, `title`, `compare`, `valMin`, `valMax`, `postType`, `filterorder`, `displayorder`) 
		VALUES ('{$this->name}', '{$this->value}', '{$this->type}', '{$this->title}', '{$this->compare}', '{$this->valMin}', '{$this->valMax}', '{$this->customPostType}', '{$this->filterorder}', '{$this->displayorder}');";   
	$result = $wpdb->get_results($query);	 
	return "<br />{$this->name} saved $query";
   }
 }
}
