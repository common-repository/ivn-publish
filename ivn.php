<?php
/**
 * Plugin Name: IVN Publish
 * Plugin URI: http://ivn.us/plugin/
 * Description: Allows external WordPress sites to publish posts to IVN.us
 * Version: 1.2
 * Author: IVN
 * Author URI: http://ivn.us
 * Requires at least: 3.3
 * Tested up to: 3.0

Copyright: 2013 Shared and distributed between IVN, James Willmott and Creative Round

GNU General Public License, Free Software Foundation <http://creativecommons.org/licenses/GPL/2.0/>
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*/



/** 
* include XML-RPC library
*/
require_once('IXR_Library.php');


/**
* Global Variables
*/
$ivn_plugin_options = get_option('ivn_plugin_options');
$ivn_version = 1.0;
$ver_url = 'http://ivn.us/ivn-wp-plugin.xml';
$rpc_url = 'http://ivn.us/xmlrpc.php';
$max_post = 20;


/**
* include IVN css style sheet
*/
add_action( 'admin_init','ivn_css');

function ivn_css() {

	wp_register_style( 'ivn_css', plugins_url('css/ivn.css',__FILE__ ));
	wp_enqueue_style('ivn_css');

}



/**
* IVN Settings Class
*/
class IVN_Options
{

	public $options;
	
	public function __construct()
	{
		//delete_option('ivn_plugin_options');
		$this->options = get_option('ivn_plugin_options');
		$this->register_settings_and_fields();

	}

	public function add_menu_page()
	{
		
		
		add_options_page(__('IVN Options', 'ivn'), __('IVN Plugin', 'ivn'), 'manage_options', __FILE__, array('IVN_Options', 'ivn_render_admin'));
	}

	public function ivn_render_admin()
	{
		?>

		<div class="wrap">
			<?php screen_icon(); ?>
			<h2><?php _e('IVN Plugin Administration', 'ivn') ?></h2><br /><a href="http://ivn.us"><img src="<?php echo plugins_url('img/ivn.png',__FILE__ ); ?>" /></a>
			<p><?php _e('For more information please visit ', 'ivn') ?> <a href="http://ivn.us">our website</a>.  For technical support, please email <a href="mailto:admin@ivn.us">admin@ivn.us</a>.<br />		

			<form action="options.php" method="POST" >
				<?php settings_fields('ivn_plugin_options'); ?>
				<?php do_settings_sections(__FILE__); ?>
				<br /><input name="submit" type="submit" class="ivn_registerlink" value="Save Changes" />
			</form>

			<br />
			<br />
			<hr>
			<br />
			<br />
			
			<h3><?php _e('Publish All Articles To IVN', 'ivn') ?></h3>
			<p><?php _e('Clicking the "Publish All" button will one-time publish every post on the site to IVN.  Recommended for new sites only with fewer than 20 posts.', 'ivn') ?></p>
			<img src="<?php echo admin_url('/images/wpspin_light.gif'); ?>" class="waiting" id="ivn_loading" style="display:none" />
			<div id="ivn_results"></div>
			<form action="" method="POST" id="ivn-push-all-form" >
				<input type="submit" name="push-all" value="<?php _e('Publish All', 'ivn') ?>" id="ivn_submit" class="ivn_registerlink"/>	
			</form>
		</div>
	<?php
	}

	public function register_settings_and_fields()
	{
		register_setting( 'ivn_plugin_options', 'ivn_plugin_options');
		//register_setting( 'ivn_plugin_options', 'ivn_plugin_options', array($this, 'ivn_validate_settings'));
		add_settings_section('ivn_main_section', 'Publishing Settings', array($this, 'ivn_main_section_cb'), __FILE__);
		add_settings_field('ivn_username', 'IVN Username: ', array($this, 'ivn_username_setting'), __FILE__, 'ivn_main_section');
		add_settings_field('ivn_password', 'IVN Password: ', array($this, 'ivn_password_setting'), __FILE__, 'ivn_main_section');
		add_settings_field('ivn_rpcurl', 'Publishing URL (optional): ', array($this, 'ivn_rpcurl_setting'), __FILE__, 'ivn_main_section');
		add_settings_field('ivn_autopublish', 'Automatically Publish New Posts: ', array($this, 'ivn_autopublish_setting'), __FILE__, 'ivn_main_section');

	}
	public function ivn_main_section_cb()
	{

	}

