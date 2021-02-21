<?php
namespace AutaWP;

class AutaField {
 //private $thisPostCustom;
 public function __construct($name,$type="",$title="",$options="",$postType="",$compare="",$filterorder="",$displayorder="",$icon="") {
	  $this->name=$name;	 
	  $this->type=$type;	
	  $this->id=AutaPlugin::$prefix.$name;
	  $this->title=__($title);	 
	  $this->options=$options;	  
	  $this->customPostType=$postType;		 
	  $this->compare=$compare;
	  $this->filterorder=$filterorder;
	  $this->displayorder=$displayorder;
	  $this->icon=$icon;
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
	if ($this->type=="bool" && $val!="on") $val="0";
	if ($this->type=="bool" && $val=="on") $val="1";	
    AutaPlugin::logWrite("save: {$this->name} {$val}");	
	update_post_meta($post->ID, $this->name, $val);
 }
 public function printFieldEdit() {
	 ?>
	
	 <form action='<?= remove_query_arg( 'do')?>' method='post' class='editFieldRow'>	 	 	 
			<div><div><label>name</label></div><input type='text' readonly='true' name='name' value='<?= $this->name?>' /></div>
			<div><div><label>type</label></div><input type='text' name='type' value='<?= $this->type?>' /></div>
			<div><div><label>compare</label></div><input type='text' name='compare' value='<?= $this->compare?>' /></div>
			<div><div><label>options (split with ;)</label></div><input type='text' name='options' value='<?= $this->options?>' /></div>
			<div><div><label>title</label></div><input type='text' name='title' value='<?= $this->title?>' /></div>	
			<div><div><label>filterorder</label></div><input type='text' name='filterorder' value='<?= $this->filterorder?>' /></div>	
			<div><div><label>displayorder</label></div><input type='text' name='displayorder' value='<?= $this->displayorder?>' /></div>	
			<div><div><label>icon</label></div>
			<div class='iconEdit'>
			<?php
			if( $image = wp_get_attachment_image_src( $this->icon ) ) {
	
				echo '
					<a href="#" class="icon-upl"><img src="' . $image[0] . '" /></a>
					<input type="hidden" name="icon" value="'.$this->icon.'" />		
					<a href="#" class="icon-rmv">Remove image</a>
								
					';
			
			} else {
			
				echo '
					<a href="#" class="icon-upl">Upload image</a>
					<input type="hidden" name="icon" value="'.$this->icon.'" />		
					<a href="#" class="icon-rmv" style="display:none">Remove image</a>
									
					';
			
			}
			?>
			</div>

			</div>			
						
			<div><input name='editField' type='submit' value='edit' /><input name='deleteField' type='submit' value='delete' /></div>	

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
   $wpdb->get_results($query);	
   if (!$deleteOnly) {   	
	$icon=$this->icon;		
	if ($tabName=="ajax") {
		$icon=wp_get_attachment_url($icon);
	}
	$query = "INSERT INTO `{$tableName}` ( `name`, `value`, `type`, `title`, `compare`, `valMin`, `valMax`, `postType`, `filterorder`, `displayorder`, `icon`) 
		VALUES ('{$this->name}', '{$this->value}', '{$this->type}', '{$this->title}', '{$this->compare}', '{$this->valMin}', '{$this->valMax}', '{$this->customPostType}', '{$this->filterorder}', '{$this->displayorder}', '{$icon}');";   
	$wpdb->get_results($query);	 
	return "<br />{$this->name} saved";
   }
 }
}
