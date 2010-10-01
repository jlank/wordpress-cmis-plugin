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

function build_list($objs, $folder) {

	$objlist = '<ul>';
	$objlist .= '<li class=folder><a href=' . get_option('siteurl') . '?cmis_show_folder=' . substr($folder->properties['cmis:path'], 0, -strlen(strrchr($folder->properties['cmis:path'], "/"))) . '>Parent folder</a></li>';
    foreach ($objs->objectList as $obj)
    {
        if ($obj->properties['cmis:baseTypeId'] == 'cmis:document')
        {
        	$liclass = get_li_class($obj->properties['cmis:contentStreamMimeType']);
            $objlist .= '<li class='.$liclass.'>' . build_doc_link($obj->properties['cmis:objectId'], $obj->properties['cmis:name']) . '</li>';
        }
        elseif ($obj->properties['cmis:baseTypeId'] == 'cmis:folder')
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
?>