	public function ivn_validate_settings($plugin_options)
	{

	}
	
	

	/**
	* Inputs
	*/

	// username
	public function ivn_username_setting()
	{
		echo "<input type='text' name='ivn_plugin_options[ivn_username]' value='{$this->options['ivn_username']}' />";
	}

	// password
	public function ivn_password_setting()
	{
		echo "<input type='password' name='ivn_plugin_options[ivn_password]' value='{$this->options['ivn_password']}'/>";
		echo "<br> <a class='ivn-link' href='http://ivn.us/create-account' target='_blank'>Create Account</a>";
		echo "&nbsp;<a class='ivn-link' href='http://ivn.us/wp-login.php?action=lostpassword' target='_blank'>Forgot Password?</a>";
	}

	// auto-publish
	public function ivn_autopublish_setting()
	{
		
		$o = get_option('ivn_plugin_options');

		if (is_array($o)) {

			if (array_key_exists('ivn_autopublish', $o)) {
			
				echo "<input type='checkbox' name='ivn_plugin_options[ivn_autopublish]' checked='yes'/>";
		
			} else {

				echo "<input type='checkbox' name='ivn_plugin_options[ivn_autopublish]' />";
				
			}

		} else {

			echo "<input type='checkbox' name='ivn_plugin_options[ivn_autopublish]' />";

		}

	}

	// xml-rpc url
	public function ivn_rpcurl_setting()
	{

		echo "<input type='text' name='ivn_plugin_options[ivn_rpcurl]' value='{$this->options['ivn_rpcurl']}' />";

	}

}



/**
* Place link to plugin on admin menu tab
*/
add_action('admin_menu', 'ivn_plugin_menu_tab');

function ivn_plugin_menu_tab() {

	IVN_Options::add_menu_page();

}



/**
* Register settings
*/
add_action( 'admin_init', 'ivn_plugin_settings' );

function ivn_plugin_settings() {

	new IVN_Options();

}


/**
* Load Javascript
*/
add_action('admin_enqueue_scripts', 'ivn_load_scripts');

function ivn_load_scripts($hook) {

	wp_enqueue_script('ivn-ajax', plugin_dir_url(__FILE__) . 'js/ivn-ajax.js', array('jquery'));
	wp_localize_script('ivn-ajax', 'ivn_vars', array('ivn_nonce' => wp_create_nonce('ivn-nonce')));

}

/**
* Process AJAX request
*/
add_action('wp_ajax_ivn_get_results', 'ivn_process_ajax');

function ivn_process_ajax() {
	global $max_post;
	
	if(!isset($_POST['ivn_nonce']) || !wp_verify_nonce($_POST['ivn_nonce'], 'ivn-nonce'))
		die("Permission check failed");
	
	$args = array('post_status' => 'publish');
	$posts = get_posts($args);
	
	if ($posts) {

		
		if (count($posts) <= $max_post) {

			foreach($posts as $post) {

				$response = processXMLRPC($post->ID);
			}
		
		} else {

			$response = '<div id="message" class="error"><p><strong>Error Publishing Post to IVN. You cannot exceed a maximum of 20 post at once.</strong></p></div>';
	

		}

	}

	echo $response;
	die();
}


/**
* Create metabox
*/
add_action( 'load-post.php', 'ivn_post_meta_boxes_setup' );

function ivn_post_meta_boxes_setup() {
	
	/* Add meta boxes on the 'add_meta_boxes' hook. */
	add_action( 'add_meta_boxes', 'cd_meta_box_add' );

}

function cd_meta_box_add() {
	
	add_meta_box( 'my-meta-box-id', 'IVN', 'cd_meta_box_cb', 'post', 'side', 'high' );

}

