<?php
/**
 * Plugin Name: AIRIPortal
 * Plugin URI: https://github.com/AIRIOpenLab/AIRIplugin
 * Description: Plugin per la gestione della homepage.
 * Version: 1.0.1
 * Author: Nicola Romanò
 * Author URI: https://github.com/nicolaromano
 * License: GPL3
 */
 
/*   Copyright (C) 2016 Nicola Romanò (romano.nicola@gmail.com)

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

/******************* LOAD JAVASCRIPT & CSS *******************/

function AIRIPortal_load_custom_scripts() 
	{
	$page_ID = get_the_ID();
	//echo $page_ID;

	if ($page_ID == 8464) // Pagina AIRIbandi
		{
		wp_enqueue_style('AIRIPortalCSS', plugins_url('AIRIPortal.css', __FILE__ ));
		wp_enqueue_script('initPage', plugins_url('AIRIPortal.js', __FILE__), array("jquery"), '1', true);
		wp_localize_script('initPage', 'DataTablesLoadData', array('ajaxURL' => admin_url('admin-ajax.php')));
		}
	}

add_action('wp_enqueue_scripts', 'AIRIPortal_load_custom_scripts');


/******************* AJAX *******************/

add_action('wp_ajax_AIRInforma_get_latest', 'AIRInforma_get_latest_AJAX');
add_action('wp_ajax_nopriv_AIRInforma_get_latest', 'AIRInforma_get_latest_AJAX');

add_action('wp_ajax_AIRISocial_get_latest', 'AIRISocial_get_latest_AJAX');
add_action('wp_ajax_nopriv_AIRISocial_get_latest', 'AIRISocial_get_latest_AJAX');

function AIRInforma_get_latest_AJAX()
	{
	// AIRInforma
	switch_to_blog(12);

	$args = array(  "posts_per_page"	=> 5,
			"category"		=> get_category_by_slug("articoli-airinforma"),
			"tax_query" => array(
				array(
				'taxonomy' => 'category',
				'field' => 'name',
				'terms' => 'Articoli AIRInforma'
				))
			);
	$last_articles = get_posts($args);

	$res = array();
	$taxonomies = get_taxonomies('', 'names');

	foreach($last_articles as $a)
		{
		$res[] = array(	"post_title" => $a->post_title,
				"post_URL" => get_permalink($a),
				"post_image" => wp_get_attachment_url(get_post_thumbnail_id($a->ID))
				#"taxonomies" => $taxonomies,
				#"terms" => wp_get_post_terms($a->ID, "category", array("fields" => "names"))
				);
		}
	echo json_encode($res);

	// AIRIcerca
	switch_to_blog(1);

	wp_die();
	}

function AIRISocial_get_latest_AJAX()
	{
	// AIRISocial
	switch_to_blog(13);

	$args = array(  "posts_per_page"	=> 10
			#"category"		=> get_category_by_slug("articoli-airinforma"),
			);
	$last_articles = get_posts($args);

	$res = array();
	$taxonomies = get_taxonomies('', 'names');

	foreach($last_articles as $a)
		{
		$res[] = array(	"post_title" => $a->post_title,
				"post_URL" => get_permalink($a),
				"post_image" => wp_get_attachment_url(get_post_thumbnail_id($a->ID))
				);
		}
	echo json_encode($res);

	// AIRIcerca
	switch_to_blog(1);

	wp_die();
	}

/******************* SHORTCODES *******************/

function airinewsLatest()
	{
	$num = 4;

	$txt = "";

	$args = array(  "posts_per_page"	=> $num,
			"category_name" 	=> "Notizie");

	$last_news = get_posts($args);

	$txt .= "<h2 align='center'>Le ultime notizie</h2>";

	$txt .= "<table><tr>";

	foreach ($last_news as $n)
		{
		$txt .= "<td style='padding:1.5em; border-right:2px dotted gray;' width='".(100/(int)$num)."%'>";

		$thumb = get_the_post_thumbnail($n, $size = array(150, 150));
		if ($thumb == "")
			{
			$thumb = '<img width="150" height="150" src="http://www.airicerca.org/wp-content/uploads/2014/03/airi-mark-square.jpg" style="align:center">';
			}

		$txt .= "<div align='center'>".$thumb."</div><br />";
		$txt .= "<div align='center'><strong><a href='".get_permalink($n)."' target='_blank'>".$n->post_title."</a></strong></div>";

		$txt .= "</td>";
		}

	$txt .= "</tr></table>";

	return $txt;
	}

function airinformaLatest()
	{
	$txt = "";

	$txt .= "<div align='center'><img src='http://informa.airicerca.org/wp-content/uploads/sites/12/2016/07/cropped-AIRInforma-logo_smaller.png' width='200'/></div>";
	$txt .= "<h3 align='center'>Articoli divulgativi scritti dai ricercatori</h3>";
#	$txt .= "<h2 align='center'>I nostri ultimi articoli</h2>";
	$txt .= "<div id='AIRInformaLatest' width='100%'><div class='ap_spinner'></div></div>";

	return $txt;
	}

function airisocialLatest()
	{
	$txt = "";

	$txt .= "<div align='center'><img src='http://social.airicerca.org/wp-content/uploads/sites/13/2014/11/airisocial-logo.png' width='200'/></div>";
	$txt .= "<h3 align='center'>Scienza e media 2.0</h3>";
#	$txt .= "<h2 align='center'>AIRISocial</h2>";
	$txt .= "<div id='AIRISocialLatest' width='100%'><div class='ap_spinner'></div></div>";

	return $txt;
	}

add_shortcode('AIRINews-latest', 'airinewsLatest');
add_shortcode('AIRInforma-latest', 'airinformaLatest');
add_shortcode('AIRISocial-latest', 'airisocialLatest');

