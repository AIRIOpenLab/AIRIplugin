<?php
/**
 * Plugin Name: AIRIGuida
 * Plugin URI: https://github.com/AIRIOpenLab/AIRIplugin
 * Description: Plugin per la Guida intergalattica per Phdstoppisti.
 * Version: 1.1.0
 * Author: Marcello Barisonzi
 * Author URI: https://github.com/mbarison
 * License: GPL3
 */
 
/*   Copyright (C) 2019 Marcello Barisonzi (marcello.barisonzi@gmail.com)

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

function AIRIGuida_load_custom_scripts() 
	{
	$page_ID = get_the_ID();
	//echo $page_ID;
	$PLUGIN_BASE = $_SERVER['DOCUMENT_ROOT']."/wp-content/plugins/AIRIGuida/";

	if ($page_ID == 13273 || $page_ID == 4333 ) // Pagina Guida
		{
		$f = fopen($PLUGIN_BASE."/leaflet.txt", "r");
		$key = trim(fgets($f));
		fclose($f);
		  
		wp_register_style( 'leafletcss', 'https://unpkg.com/leaflet@1.5.1/dist/leaflet.css' );
		wp_enqueue_style( 'leafletcss' );
		
		wp_enqueue_script('leaflet', 'https://unpkg.com/leaflet@1.5.1/dist/leaflet.js' );
		
		wp_enqueue_script('initPage', plugins_url('AIRIGuida.js', __FILE__), array("jquery", "leaflet"), '1', true);
		
		wp_localize_script('initPage', 'Leaflet', array('key' => $key));
		}
	}

add_action('wp_enqueue_scripts', 'AIRIGuida_load_custom_scripts');

/******************* SHORTCODES *******************/

function AIRIGuidaMap()
	{
	return "<div id='map' style='width: 750px; height: 500px;'></div>";
	}

add_shortcode('AIRIGuida-map', 'AIRIGuidaMap');
