<?php
/*
	Plugin Name: WordPress CMIS plugin
	Plugin URI: http://www.unorganizedmachines.com
	Description: CMIS integration for WordPress
	Version: 0.1
	Author: Nathan McMinn
	Author URI: http://www.unorganizedmachines.com
	License: MIT
*/
/*  Copyright (c) 2010 Nathan McMinn

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.

*/

include('cmis_repository_wrapper.php');

add_action('admin_menu', 'cmis_admin_actions');
add_action('plugins_loaded', 'cmis_display_actions');
add_action('get_header', 'cmis_set_head');
// register Foo_Widget widget
add_action('widgets_init', create_function('', 'register_widget("cmis_folder_widget");'));
add_filter('the_content', 'cmis_parse', 4);

function cmis_admin() {
	include('cmis_admin.php');
}

function cmis_admin_actions() {
	add_options_page(__('CMIS Document Display','menu-cmis'), __('CMIS Document Display','menu-cmis'), 'manage_options', 'cmissettings', 'cmis_admin');
}

function cmis_set_head() {
	echo '<link type="text/css" rel="stylesheet" href="' . get_option('siteurl') . '/wp-content/plugins/cmis/css/cmis.css" />' . "\n";
}

function cmis_display_actions() {
	$client = new CMISService(get_option('cmis_repo_url'), get_option('cmis_user'), get_option('cmis_pass'));
	$dlfile = $_GET['cmis_dl_id'];

	if($dlfile != '')
	{
		$props = $client->getProperties($dlfile);

		$file_name = $props->properties['cmis:name'];
		$file_size = $props->properties['cmis:contentStreamLength'];
		$mime_type = $props->properties['cmis:contentStreamMimeType'];

	    header('Content-type: $mime_type');
	    header('Content-Disposition: attachment; filename="'.$file_name.'"');
		header("Content-length: $file_size");

		$content = $client->getContentStream($dlfile);

		echo $content;
	}
}

function cmis_listdocuments() {

	$showfolder = $_GET['cmis_show_folder'];

	try {
		$client = new CMISService(get_option('cmis_repo_url'), get_option('cmis_user'), get_option('cmis_pass'));
		if($showfolder != '') {
			$folder = $client->getObjectByPath($showfolder);
		}else {
			$folder = $client->getObjectByPath(get_option('cmis_display_folder'));
		}
		$ret = '<h3>' . $folder->properties['cmis:path'] . '</h3>';
		$objs = $client->getChildren($folder->id);
		$ret .= build_list($objs, $folder);
	}catch (Exception $e) {
    	$ret = "Error retrieving documents: $e";
  	}

	return $ret;
}

function build_list($objs, $folder, $folders=TRUE) {

	$objlist = '<ul>';
        if ($folders===TRUE) {
		$objlist .= '<li class=folder><a href=' . get_option('siteurl') . '?cmis_show_folder=' . substr($folder->properties['cmis:path'], 0, -strlen(strrchr($folder->properties['cmis:path'], "/"))) . '>Parent folder</a></li>';
	}
    foreach ($objs->objectList as $obj)
    {
        if ($obj->properties['cmis:baseTypeId'] == 'cmis:document')
        {
        	$liclass = get_li_class($obj->properties['cmis:contentStreamMimeType']);
            $objlist .= '<li class='.$liclass.'>' . build_doc_link($obj->properties['cmis:objectId'], $obj->properties['cmis:name']) . '</li>';
        }
        elseif ($obj->properties['cmis:baseTypeId'] == 'cmis:folder' && $folders === TRUE)
        {
            $objlist .= '<li class=folder>' . build_folder_link($obj->properties['cmis:path'], $obj->properties['cmis:name']) . '</li>';
        }
    }

    $objlist .= '</ul>';

    return $objlist;
}

function get_li_class($mime){

	switch($mime)
	{
		case 'application/pdf';
			return 'pdf';
		break;
		case 'application/msword';
			return 'doc';
		break;
		case 'application/excel';
			return 'xls';
		break;
		case 'application/powerpoint';
			return 'ppt';
		break;
		case 'application/zip';
			return 'zip';
		break;
		case 'text/plain';
			return 'txt';
		break;
		default;
			return 'generic';
		break;
	}
}