/**
* Create metabox form
*/
function cd_meta_box_cb($post) {
	

		$meta_values = get_post_meta($post->ID, '_ivn_post_date', false);


		if(empty($meta_values)) {
    
			?>
			<p>Publish this post to <a href="http:///ivn.us" target='_blank'>IVN.us</a>
			<form id="ivn_form_post" method="POST">
				<div>
            		<input id="post_id_hidden" type="hidden" name="post_id_hidden" value="<?php echo $post->ID; ?>" />
            		<input id="ivn_button" type="button" value="Publish To IVN" class="ivn_registerlink" />
        		</div>
    		</form>
    		<br /><img src="<?php echo admin_url('/images/wpspin_light.gif'); ?>" class="waiting" id="ivn_loading_post" style="display:none" />
			<div id="ivn_results_post"></div>
			<?php
		
		} else {

		//echo end($meta_values);
			?>
			<p>This post was already publised to IVN on <?php echo end($meta_values); ?></a>
			<form id="ivn_form_post" method="POST">
				<div>
            		<input id="post_id_hidden" type="hidden" name="post_id_hidden" value="<?php echo $post->ID; ?>" />
            		<input id="ivn_button" type="button" value="Resubmit To IVN" class="ivn_registerlink" />
        		</div>
    		</form>
    		<br /><img src="<?php echo admin_url('/images/wpspin_light.gif'); ?>" class="waiting" id="ivn_loading_post" style="display:none" />
			<div id="ivn_results_post"></div>
			<?php
		
		}


}



/**
* Process AJAX on post list column
*/
add_action('wp_ajax_ivn_get_results_post', 'ivn_process_ajax_post');

function ivn_process_ajax_post() {
	
	if(!isset($_POST['ivn_nonce']) || !wp_verify_nonce($_POST['ivn_nonce'], 'ivn-nonce'))
		die("Permission check failed");
	
	if(isset($_POST['ivn_post_id']))	
		echo $response = processXMLRPC($_POST['ivn_post_id']);
	
	die();
}


/**
* Add custom column to post list
*/
add_filter('manage_posts_columns', 'posts_columns_id', 5);
add_action('manage_posts_custom_column', 'posts_custom_id_columns', 5, 2);

function posts_columns_id($columns) {
  $new = array();
  foreach($columns as $key => $title) {
    if ($key=='author') // Put the column before the Author
      $new['wps_post_id'] = __('IVN');
    $new[$key] = $title;
  }
  return $new;
}


/**
* Add custom button to post list column
*/
function posts_custom_id_columns($column_name, $id){
    
    if($column_name === 'wps_post_id'){
		
		$loading_image = admin_url('/images/wpspin_light.gif');
		$meta_values = get_post_meta($id, '_ivn_post_date', false);


		if(empty($meta_values)) {
    
			$html = '<div><span class="clickable-image" ><div class="ivn_post_button" edit_id="'.$id.'">Publish To IVN</div></span></div>';
    		$html .= '<img src="'.$loading_image.'" class="waiting" id="ivn_loading_post_row_'.$id.'" style="display:none" />';
			$html .= '<div id="ivn_results_post_'.$id.'"></div>';
			echo $html;
		
		} else {

		echo "Already Published to IVN on <br />".end($meta_values);

		}

	
    }
}


/**
* Register post insert hook
*/
add_action ('publish_post', 'ivn_publish_article');

function ivn_publish_article($post_id) {

	global $ivn_plugin_options;

	if (array_key_exists('ivn_autopublish', $ivn_plugin_options)) {
		$response = processXMLRPC($post_id);
		return $response;
	}
	
}


