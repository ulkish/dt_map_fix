<?php
/**
* Plugin Name: Distributor Map Fix
* Plugin URI: https://wordpress.org/plugins/multisite-cloner
* Description: Fixes integration between cloned posts and the Distributor plugin.
* Version: 1.0
* Author: Hugo Moran
* Author URI: http://tipit.net
* License: License: GPL2+
**/


function dt_map_fixer(int $latest_clone_site_id, int $cloned_site_id ) {

	// Search through cloned site for distributed posts
	switch_to_blog( $cloned_site_id );
	$args = array(
		'meta_key' => 'dt_original_blog_id',
		'post_type' => 'any',
		'posts_per_page' => -1,
	);
	$posts_query = new WP_Query( $args );
	$distributed_posts = $posts_query->posts;

	// Getting the original blog ID and post ID from every distributed post.
	$og_blog_and_post_ids = array();
	foreach ( $distributed_posts as $post ){
		//Find its original blog ID, original post ID, then add the new dt connection.
		$original_blog_id = get_post_meta( $post->ID, 'dt_original_blog_id' )[0];
		$original_post_id = get_post_meta( $post->ID, 'dt_original_post_id' )[0];
		$og_blog_and_post_id = array(
								'blog_id' => $original_blog_id,
								'post_id' => $original_post_id,
								);
		array_push($og_blog_and_post_ids, $og_blog_and_post_id);
	}

	$current_blog_id = 0;
	foreach ( $og_blog_and_post_ids as $post ) {
		if ( $post['blog_id'] !== $current_blog_id ) {
			switch_to_blog( $post['blog_id'] );
			$current_blog_id = $post['blog_id'];
		}
		$dt_connection = get_post_meta( $post['post_id'], 'dt_connection_map' );
		$dt_connection[0]['internal'][$latest_clone_site_id] = $dt_connection[0]['internal'][$cloned_site_id];
		update_post_meta( $post['post_id'], 'dt_connection_map', $dt_connection[0] );
	}
	restore_current_blog();
}



add_action( 'admin_menu', 'dtmf_settings_page' );
function dtmf_settings_page() {
	add_menu_page( 'DT Map Fixer Page', 'DT Map Fixer', 'manage_options', 'dt_map_fix/dt_map_fix.php', 'dtfm_admin_page', '', 6  );
}

function dtfm_admin_page() {
	$network_sites = get_sites();
	$plugin_url = admin_url('admin.php?page=dt_map_fix%2Fdt_map_fix.php');
	?>
	<div class="wrap">
		<h2>Distributor Map Fixer</h2>
		<p>Since an original site would not know about any Distributor connections
		in the duplicate of a site containing connections, this fix<br>connects the original
		with the latest created clone.
		</p>
		<form action="<?php echo $plugin_url ?>" method="POST">
			<table class="form-table">
			<tr>
				<th scope="row"><label for="latest">Latest cloned site:</label></th>
				<td>
					<select name="latest" id="latest">
					<?php foreach ($network_sites as $site) {
							echo '<option value="'
							. $site->blog_id
							. '" name="'
							. $site->domain
							. '"' . '>'
							. $site->domain
							. '</option>';
							}
					?>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="cloned">Cloned site:</label></th>
				<td>
					<select name="cloned" id="cloned">
					<?php foreach ($network_sites as $site) {
							echo '<option value="'
							. $site->blog_id
							. '" name="'
							. $site->domain
							. '"' . '>'
							. $site->domain
							. '</option>';
							}
					?>
				 </select>
				</td>
			</tr>
			</table>
			<p class="submit">
				<input type="submit" class="button button-primary"value="Fix it">
			</p>
		</form>
	</div>
	<?php

	if( (isset($_POST['latest']) && isset($_POST['cloned'])) && $_SERVER['REQUEST_METHOD'] == "POST") {
		dt_map_fixer($_POST['latest'], $_POST['cloned']);
		echo "Fix succesfully executed.";
	} else {  }
}

