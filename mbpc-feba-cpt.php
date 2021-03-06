<?php
/*
Plugin Name: MBPC FEBA Custom Post Type Plugin
Plugin URI: http://example.com/wordpress-plugins/my-plugin
Description: Adds a custom post type for FEBA messages to the website
Version: 1.1
Author: fonglh
Author URI: https://wpadventures.wordpress.com
License: GPLv2
*/

register_activation_hook( WP_PLUGIN_DIR . '/mbpc-feba-cpt/mbpc-feba-cpt.php', 'mbpc_feba_cpt_install' );

/* Start by giving the administrator role access to the CPT
 */
function mbpc_feba_cpt_install() {
	/* Get the administrator role. */
	$role =& get_role( 'administrator' );

	/* If the administrator role exists, add required capabilities for the plugin. */
	if ( !empty( $role ) ) {
		/* CPT management capabilities. */
		$role->add_cap( 'publish_febas' );
		$role->add_cap( 'create_febas' );
		$role->add_cap( 'delete_febas' );
		$role->add_cap( 'delete_published_febas' );
		$role->add_cap( 'edit_febas' );
		$role->add_cap( 'edit_published_febas' );
	}

	flush_rewrite_rules();

}

register_deactivation_hook( WP_PLUGIN_DIR . '/mbpc-feba-cpt/mbpc-feba-cpt.php', 'mbpc_feba_cpt_deactivate' );

/* Remove custom capabilities when plugin is deactivated
 *
 */
function mbpc_feba_cpt_deactivate() {
	/* Get the administrator role. */
	$role =& get_role( 'administrator' );

	/* If the administrator role exists, add required capabilities for the plugin. */
	if ( !empty( $role ) ) {
		/* CPT management capabilities. */
		$role->remove_cap( 'publish_febas' );
		$role->remove_cap( 'create_febas' );
		$role->remove_cap( 'delete_febas' );
		$role->remove_cap( 'delete_published_febas' );
		$role->remove_cap( 'edit_febas' );
		$role->remove_cap( 'edit_published_febas' );
	}

	flush_rewrite_rules();
}



add_action( 'init', 'mbpc_add_feba_cpt' );

/*  Register the custom post type for FEBA
 *
 */
function mbpc_add_feba_cpt() {
	 /* Set up the arguments for the 'FEBA' post type. */
    $feba_args = array(
       'labels'=>array(
					'name'=>__('FEBA Messages'),
					'singular_name'=>__('FEBA Message'),
					'add_new'=>__('Add New'),
					'add_new_item'=>__('Add New FEBA Message'),
					'edit'=>__('Edit'),
					'edit_item'=>__('Edit FEBA Message'),
					'new_item'=>__('New FEBA Message'),
					'view_item'=>__('View FEBA Message'),
					'search_items'=>__('Search FEBA Messages'),
					'not_found'=>__('No FEBA Messages found'),
					'not_found_in_trash'=>__('No FEBA Messages found in trash')),    
	   'description'=>__('Contains a link to the recording of the FEBA Message'),
        'public' => true,
		'menu_position' => 8,
		'has_archive' => true,
		'capability_type' => 'feba',
		'map_meta_cap' => true,
        'rewrite' => array(
            'with_front' => false,
			'slug' => 'feba'
        )
		);

    /* Register the feba post type. */
    register_post_type( 'feba', $feba_args );

	//register taxonomy for the feba post type
	register_taxonomy(
			'station',
			array('feba'),
			array(
				'labels'=>array(
					'name'=>__('Radio Stations'),
					'singular_name'=>__('Station'),
					'select_name' => __( 'Select Radio Station' ),
					'search_items'=>__('Search for Radio Station'),
					'popular_items'=>__('Popular Radio Stations'),
					'all_items'=>__('All Radio Stations'),
					'edit_item'=>__('Edit Radio Station'),
					'update_item'=>__('Update Radio Station'),
					'add_new_item'=>__('Add New Radio Station'),
					'new_item_name'=>__('Name of Radio Station'),
					'add_or_remove_items'=>__('Add or remove Radio Stations')
					),
				'hierarchical'=>true,
				'capabilities' => array('assign_terms' => 'edit_febas')
				)
			);

}