/**
* Custom Bulk Class
*/
if (!class_exists('Custom_Bulk_Action')) {
 
	class Custom_Bulk_Action {
		
		public function __construct() {
			
			if(is_admin()) {
				// admin actions/filters
				add_action('admin_footer-edit.php', array(&$this, 'custom_bulk_admin_footer'));
				add_action('load-edit.php',         array(&$this, 'custom_bulk_action'));
				add_action('admin_notices',         array(&$this, 'custom_bulk_admin_notices'));
			}
		}
		
		
		/**
		 * Step 1: add the custom Bulk Action to the select menus
		 */
		function custom_bulk_admin_footer() {
			global $post_type;
			
			if($post_type == 'post') {
				?>
					<script type="text/javascript">
						jQuery(document).ready(function() {
        					jQuery('<option>').val('export').text('<?php _e('Publish to IVN')?>').appendTo("select[name='action']");
        					jQuery('<option>').val('export').text('<?php _e('Publish to IVN')?>').appendTo("select[name='action2']");
						});
					</script>
				<?php
	    	}
		}
		
		
		/**
		 * Step 2: handle the custom Bulk Action
		 */
		function custom_bulk_action() {
			global $typenow, $max_post;
			$post_type = $typenow;
			
			if($post_type == 'post') {
				
				// get the action
				$wp_list_table = _get_list_table('WP_Posts_List_Table');  // depending on your resource type this could be WP_Users_List_Table, WP_Comments_List_Table, etc
				$action = $wp_list_table->current_action();
				
				$allowed_actions = array("export");
				if(!in_array($action, $allowed_actions)) return;
				
				// security check
				check_admin_referer('bulk-posts');
				
				// make sure ids are submitted.  depending on the resource type, this may be 'media' or 'ids'
				if(isset($_REQUEST['post'])) {
					$post_ids = array_map('intval', $_REQUEST['post']);
				}
				
				if(empty($post_ids)) return;
				
				// this is based on wp-admin/edit.php
				$sendback = remove_query_arg( array('exported', 'untrashed', 'deleted', 'ids'), wp_get_referer() );
				if ( ! $sendback )
					$sendback = admin_url( "edit.php?post_type=$post_type" );
				
				$pagenum = $wp_list_table->get_pagenum();
				$sendback = add_query_arg( 'paged', $pagenum, $sendback );
				
				switch($action) {
					case 'export':
						
						// if we set up user permissions/capabilities, the code might look like:
						//if ( !current_user_can($post_type_object->cap->export_post, $post_id) )
						//	wp_die( __('You are not allowed to export this post.') );
						
						$exported = 0;
						
						if (count($post_ids) <= $max_post) {

							foreach( $post_ids as $post_id ) {
							
							if ( !$this->perform_export($post_id) )
								wp_die( __('Error publishing post to IVN.') );
			
							$exported++;

							}
						
							$sendback = add_query_arg( array('exported' => $exported, 'ids' => join(',', $post_ids) ), $sendback );

						} else {

							wp_die( __('Error Publishing Post to IVN. You cannot exceed a maximum of 20 post at once.') );
						}

				
					break;
					
					default: return;
				}
				
				$sendback = remove_query_arg( array('action', 'action2', 'tags_input', 'post_author', 'comment_status', 'ping_status', '_status',  'post', 'bulk_edit', 'post_view'), $sendback );
				
				wp_redirect($sendback);
				exit();
			}
		}
		
		
		/**
		 * Step 3: display an admin notice on the Posts page after exporting
		 */
		function custom_bulk_admin_notices() {
			global $post_type, $pagenow;
			
			if($pagenow == 'edit.php' && $post_type == 'post' && isset($_REQUEST['exported']) && (int) $_REQUEST['exported']) {
				$message = sprintf( _n( 'Post published to IVN.', '%s posts published to IVN.', $_REQUEST['exported'] ), number_format_i18n( $_REQUEST['exported'] ) );
				echo "<div class=\"updated\"><p>{$message}</p></div>";
			}
		}
		
		function perform_export($post_id) {
			// run through each post
			$response = processXMLRPC($post_id);
			
			if (strlen(strstr($response, "Successfully"))>0) {
			return true;
			} else {
				return false;
			}
		}
	}
}
new Custom_Bulk_Action();


