<?php
/*
Plugin Name: MBPC FEBA Custom Post Type Plugin
Plugin URI: http://example.com/wordpress-plugins/my-plugin
Description: Adds a custom post type for FEBA messages to the website
Version: 1.0
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

    /* Register the music album post type. */
    register_post_type( 'feba', $feba_args );
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

	if ( !empty( $_FILES[ 'mbpc_feba_file' ] ) ) {
		$uploaded_file = $_FILES[ 'mbpc_feba_file' ][ 'name' ];
		echo $uploaded_file;
		die();
	}
	else {
	}

}
