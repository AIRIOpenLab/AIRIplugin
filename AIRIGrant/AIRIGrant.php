<?php
/**
 * Plugin Name: AIRIGrant
 * Description: Plugin per le pagine di AIRIGrant.
 * Version: 1.0.0
 * Author: Nicola Romanò
 * License: GPL3
 */
 
/*   Copyright (C) 2017 Nicola Romanò (romano.nicola@gmail.com)

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

function AIRIGrant_load_custom_scripts() 
	{
	$page_ID = get_the_ID();
	//echo $page_ID;

	if ($page_ID == 11708) // Pagina revisori AIRIGrant 
		{
		wp_enqueue_style('AIRIGrantCSS', plugins_url('AIRIGrant.css', __FILE__ ));
		wp_enqueue_script('autocomplete', plugins_url('jquery.autocomplete.js', __FILE__), array("jquery"), '1', true);
		wp_enqueue_script('initPage', plugins_url('AIRIGrant.js', __FILE__), array("jquery"), '1', true);
		}
	}

add_action('wp_enqueue_scripts', 'AIRIGrant_load_custom_scripts');
/*

/******************* AJAX *******************/

add_action('wp_ajax_AIRIGrant_get_user', 'AIRIGrant_get_user');
add_action('wp_ajax_nopriv_AIRIGrant_get_user', 'AIRIGrant_get_user');
add_action('wp_ajax_AIRIGrant_rem_revisore', 'AIRIGrant_rem_revisore');
add_action('wp_ajax_nopriv_AIRIGrant_rem_revisore', 'AIRIGrant_rem_revisore');
add_action('wp_ajax_AIRIGrant_add_revisore', 'AIRIGrant_add_revisore');
add_action('wp_ajax_nopriv_AIRIGrant_add_revisore', 'AIRIGrant_add_revisore');

function AIRIGrant_get_user()
	{
	$query = $_POST['query'];
	
	global $wpdb;
	
	$q = $wpdb->prepare('SELECT user_login, ID FROM wp_users WHERE user_login RLIKE "%s"', str_replace(' ', '', $query));
	$res = $wpdb->get_results($q);
	
	$users = array();
	foreach($res as $user)
		{
		$um = get_user_meta($user->ID);
				
		$users[] = $um["first_name"][0]." ".$um["last_name"][0]." (id=".$user->ID.")";
		}
	echo json_encode(array("query" => $query,
			       "suggestions" => $users));
			       
	wp_die();
	} 
	
function AIRIGrant_rem_revisore()
	{
	$id = (int)$_POST["id"];
	delete_user_meta($id, "revisoreGrant");
	delete_user_meta($id, "revisoreExperience");
	
	wp_die();
	}

function AIRIGrant_add_revisore()
	{
	$id = (int)($_POST["id"]);
	$exp = sanitize_text_field($_POST["exp"]);
	
	add_user_meta($id, "revisoreGrant", 1);
	add_user_meta($id, "revisoreExperience", $exp);
	
	wp_die();
	}

/******************* SHORTCODES *******************/

function AIRIGrantRevisori()
	{
	global $wpdb;
				
	$txt = "";
	
	$res = $wpdb->get_results("SELECT um.user_id FROM `wp_usermeta` um WHERE um.meta_key = 'revisoreGrant' AND um.meta_value = 1");
		
	$txt .= "<h1>Revisori AIRIGrant</h1>";
	
	foreach($res as $u)
		{
		$ud = get_userdata($u->user_id);
		
		$txt .= "<div id='revisore_".$u->user_id. "'><strong>" . get_user_meta($u->user_id, "first_name", true). " ". 
		get_user_meta($u->user_id, "last_name", true). "</strong> - <span style='font-size:0.7em'><a href = 'mailto:". 
		$ud->user_email . "'>" . $ud->user_email . "</a></span> <button id='rem_revisore_".$u->user_id.
		"' style='padding:0.2em; font-size:0.7em'>Elimina</button><br />".
		"<span style='margin-left: 3em;'>". get_user_meta($u->user_id, "revisoreExperience", true) ."</span></div>";
		}
		
	$txt .= "<hr />";
	$txt .= "<h2>Aggiungi un revisore</h2>";
	$txt .= "Nome utente: <input id='username' maxlength = '500' /><br /><br />
	         Campo di esperienza:<br /><textarea id='description' cols = '80' rows = '5' maxlength = '500'></textarea>
	         <br /><button id='add_revis'>Aggiungi revisore</button>";
	return $txt;
	}

add_shortcode('AIRIGrant-revisori', 'AIRIGrantRevisori');
?>