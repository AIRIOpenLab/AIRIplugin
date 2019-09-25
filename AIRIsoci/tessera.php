<?php
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

// 2016-01-16 - Cambiato lo script usando GD invece di Imagick in attesa di news da Bluehost
// sul perché Imagick abbia smesso di funzionare

// Queste due linee servono per caricare l'environment di WP (DB, funzioni tipo get_user_meta etc.) 
define('WP_USE_THEMES', false);
require '../../../wp-load.php';

$id = (int)$_GET['id'];
global $wpdb;
$res = $wpdb->get_results("SELECT membership_id FROM wp_pmpro_memberships_users WHERE user_id=$id");

switch($res[0]->membership_id)
	{
	case 1:
		$filename = "images/Tessera_fondatore.jpg";
	break;
	
	case 2:
		$filename = "images/Tessera_ordinario.jpg";
	break;
	
	case 3:
		$filename = "images/Tessera_onorario.jpg";
	break;
	
	default:
		wp_die();
	}
	
$nome = get_user_meta($id, "first_name", true);
$cognome = get_user_meta($id, "last_name", true);
$numero_tessera = get_user_meta($id, "card_number", true);

$im = imagecreatefromjpeg($filename);
#$image = new Imagick();
#$draw = new ImagickDraw();

#$handle = fopen($filename, 'rb');
#$image->readImageFile($handle);

// Il colore del nome del socio
switch($res[0]->membership_id)
	{
	case 1:
//		$namecolor = "rgb(238, 54, 41)";
		$namecolor = imagecolorallocate($im, 238, 54, 41);
	break;
	
	case 2:
//		$namecolor = "rgb(42, 56, 144)";
		$namecolor = imagecolorallocate($im, 42, 56, 144);
	break;
	
	case 3:
//		$namecolor = "rgb(166, 144, 49)";
		$namecolor = imagecolorallocate($im, 166, 144, 49);
	break;
	
	default:
		wp_die();
	}

$font_file = './futura-md-bt-medium.ttf';
$bb = imagettfbbox(43, 0, $font_file, "$nome $cognome");
imagettftext($im, 43, 0, 920-($bb[2]-$bb[0]), 513, $namecolor, $font_file, "$nome $cognome");

$numcolor = imagecolorallocate($im, 255, 255, 255);
imagettftext($im, 43, 0, 780, 602, $numcolor, $font_file, $numero_tessera);

#$draw->setFillColor($namecolor);

#/* Font properties */
#$draw->setFont($font_file);
#$draw->setTextAntialias(true);

#$draw->setFontSize(43);
#$draw->setTextAlignment(3);
#$image->annotateImage($draw, 920, 513, 0, "$nome $cognome");

#$draw->setTextAlignment(1);
#$draw->setFillColor("white");
#$draw->setFontSize(46);
#$image->annotateImage($draw, 780, 602, 0, $numero_tessera);

#/* Give image a format */
#$image->setImageFormat('pdf');
#$image->setImageUnits(imagick::RESOLUTION_PIXELSPERINCH);
#$image->setImageResolution(300, 290);

#/* Output the image with headers */
#//header('Content-type: application/pdf');

$out=0;

if (array_key_exists('out', $_GET))
    $out = (int)$_GET['out'];

if ($out == 1)
	{
	/* Output the image with headers */
	$path_img = "tmp/$numero_tessera.jpg";
	imagejpeg($im, $path_img, 100);
//	$image->writeImage("tmp/$numero_tessera.pdf");
	}
else
	{
	header('Content-type: image/jpeg');
	imagejpeg($im, NULL, 100);
//	echo $image;
	}

imagedestroy($im);
?>