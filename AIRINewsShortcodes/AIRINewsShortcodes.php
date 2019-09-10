<?php
/**
 * Plugin Name: AIRINewsShortcodes
 * Plugin URI: https://github.com/AIRIOpenLab/AIRIplugin
 * Description: Shortcodes per le news aggiungere la firma nelle pagine delle news.
 * Version: 1.1.0
 * Author: Nicola Romanò
 * Author URI: https://github.com/nicolaromano
 * License: GPL3
 */
 
/*  Copyright 2016 Nicola Romanò (romano.nicola@gmail.com)

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

// Loading condizionale di Javascript
/******************* SHORTCODES *******************/

// [info-autore]
function an_info_autore($atts)
	{
	$slug = isset($atts["nome"]) ? sanitize_title($atts["nome"]) : "";
	
	// Non vogliamo scrivere nulla se l'autore non è stato specificato
	if ($slug == "")
		return;
			
	$args = array(
		'name' => $slug,
		'post_type' => 'pro_team_posts',
		'post_status' => 'publish'
		);
	$my_posts = get_posts($args);

	if ($my_posts)
		{
		$img = get_the_post_thumbnail($my_posts[0]->ID, array(60, 60), array('class' => 'valignmiddle',  'itemprop' => 'photo'));
		$autore = $my_posts[0]->post_title;
		
		$twitter = get_post_meta($my_posts[0]->ID, "ultimate_pro_team_members_social_");
		
		$twitterURL = "";
		
		if (isset($twitter[0]))
			{
			foreach ($twitter[0] as $social)
				{
				if ($social["ultimate_pro_team_re_members_social_icon"] == "fa-twitter")
					$twitterURL = $social["ultimate_pro_team_re_members_social_url"];
				}
			}
			
		$txt = "<br /><hr /><div itemscope='' itemprop='author' itemtype='https://schema.org/Person'><meta itemprop='affiliation' content = 'AIRIcerca'/>".$img."&nbsp; Notizia a cura di <span itemprop='name' class='vcard author'><span class='fn'>$autore</span></span>  
			<a href='mailto:news@airicerca.org'><img style='background-color: #4B61D1; vertical-align: middle; border-radius:20px;' src=".plugins_url('Email.png', __FILE__)." /></a>&nbsp;";
		
		if ($twitterURL != "")
			$txt .= "<a href='$twitterURL' target='_blank'><img style='background-color: #4B61D1; vertical-align: middle; border-radius:20px;' src=".plugins_url('Twitter.png', __FILE__)." /></a>";
		
		$txt .= "<br /></div>";
		
		//<div id='bio-news' style='display:none'>".$my_posts[0]->post_content."</div></div>";
		}
	else
		{
		$txt = "";
		}
				
 	return $txt;
 	}

add_shortcode('info-autore', 'an_info_autore');

function an_display_prev_next()
	{
	wp_link_pages();
	$prev = get_previous_post(TRUE);
	$next = get_next_post(TRUE);

	echo '<div id="post-navigation">';

	$title_len_limit = 55;

	// Original PHP code by Chirp Internet: www.chirp.com.au
	// Please acknowledge use of this code by including this header.

	function truncate($string, $limit, $break=" ", $pad="&hellip;")
		{
		// Return with no change if string is shorter than $limit
		if (strlen($string) <= $limit) 
			return $string;

	// is $break present between $limit and the end of the string?
	if (false !== ($breakpoint = strpos($string, $break, $limit))) 
		{
		if ($breakpoint < strlen($string) - 1) 
			{
			$string = substr($string, 0, $breakpoint) . $pad;
			}
		}

		return $string;
		}
	
	if (!empty($prev))
		{
		$title = truncate($prev->post_title, $title_len_limit);
		
		echo '<div class="prev-post">'.
			'<span class="font-medium"><strong>NOTIZIA PRECEDENTE</strong></span><br />'.
			get_the_post_thumbnail($prev->ID, array(70, 70), array('class' => 'valignmiddle')).
			'<br /><a href="'.get_permalink($prev->ID).'">'.$title.'</a></div>';
		}
	if (!empty($next))
		{
		$title = truncate($next->post_title, $title_len_limit);

		echo '<div class="next-post"><span class="font-medium"><strong>NOTIZIA SUCCESSIVA</strong></span><br/>'.
		get_the_post_thumbnail($next->ID, array(70, 70), array('class' => 'valignmiddle')).
		'<br /><a href="'.get_permalink($next->ID).'">'.$title.'</a></div>';
		}
	
	echo '</div>';
	}

function an_get_related()
	{
//	if (get_current_user_id() != 60)
//		return;

	$tags = wp_get_post_tags(get_the_ID());
	$slugs = array();	
	foreach ($tags as $tag)
		{
		$slugs[] = $tag->slug;
		}

	$related = get_posts(array(
		'showposts' => 4,
		'orderby' => 'rand',
		'category_name' => 'Notizie',
		'tag' => $slugs
		));

	echo '<div class="font-medium"><strong>NOTIZIE CORRELATE</strong></div><br />';

	echo "<table><tr>";

	foreach ($related as $r)
		{
		$thumbnail = get_the_post_thumbnail($r->ID, array(150, 150), array('style' => 'align:center'));

		if ($thumbnail == "")
			{
			$thumbnail = '<img width="150" height="150" src="http://www.airicerca.org/wp-content/uploads/2014/03/airi-mark-square.jpg" style="align:center">';
			}
		
		echo "<td style='text-align:center' width='25%'>".$thumbnail.
			'<br /><a href="'.get_permalink($r->ID).'">'.$r->post_title.'</a></td>';
		}
	echo "</tr></table>";
	}
?>
