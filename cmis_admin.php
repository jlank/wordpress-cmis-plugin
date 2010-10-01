<?php
	if($_POST['cmis_hidden'] == 'Y') {

	//Form data was sent, save to DB.
	$cmis_repo_url = $_POST['cmis_repo_url'];
	update_option('cmis_repo_url', $cmis_repo_url);

	$cmis_display_folder = $_POST['cmis_display_folder'];
	update_option('cmis_display_folder', $cmis_display_folder);

	$cmis_user = $_POST['cmis_user'];
	update_option('cmis_user', $cmis_user);

	$cmis_pass = $_POST['cmis_pass'];
	update_option('cmis_pass', $cmis_pass);

?>
<div class="updated"><p><strong><?php _e('Options saved.' ); ?></strong></p></div>
<?php
	} else {
		//Normal page display
		$cmis_repo_url = get_option('cmis_repo_url');
		$cmis_display_folder = get_option('cmis_display_folder');
		$cmis_user = get_option('cmis_user');
		$cmis_pass = get_option('cmis_pass');
	}
?>
<div class="wrap">
	<?php echo "<h2>" . __( 'CMIS Display Options', 'cmis_trdom' ) . "</h2>"; ?>

	<form name="cmis_form" method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
		<input type="hidden" name="cmis_hidden" value="Y">
		<?php echo "<h4>" . __( 'CMIS Repository Settings', 'cmis_trdom' ) . "</h4>"; ?>
		<p><?php _e("CMIS URL: " ); ?><input type="text" name="cmis_repo_url" value="<?php echo $cmis_repo_url; ?>" size="30"><?php _e(" ex: http://www.yourcmisserver.com/alfresco/service/cmis" ); ?></p>
		<p><?php _e("Document folder: " ); ?><input type="text" name="cmis_display_folder" value="<?php echo $cmis_display_folder; ?>" size="20"><?php _e(" ex: path/to/your/docs" ); ?></p>
		<p><?php _e("CMIS user: " ); ?><input type="text" name="cmis_user" value="<?php echo $cmis_user; ?>" size="20"><?php _e(" ex: username" ); ?></p>
		<p><?php _e("CMIS password: " ); ?><input type="password" name="cmis_pass" value="<?php echo $cmis_pass; ?>" size="20"><?php _e(" ex: password" ); ?></p>
		<p class="submit">
		<input type="submit" name="Submit" value="<?php _e('Update Options', 'cmis_trdom' ) ?>" />
		</p>
	</form>
</div>