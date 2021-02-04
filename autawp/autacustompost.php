<?php
namespace AutaWP;

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
	 
	function csvMenu() {
		$setUrl = [	
			["csv import",add_query_arg( 'do', 'csv'),"import csv file"],
			["csv remove",add_query_arg( 'do', 'removecsv'),"remove csv imports"],
		];
		?>
		<h1>CSV options</h1>
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
		if ($do=="csv") {
			$importCSV=new ImportCSV();		
			$importCSV->loadCsvFile(plugin_dir_path( __FILE__ )."recsout.txt","csvtab","^","",null,true,"cp852");		  
			$importCSV->createPostsFromTable("csvtab");	
		  }
		  if ($do=="removecsv") {
			$importCSV=new ImportCSV();
			$importCSV->removePreviousPosts("csvtab");	
		  }	
	}

	 function add_to_admin_menu() {
		//add_submenu_page( string $parent_slug, string $page_title, string $menu_title, string $capability, string $menu_slug, callable $function = '', int $position = null )
		$parent_slug='edit.php?post_type='.$this->customPostType;
		$page_title='Auta admin';		
		$capability='edit_posts';
		$menu_slug=basename(__FILE__);
		$function = [$this,'csvMenu'];
		$menu_title='Import';
		add_submenu_page($parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function);
	} 
		
}