function build_doc_link($obj_id, $obj_name) {

	$linky = '<a href=' . get_option('siteurl') . '?cmis_dl_id=' . urlencode($obj_id) .'>' . $obj_name . '</a>';

	return $linky;
}

function build_folder_link($path, $obj_name) {

	$linky = '<a href=' . get_option('siteurl') . '?cmis_show_folder=' . urlencode($path) .'>' . $obj_name . '</a>';

	return $linky;
}

function cmis_parse($the_content, $doExcerpt=false) {
	$pattern = '|\[cmis:(\/[\w\/ ]+)\]|';
	preg_match_all($pattern, $the_content, $matches,  PREG_SET_ORDER);
	foreach($matches as $match) {
		$client = new CMISService(get_option('cmis_repo_url'), get_option('cmis_user'), get_option('cmis_pass'));
		$folder = $client->getObjectByPath(urlencode($match[1]));
		$objs = $client->getChildren($folder->id);
		$new_content = build_list($objs, $folder, FALSE);
		$the_content = str_replace($match[0], $new_content, $the_content);
	}
	return $the_content;
}

/**
 * Adds Foo_Widget widget.
 */
class CMIS_Folder_Widget extends WP_Widget {

	/**
	 * Register widget with WordPress.
	 */
	public function __construct() {
		parent::__construct(
	 		'cmis_folder_widget', // Base ID
			'CMIS Folder', // Name
			array( 'description' => __( 'Display the contents of a CMIS folder', 'text_domain' ), ) // Args
		);
	}

	/**
	 * Front-end display of widget.
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	public function widget( $args, $instance ) {
		extract( $args );
		$title = apply_filters( 'widget_title', $instance['title'] );

		echo $before_widget;
		if ( ! empty( $title ) )
			echo $before_title . $title . $after_title;
		//echo __( 'Hello, World!', 'text_domain' );
		$showfolder = urlencode($instance['folderpath']);

		if (!empty($showfolder)) {
			$ret = "";
			try {
				$client = new CMISService(get_option('cmis_repo_url'), get_option('cmis_user'), get_option('cmis_pass'));
				$folder = $client->getObjectByPath($showfolder);
				if ($folder) {
					if (empty($title)) {
						$title = $folder->properties["cmis:name"];
						echo $before_title . $title . $after_title;
					}
					$objs = $client->getChildren($folder->id);
					$ret .= build_list($objs, $folder, FALSE);
				}
				else {
					$ret = "Folder not found: " . $showfolder;
				}
			} catch (Exception $e) {
				$ret = "Error retrieving documents: $e";
			}
			echo $ret;
		}

		echo $after_widget;
	}

	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @see WP_Widget::update()
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 *
	 * @return array Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = strip_tags( $new_instance['title'] );
		$folderpath = $new_instance['folderpath'];
		if (strpos($folderpath, "/") !== 0) {
			$folderpath = "/".$folderpath;
		}
		$instance['folderpath'] = $folderpath;

		return $instance;
	}

	/**
	 * Back-end widget form.
	 *
	 * @see WP_Widget::form()
	 *
	 * @param array $instance Previously saved values from database.
	 */
	public function form( $instance ) {
		if (!empty( $instance[ 'title' ] ) ) {
			$title = $instance[ 'title' ];
		}
		else {
			//$title = __('New title', 'text_domain');
			$title = __('', 'text_domain');
		}
		if ( isset( $instance[ 'folderpath' ] ) ) {
			$folderpath = $instance[ 'folderpath' ];
		}
		else {
			$folderpath = __(get_option('cmis_display_folder'), 'text_domain');
		}
		?>
		<p>
		<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title (Optional):' ); ?></label> 
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		<label for="<?php echo $this->get_field_id('folder'); ?>"><?php _e( 'Folder:' ); ?></label> 
		<input class="widefat" id="<?php echo $this->get_field_id('folderpath'); ?>" name="<?php echo $this->get_field_name('folderpath'); ?>" type="text" value="<?php echo esc_attr($folderpath); ?>" />
		</p>
		<?php 
	}

} // class CMIS_Folder_Widget
?>