/**
* Process XML-RPC push and response
*/
function processXMLRPC($pid) {

	$output = '';
	$postData = getPostData($pid);
	$imgResults = wpImageXMLRPC($postData['images']);
				
	//image list is empty
	if(empty($imgResults)) {
    
     	$postResults = wpPostXMLRPC($postData, $imgResults='NULL');
		
		if (!is_array($postResults)) {
				
				$output = '<div id="setting-error-settings_updated" class="updated settings-error"><p><strong>Successfully posted to IVN and awaiting approval</strong></p></div>';
				
				add_post_meta($pid, '_ivn_post_date', date("m/d/Y"));
			
			} else {

				$output = '<div id="message" class="error"><p><strong>'.$postResults['faultCode'].' : '.$postResults['faultString'].'</strong></p></div>';

			}

		}

	//image list is NOT empty
	if (is_array($imgResults)) {
		$arr_count = count($imgResults[0]);

		if ($arr_count > 2) {
				
			$postResults = wpPostXMLRPC($postData, $imgResults);
			
			if($postResults > 0) {
				
				$output = '<div id="setting-error-settings_updated" class="updated settings-error"><p><strong>Successfully posted to IVN and awaiting approval</strong></p></div>';
				
				add_post_meta($pid, '_ivn_post_date', date("m/d/Y"));
			}
			

		} else {

			$output = '<div id="message" class="error"><p><strong>'.$imgResults[0]['faultCode'].' : '.$imgResults[0]['faultString'].'</strong></p></div>';

		}

	}
	
	return $output;
}



/**
* Push post to IVN
*/
function wpPostXMLRPC($data, $images, $encoding='UTF-8') {
	global $ivn_plugin_options, $rpc_url;
       
    if ($images != 'NULL') {
    	
    	$modBody = swapElements($data['body'], $images);
    
    } else {
    	
    	$modBody = $data['body'];
    }
    
    $cats = implode(",", $data['categories']);
     
    
    if ($ivn_plugin_options['ivn_rpcurl'] != ''){

    	$client = new IXR_Client($ivn_plugin_options['ivn_rpcurl']);

    } else {

    	$client = new IXR_Client($rpc_url);
    }


    $postData = array(
    	'title' => $data['title'],
    	'description' => $modBody,
    	'dateCreated' => (new IXR_Date(time())),
    	'mt_allow_comments' => 1,  // 1 to allow comments / 0 to disallow
    	'mt_allow_pings' => 1,  // 1 to allow trackbacks / 0 to disallow
    	'mt_keywords'=> $data['tags'],
    	'custom_fields' => array( array( 'key' => 'sourceLink', 'value' => $data['permalink'] ),
						   array( 'key' => 'sourceType', 'value' => 'xmlrpc' ),
						   array( 'key' => 'sourceCategory', 'value' => $cats ))
	);

    //post-type_status
    $published = 0; // 0 - draft, 1 - published
    $res = $client->query('metaWeblog.newPost', '', $ivn_plugin_options['ivn_username'], $ivn_plugin_options['ivn_password'], $postData, $published);

    $response = $client->getResponse();

    return $response;
}


/**
* Upload images
*/
function wpImageXMLRPC($images,$encoding='UTF-8') {
	global $ivn_plugin_options, $rpc_url;

	$xPostArr = array();

	foreach ($images as $image) {
	
		if ($ivn_plugin_options['ivn_rpcurl'] != ''){

    		$rpc = new IXR_Client($ivn_plugin_options['ivn_rpcurl']);

    	} else {
    	
    		$rpc = new IXR_Client($rpc_url);
    	}
		
		$method_name = "wp.uploadFile";
		$imgpath = $image;
		$xFile = new IXR_Base64(file_get_contents($imgpath));

		$pieces = explode("/", $image);

		$post = array(
			"name" => end($pieces),
			"type" => 'image/jpg',
			"bits" => $xFile,
			"overwrite" => 'true'
		);

		$status = $rpc->query($method_name,1,$ivn_plugin_options['ivn_username'],$ivn_plugin_options['ivn_password'],$post);
		
		$xPostArr[] = $rpc->getResponse();
	}


	return $xPostArr;
}