// for new feba posts, hide the default post editor
// or when editing feba posts, hide the default post editor
if( ( isset( $_GET['post_type'] ) && 'feba' == $_GET['post_type'] ) ||
	( isset( $_GET['post'] ) && 'feba' == get_post_type( $_GET['post'] ) ) ) {
	add_action( 'admin_head', 'mbpc_feba_hide_editor' );
	add_action( 'admin_head', 'mbpc_feba_add_post_enctype' );
}

function mbpc_feba_hide_editor() {
	?>
		<style>
			#postdivrich {display:none;}
		</style>
	<?php
}

// Need to add the following attributes to the overall post form for file uploads to work
function mbpc_feba_add_post_enctype() {
	echo '
		<script type="text/javascript">
		jQuery(document).ready(function(){
				jQuery("#post").attr("enctype", "multipart/form-data");
				jQuery("#post").attr("encoding", "multipart/form-data");
				});
	</script>';
}


add_action( 'add_meta_boxes', 'mbpc_feba_add_upload_box' );

function mbpc_feba_add_upload_box() {
	add_meta_box(
		'mbpc_feba_upload',
		'FEBA Audio Upload',
		'mbpc_feba_show_box',
		'feba',
		'normal'
	);
}

function mbpc_feba_show_box( $post ) {
	// use nonce for verification
	wp_nonce_field( plugin_basename( __FILE__ ), 'mbpc_feba_upload_nonce' );

	// get attached mp3 file (if any) and show which one is displayed
	$children = get_children(array('post_parent' => $post->ID, 'post_type' => 'attachment'));
	if($children) {
		echo 'Currently attached: <br/>';
		foreach ($children as $child) {		//there should only be 1 child anyway
			echo '<a href=\'' . $child->guid . '\'>' . $child->guid . '</a><br />';
		}
		echo '<br/>';
	}

	//output file input box
	echo '<input type="file" id="mbpc_feba_file" name="mbpc_feba_file" />';
	echo '<br />';
	echo 'Filename format: feba-yyyy-mm-dd.mp3';
}

add_action( 'save_post', 'mbpc_feba_save_upload' );

