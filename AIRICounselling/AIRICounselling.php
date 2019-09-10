<?php
/**
 * Plugin Name: AIRICounselling
 * Plugin URI: https://github.com/AIRIOpenLab/AIRIplugin
 * Description: Plugin per le pagine di AIRICounselling.
 * Version: 1.1.0
 * Author: Nicola Romanò
 * Author URI: https://github.com/nicolaromano
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

function AIRICounselling_load_custom_scripts() 
	{
	$page_ID = get_the_ID();
	//echo $page_ID;
	$PLUGIN_BASE = $_SERVER['DOCUMENT_ROOT']."/wp-content/plugins/AIRICounselling/";

	if ($page_ID == 10844) // Pagina AIRICounselling
		{
		//$f = fopen($_SERVER['DOCUMENT_ROOT']."/wp-content/plugins/AIRICounselling/gmaps.txt", "r");
		$f = fopen($PLUGIN_BASE."/leaflet.txt", "r");
		$key = trim(fgets($f));
		fclose($f);
		  
		wp_register_style( 'leafletcss', 'https://unpkg.com/leaflet@1.5.1/dist/leaflet.css' );
		wp_enqueue_style( 'leafletcss' );
		
		wp_enqueue_script('leaflet', 'https://unpkg.com/leaflet@1.5.1/dist/leaflet.js' );
		
		//wp_enqueue_script('google-maps', "https://maps.googleapis.com/maps/api/js?key=".$key);
		//wp_enqueue_script('initPage', plugins_url('AIRICounselling.js', __FILE__), array("jquery", "google-maps"), '1', true);
		wp_enqueue_script('initPage', plugins_url('AIRICounselling.js', __FILE__), array("jquery", "leaflet", "leafletcss"), '1', true);
		
		wp_localize_script('initPage', 'DataTablesLoadData', array('ajaxURL' => admin_url('admin-ajax.php'), 'leafletkey' => $key));
		}
	}

add_action('wp_enqueue_scripts', 'AIRICounselling_load_custom_scripts');


/******************* AJAX *******************/

add_action('wp_ajax_AIRICounselling_get_centres', 'AIRICounselling_get_centres_AJAX');
add_action('wp_ajax_nopriv_AIRICounselling_get_centres', 'AIRICounselling_get_centres_AJAX');

function AIRICounselling_get_centres_AJAX()
	{
	global $wpdb;

	$res = $wpdb->get_results("SELECT id, lon, lat, name, description, website, phone FROM wp_counselling_centri");
		
	$ids = array();
	$coord = array();
	$nomi = array();
	$descrizioni = array();
	$siti = array();
	$tel = array();
	
	foreach($res as $centro)
		{
		$ids[] = $centro->id;
		$coord[] = array($centro->lon, $centro->lat);
		$nomi[] = $centro->name;
		$descrizioni[] = $centro->description;
		$siti[] = $centro->website;
		$tel[] = $centro->phone;
		}
	
	echo json_encode(array("ids" => $ids, "coord" => $coord, "nomi" => $nomi, "descrizioni" => $descrizioni, "siti" => $siti, "tel" => $tel));
	
	wp_die();
	}

/******************* SHORTCODES *******************/

function AIRICounsellingCentres()
	{
	return "<div id='map' style='width: 750px; height: 500px;'></div>";
	}

add_shortcode('AIRICounselling-centres', 'AIRICounsellingCentres');