/**
* Get wordpress post data
*/
function getPostData($post_id) {

	$body = get_post_field('post_content', $post_id);
	$terms = get_the_terms($post_id, 'category');
	$cats = array();
	
	foreach ($terms as $term) {
		$cats[] = $term->name;
	}

	$postTags = wp_get_post_tags($post_id);
	$tags = array();
	
	foreach ($postTags as $postTag) {
		$tags[] = $postTag->name;
	}

    $data = array(
    	'title' => get_the_title($post_id),
    	'body' => $body,
    	'permalink' => get_permalink($post_id),
    	'tags' => $tags,
    	'images' => getImgSrc($body),
    	'categories' => $cats
	);
	
	return $data;
}


/**
* Get all images from post
*/
function getImgSrc($postContent) {

	$images = array();
	$this_url = site_url();

	$doc = new DOMDocument();
	@$doc->loadHTML($postContent);
	$tags = $doc->getElementsByTagName('img');
	
	foreach ($tags as $tag) {
    	   
		if (strlen(strstr($tag->getAttribute('src'),$this_url))>0) {
		
			$images[] = $tag->getAttribute('src');
		
		}	   
	}
	
	return $images;
}


/**
* Swap all images and hrefs from post content
*/
function swapElements($postContent, $newElements) {

	$imgUrl = array();
	$this_url = site_url();

	$doc = new DOMDocument();
	@$doc->loadHTML($postContent);
	$imgs = $doc->getElementsByTagName('img');
	$links = $doc->getElementsByTagName('a');
	
	foreach ($newElements as $newElement) {
  
		$imgUrl[] = $newElement['url'];
    }

    $count = 0;
	foreach ($imgs as $img) {
    	
    	$src = $img->getAttribute('src');
    	 if (strlen(strstr($src,$this_url))>0) {

    		$postContent = str_replace($src, $imgUrl[$count], $postContent);
    		$count++;

    	 }
	}

	$count = 0;
	foreach ($links as $link) {

    	$href = $link->getAttribute('href');
    	$postContent = str_replace($href, $imgUrl[$count], $postContent);
    	$count++;
	
	}

	return $postContent;
}



/**
 * Generic functions to show messages and alerts to admin user
 */
function showMessage($message, $errormsg = false)
{
	if ($errormsg) {
		
		echo '<div id="message" class="error">';
	
	} else {
		
		echo '<div id="message" class="updated fade">';
	}

	echo "<p><strong>$message</strong></p></div>";
}    



/**
 * Determine error states and display
 */
add_action('admin_notices', 'showAdminMessages');    

function showAdminMessages() {
	global $ivn_plugin_options, $ivn_version;
    
	$ver = versionCheck();
	$ver_pieces = explode("|", $ver);

 	$current_wp_ver = get_bloginfo('version');
   
   	if	($ivn_version < $ver_pieces[0]) {
       showMessage("The IVN plugin is updated and is no longer supported. Please update your IVN plugin by contacting <a href='mailto:admin@ivn.us'>admin@ivn.us<a>.", $errormsg = true);
    }

 	if	($current_wp_ver < $ver_pieces[1]) {
       showMessage("This version of WordPress is not compatible with the IVN plugin. Please upgrade your WordPress to the newest version.", $errormsg = true);
    }

    if	((!$ivn_plugin_options['ivn_username']) || (!$ivn_plugin_options['ivn_password']) && strpos($_SERVER['REQUEST_URI'], 'ivn.php') === false) {
       showMessage("IVN plugin must be configured. Go to the <a href='options-general.php?page=ivn-publish/ivn.php'>admin page</a> to configure the plugin with your IVN credentials.", $errormsg = true);
    }

}


/**
 * Call back and version check 
 */
function versionCheck() {
	global $ivn_plugin_options, $ivn_version, $ver_url;

	$xml = simplexml_load_file($ver_url);
	$verOutput = array();

	if($xml) {

		$plugin_ver = $xml->version;
   		$wp_ver = $xml->wordpress;
	}

	$verOutput = $plugin_ver.'|'.$wp_ver;
	return $verOutput;
}
?>