function mbpc_feba_save_upload( $post_id ) {
	// verify if this is an autosave routine
	// if form hasn't been submitted, don't want to do anything
	if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
		return;

	// verify this came from our screen and with proper auth
	// because save_post can be triggered at other times
	if( !wp_verify_nonce( $_POST[ 'mbpc_feba_upload_nonce' ], plugin_basename( __FILE__ ) ) )
		return;

	//check permissions
	if ( 'page' == $_POST['post_type'] ) {
		if ( !current_user_can( 'edit_page', $post_id ) )
			return;
	}
	else {
		if ( !current_user_can( 'edit_post', $post_id ) )
			return;
	}

	// only do all the stuff below if there's a file upload
	if ( !empty( $_FILES[ 'mbpc_feba_file' ] ) ) {
		$uploaded_file = $_FILES[ 'mbpc_feba_file' ][ 'name' ];
		$uploaded_file = basename($uploaded_file);
		//replace spaces with '-' to facilitate year/mth extraction
		$uploaded_file = str_replace(' ', '-', $uploaded_file);		
		$name_parts = explode('-', $uploaded_file);

		$name_parts[0] = strtolower( $name_parts[0] );		//convert prefix to lowercase
		//assumes 4 part filenames for date operations
		if( !is_numeric( $name_parts[2] ) ) {	//assume 3 letter month, replace with 2 digit number
			$month_names = array("JAN", "FEB", "MAR", "APR", "MAY", "JUN", "JUL", "AUG", "SEP",
					"OCT", "NOV", "DEC");
			$name_parts[2] = strtoupper($name_parts[2]);
			$name_parts[2] = array_search($name_parts[2], $month_names) + 1;
		}

		$name_parts[2] = sprintf( '%02u', $name_parts[2] );	//convert mth to 2 digits
		$last_part = explode('.', $name_parts[3]);			//split file extension and day
		$last_part[0] = sprintf('%02u', $last_part[0]);		//convert day to 2 digit format
		$name_parts[3] = implode('.', $last_part);			//combine day and extension

		$time = $name_parts[1] . '-' . $name_parts[2];		//time is in yyyy-mm format
		if( preg_match( '/[0-9]{4}-[0-1][0-9]/', $time ) == 0 ) {		//time does not match format
			$time = null;
		}
		$_FILES[ 'mbpc_feba_file' ][ 'name' ] = implode('-', $name_parts);			//rename file

		$file = wp_handle_upload( $_FILES[ 'mbpc_feba_file' ], array('test_form' => false), $time );
		$filename = $file[ 'url' ];
		if ( !empty( $filename ) ) {
			$currPost = get_post( $post_id );

			//unattach current attachment, if any
			//this ensures that each feba post only has 1 mp3 file attached to it
			//only unattach files with the same prefix as the uploaded one
			$children = get_children( array( 'post_parent' => $post_id, 'post_type' => 'attachment' ) );
			if( $children ) {
				foreach( $children as $child ) {
					$path_parts = pathinfo( $child->guid );
					//check if prefix of uploaded file matches prefix of file to be unattached
					$child_parts = explode( '-', $path_parts['filename']);
					if( $child_parts[0] == $name_parts [0] ) {
						wp_update_post(array('ID' => $child->ID, 'post_parent' => 0, 
									'post_name' => $path_parts['filename'], 
									'post_title' => $path_parts['filename']));
					}
				}
			}

			//attach attachment post to the main parent post
			$wp_filetype = wp_check_filetype( basename($filename), null );
			$attachment = array(
					'post_mime_type' => $wp_filetype['type'],
					'post_status' => 'inherit',
					'guid' => $filename,
					'post_title' => $currPost -> post_title
					);
			$attach_id = wp_insert_attachment( $attachment, $filename, $post_id );
			// taken from the wp_insert_attachment() codex docs, actually meant for image attachments
			// you must first include the image.php file
			// for the function wp_generate_attachment_metadata() to work
			require_once(ABSPATH . 'wp-admin/includes/image.php');
			$attach_data = wp_generate_attachment_metadata($attach_id, $filename);
			wp_update_attachment_metadata($attach_id, $attach_data);

			//update post content with the download text and link to the file
			//add post content only if it's a FEBA type
			// update post date to match date given in filename
			$pos = strpos ( strtoupper( $name_parts[0] ), 'FEBA' );
			if( $pos !== false ) {
				$post_content = '[audio:' . $filename . '|titles=' . $currPost -> post_title . ']';
				$post_content .= '<p>Download MP3: <a href="' . $filename . '">' . $currPost -> post_title . '</a></p>';

				// split the last part into day.extension components
				$day = explode( '.', $name_parts[3] );
				$post_date = $name_parts[1] . '-' . $name_parts[2] . '-' . $day[0];
				// append current time to post date
				$time_str = date( 'H:i:s' );
				$post_date .= ' ' . $time_str;

				wp_update_post(array('ID' => $post_id, 'post_content' => $post_content, 
									'post_date' => $post_date, 'post_date_gmt' => $post_date));
			}
		}
	}

}

add_filter( 'template_include', 'mbpc_feba_archive_template' );

