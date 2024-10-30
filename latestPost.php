<?php 
/* 
Plugin Name: Latest Post 
Plugin URI: http://www.bluefountainmedia.com
Description: Display a different style for your most recent post on your index.php
Version: 0.1
Author: Bryan Mytko
Author URI: http://www.bluefountainmedia.com/

--GNU License
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  

See the GNU General Public License for more details:
http://www.gnu.org/licenses/gpl.txt

*/

//get settings set in wp-admin
$getSettings = get_option('getSettings');

//get the latest entry
function latestExcerpt($t) {
	
	global $getSettings,$post;
	$post = new WP_Query('ID ORDER BY post_date ASC LIMIT 1');
	$post->the_post();
	
	$t = get_the_content('');
	$t = apply_filters('the_content',$t);
	$t = str_replace(']]>',']]&gt;',$t);
	$t = strip_tags($t);
	$w = explode(' ',$t,$getSettings['displayWords'] + 2);
	array_pop($w);
	$t = implode(' ',$w);
	
	//offset the query so the latest entry doesn't show up twice
	query_posts('posts_per_page=14&offset=1');
	$r = "<a href=\"".get_permalink($post->ID)."\">".$getSettings['readMore']."</a>";
	return $t.'... '.$r;
}

function latestPost(){
	
	global $getSettings,$post;
	$post = new WP_Query('ID ORDER BY post_date ASC LIMIT 1');
	$post->the_post();
	
	$p = '<p>%s</p>';
	
	$i =& get_children('post_type=attachment&post_mime_type=image&post_parent=' . $post->ID);
	$image_url = wp_get_attachment_image_src(array_shift(array_keys($i)));
	//var_dump($image_url);
	$image_url = $image_url[0];
	
	echo "<div id=\"latestPost\">";
	echo "<h2 class=\"latestTitle\"><a class=\"latestLink\" href=\"".get_permalink($post->ID)."\">".$post->post_title."</a></h2>";
	echo "<em><?php the_time('M'); ?></em>";
	echo "<span><?php the_time('j'); ?></span>";
                       
	if ($getSettings['imgShow']){
		if(($getSettings['imgHeight']) && ($getSettings['imgWidth'])){
			echo "<img class=\"latestImg\" src=\"".$image_url."\" alt=\"\" height=\"".$getSettings['imgHeight']."\" width=\"".$getSettings['imgWidth']."\" />";
		}
		else {
			echo "<img src=\"".$image_url."\" alt=\"foo\" />";
		}
	}
	
	echo "<div class=\"latestDiv\">";
	printf($p,latestExcerpt($post));
	echo "</div>";
	
	echo "<p class=\"latestComment\">".comments_popup_link('Leave a Comment &#187;', '1 Comment &#187;', 'Comments (%)')."</p>";
	echo "</div>";
}

add_action('admin_menu', 'latestOptions');

function latestOptions() {
  add_options_page('Latest Post Options', 'Latest Post', 'manage_options', 'latestoptionspage', 'latestOptionsPage');
}

function latestOptionsPage() {
	
	global $getSettings,$_POST;
	
	if (!empty($_POST)) {
		if(isset($_POST['displayWords']))
			$getSettings['displayWords'] = $_POST['displayWords'];
		if(isset($_POST['imgShow']))
			$getSettings['imgShow'] = $_POST['imgShow'];
		if(isset($_POST['imgWidth']))
			$getSettings['imgWidth'] = $_POST['imgWidth'];
		if(isset($_POST['imgHeight']))
			$getSettings['imgHeight'] = $_POST['imgHeight'];
		if(isset($_POST['readMore']))
			$getSettings['readMore'] = $_POST['readMore'];
		if(isset($_POST['customCss']))
			saveCss($_POST['customCss']);
		
	update_option('getSettings',$getSettings);
		echo '<div id="message" class="updated fade"><p>Your settings have been saved.</p></div>';
	}	
	
	if (!current_user_can('manage_options'))  {
	wp_die( __('You do not have sufficient permissions to access this page.') );
	}

	echo "<div class=\"wrap\">";
	echo "<h2>Latest Post Options</h2>";
	echo "<form action=\"\" method=\"post\">";
	echo "How many words to display: <input type=\"text\" name=\"displayWords\" value=\"".$getSettings['displayWords']."\" />";
	
	if ($getSettings['imgShow'])
	echo "<p>Display Image?: Yes <input type=\"radio\" name=\"imgShow\" value=\"1\" checked /> No <input type=\"radio\" name=\"imgShow\" value=\"0\" /></p>";
	else
		echo "<p>Display Image?: Yes <input type=\"radio\" name=\"imgShow\" value=\"1\"  /> No <input type=\"radio\" name=\"imgShow\" value=\"0\" checked /></p>";
	
	echo "<p>Image Size: Width <input type=\"text\" name=\"imgWidth\" value=\"".$getSettings['imgWidth']."\" size=\"4\" /> Height <input type=\"text\" name=\"imgHeight\" value=\"".$getSettings['imgHeight']."\"  size=\"4\" />";
	echo "<p>Custom \"Read More\" Tag: <input type=\"text\" name=\"readMore\" value=\"".$getSettings['readMore']."\" /></p>";
	echo "<hr />";
	echo "<p style=\"vertical-align:top;\">Custom CSS:</p><p> <textarea cols=\"100\" rows=\"10\" name=\"customCss\" />".$getSettings['customCss'].getCss()."</textarea></p>";
	echo "<p><input type=\"submit\" value=\"Save\" /></p>";
	echo "</div>";
}

function latestActive() {
	
	global $getSettings;
	
	$getSettings = array('customCss' => '', 'displayWords' => 50,'imgShow' => 'yes','imgWidth' => '','imgHeight' => '', 'readMore' => 'Read More');
	
	if (!get_option('getSettings'))
		add_option('getSettings' , $getSettings);
	else
		update_option('getSettings' , $getSettings);
}

register_activation_hook( __FILE__, 'latestActive');
register_deactivation_hook( __FILE__, 'latestDeactive');

function latestDeactive() {
	delete_option('getSettings');
}

function latestHeader() {
	echo "<link rel=\"stylesheet\" href=\"".get_option("siteurl")."/wp-content/plugins/latestPost/style.css\" type=\"text/css\" media=\"screen\" />";
}

function getCss() {

	$file = "../wp-content/plugins/latestPost/style.css";
	$cssOpen = fopen($file,'r') or die('DIED: ERROR OPENING<b>'.$file.'</b>');
	$latestCss = '';
	while(!feof($cssOpen)){
		$latestCss .= fread($cssOpen,sizeof($cssOpen));
	}
	fclose($cssOpen);
	return $latestCss;
}

function saveCss($a){
	$file = "../wp-content/plugins/latestPost/style.css";
	$cssOpen = fopen($file,'w') or die('ERROR OPENING<b>'.$file.'</b>');
	fwrite($cssOpen,$a);
	fclose($cssOpen);
}
		

add_action('wp_head','latestHeader');