function mbpc_feba_archive_template( $template ) {
	$post_type = get_query_var( 'post_type' );

	// change the loaded template if we're looking at an archive page for the 'feba' CPT
	// use the included archive-feba-{theme}.php template file
	// if archive-feba.php exists in the theme directory, use that instead. this allows 
	// themes to override the plugin provided templates. views go into themes.
	//
	// assumes either twentyeleven or MBPC theme (child theme of twentyten) is active
	// this dependency should be removed with a proper custom archive file
	if ( is_post_type_archive( 'feba' ) && mbpc_feba_template_exists( $template ) == false ) {
		if ( function_exists( 'twentyeleven_content_nav' ) )
			$template = plugin_dir_path( __FILE__ ) . 'templates/archive-feba-2011.php';
		else
			$template = plugin_dir_path( __FILE__ ) . 'templates/archive-feba-2010.php';
	}
	
	if ( is_single() && $post_type == 'feba' && mbpc_feba_template_exists( $template ) == false) {
		// twenty eleven's single.php isn't meant to be used with a sidebar, so just stick with that
		if ( function_exists( 'twentyeleven_content_nav' ) );
		else
			$template = plugin_dir_path( __FILE__ ) . 'templates/single-feba-2010.php';
	}
	return $template;
}

/* Check if {template}-feba.php template already exists in the theme folder
 *
 */
function mbpc_feba_template_exists( $template_path ) {
	$template = basename( $template_path );
	
	// check if the template WordPress has picked from the theme/child theme already handles the CPT
	if ( 1 == preg_match( '/^(\S*)-feba.php/', $template ) )
		return true;

	return false;
}

add_action( 'generate_rewrite_rules', 'mbpc_feba_rewrite' );

function mbpc_feba_rewrite( $wp_rewrite ) {
	$rules = mbpc_feba_generate_date_archives( 'feba', $wp_rewrite );
	$wp_rewrite->rules = array_merge( $rules, $wp_rewrite->rules );
	return $wp_rewrite;
}

// to help with debugging by printing out the whole rules array before exiting
//add_filter( 'rewrite_rules_array', 'mbpc_feba_view_rewrite' );
function mbpc_feba_view_rewrite( $rules ) {
	print_r ( $rules );
	die();
}


// taken from http://www.dev4press.com/2012/tutorials/wordpress/practical/url-rewriting-custom-post-types-date-archive/
// generates date archive rewrite rules for a given CPT
function mbpc_feba_generate_date_archives($cpt, $wp_rewrite) {
	$rules = array();

	$post_type = get_post_type_object($cpt);
	$slug_archive = $post_type->has_archive;
	if ($slug_archive === false) return $rules;
	if ($slug_archive === true) {
		$slug_archive = $post_type->name;
	}

	$dates = array(
			array(
				'rule' => "([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})",
				'vars' => array('year', 'monthnum', 'day')),
			array(
				'rule' => "([0-9]{4})/([0-9]{1,2})",
				'vars' => array('year', 'monthnum')),
			array(
				'rule' => "([0-9]{4})",
				'vars' => array('year'))
			);

	foreach ($dates as $data) {
		$query = 'index.php?post_type='.$cpt;
		$rule = $slug_archive.'/'.$data['rule'];

		$i = 1;
		foreach ($data['vars'] as $var) {
			$query.= '&'.$var.'='.$wp_rewrite->preg_index($i);
			$i++;
		}

		$rules[$rule."/?$"] = $query;
		$rules[$rule."/feed/(feed|rdf|rss|rss2|atom)/?$"] = $query."&feed=".$wp_rewrite->preg_index($i);
		$rules[$rule."/(feed|rdf|rss|rss2|atom)/?$"] = $query."&feed=".$wp_rewrite->preg_index($i);
		$rules[$rule."/page/([0-9]{1,})/?$"] = $query."&paged=".$wp_rewrite->preg_index($i);
	}

	return $rules;
}


// register a sidebar so conditions can be used to display a custom sidebar for this CPT
add_action( 'widgets_init', 'mbpc_feba_register_sidebar' );

function mbpc_feba_register_sidebar() {
	// Register a sidebar which will show up when certain conditions are met
	// depending on sidebar.php 
	register_sidebar( array(
		'name' => __( 'FEBA Widget Area', 'mbpc_feba' ),
		'id' => 'mbpc-feba-widget-area',
		'description' => __( 'Widget area for FEBA custom post type', 'mbpc_feba' ),
		'before_widget' => '<li id="%1$s" class="widget-container %2$s">',
		'after_widget' => '</li>',
		'before_title' => '<h3 class="widget-title">',
		'after_title' => '</h3>',
	) );
}


