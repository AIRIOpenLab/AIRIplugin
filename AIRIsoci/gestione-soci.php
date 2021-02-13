<?php
/**
 * Plugin Name: gestione-soci
 * Description: Un semplice plugin per la gestione dei soci di AIRIcerca.
 * Version: 0.3.0
 * Author: Nicola Romanò
 * License: GPL2
 */
 
/* Copyright 2015 Nicola Romanò (romano.nicola@gmail.com)

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as 
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301 USA
*/

// Loading condizionale di Javascript
function load_custom_scripts() 
	{
	$page_ID = get_the_ID();
	//echo $page_ID;
	if ($page_ID == 3355 || $page_ID == 3480) // Pagina iscrizione soci + amici
		{
		wp_enqueue_script('reCAPTCHA', 'https://www.google.com/recaptcha/api.js?hl=it', array(), '1', true);
		wp_enqueue_script('checkData', plugins_url( 'registration.js', __FILE__ ), array("jquery"), '1', true);
		}
	else if ($page_ID == 3614) // Gestione soci
		{
		wp_enqueue_style('DataTablesCSS', 'http://cdn.datatables.net/1.10.7/css/jquery.dataTables.css');
		wp_enqueue_script('DataTables', 'http://cdn.datatables.net/1.10.7/js/jquery.dataTables.js', array("jquery"), '1', true);
		wp_enqueue_script('initDataTable', plugins_url('gestione-iscrizioni.js', __FILE__ ), array("jquery"), '1', true);
		}
	else if ($page_ID == 4116)
		{
		wp_enqueue_script('checkData', plugins_url('setpwd.js', __FILE__ ), array("jquery"), '1', true);
		}
	else if ($page_ID == 6420) // Statistiche soci
		{
		wp_enqueue_script('GoogleAPI', 'https://www.google.com/jsapi', '1', array(), '1', true);
		wp_enqueue_script('initStats', plugins_url('statistiche-soci.js', __FILE__ ), array("jquery"), '1', true);
		}
	}

if (is_admin())
	{
	// Conferma pagamento ricercatore
	add_action('wp_ajax_act_conferma', 'conferma_callback');
	add_action('wp_ajax_nopriv_act_conferma', 'conferma_callback');
	// Conferma prova di affiliazione
	add_action('wp_ajax_act_approva', 'approva_prova_callback');
	add_action('wp_ajax_nopriv_act_approva', 'approva_prova_callback');
	// Accetta candidatura
	add_action('wp_ajax_act_accetta_cand', 'accetta_candidatura_callback');
	add_action('wp_ajax_nopriv_act_accetta_cand', 'accetta_candidatura_callback');
	// Rifiuta candidatura
	add_action('wp_ajax_act_rifiuta_cand', 'rifiuta_candidatura_callback');
	add_action('wp_ajax_nopriv_act_rifiuta_cand', 'rifiuta_candidatura_callback');
	// Conferma pagamento non ricercatore
	add_action('wp_ajax_act_conferma_nonric', 'conferma_pagamento_nonric_callback');
	add_action('wp_ajax_nopriv_act_conferma_nonric', 'conferma_pagamento_nonric_callback');
	// Restituisce le statistiche dei soci
	add_action('wp_ajax_act_stats', 'stats_soci_callback');
	add_action('wp_ajax_nopriv_act_stats', 'stats_soci_callback');
	}

	
add_action('wp_enqueue_scripts', 'load_custom_scripts');

// Callback function chiamata via AJAX quando si approva un pagamento
function conferma_callback()
	{
	global $wpdb;

	$ids = $_POST['id'];
	$ids = implode(",", $ids);

	$nextyear = max(2017, date("Y")+1);
	$endsub = date("Y-m-d 00:00:00", mktime(0, 0, 0, 1, 1, $nextyear));

	// Aggiungiamo una entry nella tabella del plugin delle memberships
	$q = $wpdb->prepare("UPDATE wp_pmpro_memberships_users SET enddate = '$endsub', status='active' WHERE user_id IN ($ids)");
	$res = $wpdb->query($q);
	//echo "Aggiornamento membership: $res";

	$email = array();
	$ids = explode(",", $ids);
	foreach ($ids as $id)
		{
		$ud = get_userdata($id);
		
		// Se c'è già il numero di tessera usciamo
		if (get_user_meta($id, 'card_number', true) != "")
			{
			die("Numero di tessera già assegnato.");
			}

		// Assegniamo un numero di tessera
		$res = $wpdb->get_results("SELECT meta_value + 1 num FROM wp_usermeta WHERE meta_key LIKE 'card_number' ORDER BY meta_value DESC LIMIT 0, 1");
		update_user_meta($id, 'card_number', sprintf("%05d", $res[0]->num));
		
		$email = $ud->user_email;
		$nome = get_user_meta($id, "first_name", true);
		$cognome = get_user_meta($id, "last_name", true);
		$username = $ud->user_login;
		$token = get_user_meta($id, "payment_confirmation", true);
		$uid = $ud->ID;
		$confAddress = "http://www.airicerca.org/iscrizione/scegli-pwd/?uid=$uid&token=$token";
		// Mail di conferma all'utente
		$to = $email;
		$subject = "Iscrizione ad AIRIcerca completa";
		$body = "<div style='text-align:center'><img src='http://www.airicerca.org/wp-content/uploads/2014/03/airi-logo-256x62.png' /></div><br />
			Congratulazioni $nome $cognome, abbiamo ricevuto il tuo pagamento. La tua iscrizione come socio di AIRIcerca &egrave; completa!<br />
			<hr />
			In allegato a questa email trovi la tua tessera di socio di AIRIcerca, pronta per essere stampata in formato biglietto da visita (85mm x 55mm).<br />

			Ti abbiamo inoltre aggiunto alla mailing list dei soci, che viene usata esclusivamente per comunicazioni riguardanti l'Associazione.<br /> 
			
			Il tuo nome utente per accedere alla sezione soci di AIRIcerca è: <b>$username</b>.<br />
			Adesso non ti resta che scegliere una password visitando il seguente indirizzo:<br />
			<a href='$confAddress'>$confAddress</a><br />
			<br />
			Il team di AIRIcerca.
			<hr />
			Vuoi aiutare AIRIcerca? Puoi <a href='http://www.airicerca.org/collabora-con-noi/' target='_blank'>collaborare</a> con noi 
			o <a href='http://www.airicerca.org/dona-ora/' target='_blank'>contribuire</a> con una donazione!";

		$headers[] = "From: Iscrizioni AIRIcerca <webmaster@airicerca.org>";
		$headers[] = "Reply-To: webmaster@airicerca.org";
		$headers[] = "Content-Type: text/html";
		$headers[] = "charset=UTF-8";
		$headers[] = "MIME-Version: 1.0";
		$headers[] = "X-Mailer: PHP/".phpversion();
		
		$numero_tessera = get_user_meta($id, "card_number", true);
		//$attachment = WP_PLUGIN_DIR."/gestione-soci/tmp/$numero_tessera.pdf";
		$attachment = WP_PLUGIN_DIR."/gestione-soci/tmp/$numero_tessera.jpg";
		$response = wp_remote_get(plugin_dir_url( __FILE__ )."tessera.php?id=$id&out=1");
		/*echo $body."<br />";
		echo $attachment."<br />";
		print_r($response)."<br />";*/

		wp_mail($to, $subject, $body, $headers, $attachment);
		//unlink(WP_PLUGIN_DIR."/gestione-soci/tmp/$numero_tessera.pdf");
		//unlink(WP_PLUGIN_DIR."/gestione-soci/tmp/$numero_tessera.jpg");
//		echo "email inviata a $to<br />";

		// Aggiungiamo l'utente alla lista di MailChimp
		// Da: http://stackoverflow.com/q/30481979/176923
		$PLUGIN_BASE = $_SERVER['DOCUMENT_ROOT']."/wp-content/plugins/AIRIsoci/";
		
		$fname = $PLUGIN_BASE."/mchimp.txt";
    
		$keyfile = fopen($fname, "r");
		$apikey = trim(fgets($keyfile));
		$listID = trim(fgets($keyfile));
		fclose($keyfile);
		
		$auth = base64_encode( 'user:'.$apikey );

		$data = array(
			'apikey'	=> $apikey,
			'email_address'	=> $to,
			'status'	=> 'subscribed',
			'merge_fields'	=> array(
					'FNAME' => $nome,
					'LNAME' => $cognome)
			);
		$json_data = json_encode($data);
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, 'https://us11.api.mailchimp.com/3.0/lists/'.$listID.'/members/');
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json',
							'Authorization: Basic '.$auth));
		curl_setopt($ch, CURLOPT_USERAGENT, 'PHP-MCAPI/2.0');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
		$result = curl_exec($ch);
		}

	wp_die(); // this is required to terminate immediately and return a proper response
	}

function approva_prova_callback()
	{
	global $wpdb;
	
	$ids = $_POST['id'];
	//var_dump($ids);

	foreach ($ids as $id)
		{
		//echo "Updating $id";
		update_user_meta($id, "finalizzato", 1);
		}
	
	wp_die(); // this is required to terminate immediately and return a proper response
	}
	
function accetta_candidatura_callback()
	{
	global $wpdb;
	
	$ids = $_POST['id'];

	$email = array();
	
	foreach	($ids as $id)
		{
		$dati = get_user_meta($id, "dati_personali");
		$dati = $dati[0];

		// Controlliamo di essere su un non-ricercatore
		if ($dati["tipo_utente"] == 2)
			{
			// Aggiungiamo una entry nella tabella del plugin delle memberships
			$q = $wpdb->prepare("UPDATE wp_pmpro_memberships_users SET enddate = '0000-00-00 00:00:00', status='active' WHERE user_id = %d", $id);
			$res = $wpdb->query($q);
			
			// Confermiamo la candidatura
			update_user_meta($id, "finalizzato", 1);

			$ud = get_userdata($id);
			
			// Se c'è già il numero di tessera usciamo
			if (get_user_meta($id, 'card_number', true) != "")
				{
				die("Numero di tessera già assegnato.");
				}

			if (get_user_meta($id, 'card_number', true) == "")
				{
				$res = $wpdb->get_results("SELECT meta_value + 1 num FROM wp_usermeta WHERE meta_key LIKE 'card_number' ORDER BY meta_value DESC LIMIT 0, 1");
				update_user_meta($id, 'card_number', sprintf("%05d", $res[0]->num));
				}
				
			$email = $ud->user_email;
			$nome = get_user_meta($id, "first_name", true);
			$cognome = get_user_meta($id, "last_name", true);
			$username = $ud->user_login;
			$token = get_user_meta($id, "payment_confirmation", true);
			$uid = $ud->ID;
			$confAddress = "http://www.airicerca.org/iscrizione/scegli-pwd/?uid=$uid&token=$token";
			// Mail di conferma all'utente
			$to = $email;
			$subject = "Finalizzazione iscrizione ad AIRIcerca!";
			$body = "<div style='text-align:center'><img src='http://www.airicerca.org/wp-content/uploads/2014/03/airi-logo-256x62.png' /></div><br />
				Congratulazioni $nome $cognome, la tua candidatura come socio di AIRIcerca è stata accettata!<br />
				<hr />
				In allegato a questa email trovi la tua tessera di socio di AIRIcerca, pronta per essere stampata in formato biglietto da visita (85mm x 55mm).<br />

				Ti abbiamo inoltre aggiunto alla mailing list dei soci, che viene usata esclusivamente per comunicazioni riguardanti l'Associazione.<br /> 

				Il tuo nome utente per accedere alla sezione soci di AIRIcerca è: <b>$username</b>.<br />
				Puoi scegliere una password visitando il seguente indirizzo:<br />
				<a href='$confAddress'>$confAddress</a><br />
				
				Adesso non ti resta che versare la quota di iscrizione di minimo 5&euro; tramite Paypal o bonifico bancario.<br />
				<hr /><br />
				<strong>Per pagare tramite Bonifico Bancario</strong><br />
				Conto Corrente numero 1-075936-3<br />
				IBAN IT37H0537274300000010759363 (BIC: POCAIT3c)<br />
				Banca Popolare del Cassinate<br /><div style='line-height:3em'>&nbsp;<br />
				<strong>Indicare chiaramente il nome del socio nella causale</strong></div>
				<hr /><br />
				<strong>Per pagare tramite PayPal</strong><br />
				<form action='https://www.paypal.com/cgi-bin/webscr' method='post' target='_blank'>
				<input type='hidden' name='cmd' value='_s-xclick'>
				<input type='hidden' name='hosted_button_id' value='97TJ8YGQNYZUY'>
				<div style='text-align:center'>Premi il bottone \"Paga adesso\" e nella prossima pagina inserisci l'importo che desideri pagare (minimo 5&euro;)</div>
				<input type='image' src='https://www.paypalobjects.com/it_IT/IT/i/btn/btn_buynowCC_LG.gif' border='0' name='submit' alt='PayPal - 
				Il metodo rapido, affidabile e innovativo per pagare e farsi pagare.'>
				<img alt='' border='0' src='https://www.paypalobjects.com/it_IT/i/scr/pixel.gif' width='1' height='1'><br />
				Il bottone per pagare con Paypal non funziona? Prova invece a visitare <a href='http://www.airicerca.org/iscrizione/modalita-di-pagamento/' target='_blank'>questa pagina</a>!
				</form>
				<br /><hr/><br />
				Ricordiamo che la quota di iscrizione &egrave; dovuta entro 30 GIORNI dalla data odierna.<br/><hr />

				Il team di AIRIcerca.
				<hr />
				Vuoi aiutare AIRIcerca? Puoi <a href='http://www.airicerca.org/collabora-con-noi/' target='_blank'>collaborare</a> con noi 
				o <a href='http://www.airicerca.org/dona-ora/' target='_blank'>contribuire</a> con una donazione!";

			$headers[] = "From: Iscrizioni AIRIcerca <webmaster@airicerca.org>";
			$headers[] = "Reply-To: webmaster@airicerca.org";
			$headers[] = "Content-Type: text/html";
			$headers[] = "charset=UTF-8";
			$headers[] = "MIME-Version: 1.0";
			$headers[] = "X-Mailer: PHP/".phpversion();
		
			$numero_tessera = get_user_meta($id, "card_number", true);
			//$attachment = WP_PLUGIN_DIR."/gestione-soci/tmp/$numero_tessera.pdf";
			$attachment = WP_PLUGIN_DIR."/gestione-soci/tmp/$numero_tessera.jpg";
			$response = wp_remote_get(plugin_dir_url( __FILE__ )."tessera.php?id=$id&out=1");

			wp_mail($to, $subject, $body, $headers, $attachment);
			//unlink(WP_PLUGIN_DIR."/gestione-soci/tmp/$numero_tessera.pdf");
			unlink(WP_PLUGIN_DIR."/gestione-soci/tmp/$numero_tessera.jpg");

			// Aggiungiamo l'utente alla lista di MailChimp
			// Da: http://stackoverflow.com/q/30481979/176923
			$PLUGIN_BASE = $_SERVER['DOCUMENT_ROOT']."/wp-content/plugins/AIRIsoci/";
			
			$fname = $PLUGIN_BASE."/mchimp.txt";
		
			$keyfile = fopen($fname, "r");
			$apikey = trim(fgets($keyfile));
			$listID = trim(fgets($keyfile));
			fclose($keyfile);

			$auth = base64_encode('user:'.$apikey);

			$data = array(
				'apikey'	=> $apikey,
				'email_address'	=> $to,
				'status'	=> 'subscribed',
				'merge_fields'	=> array(
						'FNAME' => $nome,
						'LNAME' => $cognome)
				);
			$json_data = json_encode($data);
		
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, 'https://us11.api.mailchimp.com/3.0/lists/'.$listID.'/members/');
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json',
								'Authorization: Basic '.$auth));
			curl_setopt($ch, CURLOPT_USERAGENT, 'PHP-MCAPI/2.0');
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_TIMEOUT, 10);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
			$result = curl_exec($ch);
			}
		}
		
	wp_die();
	}
	
function rifiuta_candidatura_callback()
	{
	global $wpdb;
	
	$ids = $_POST['id'];
	
	foreach ($ids as $id)
		{
		$dati = get_user_meta($id, "dati_personali");
		$dati = $dati[0];
		
		// Controlliamo di essere su un non-ricercatore
		if ($dati["tipo_utente"] == 2)
			{
			$wpdb->query($wpdb->prepare("DELETE FROM wp_usermeta WHERE user_id = %d", $id));
			$wpdb->query($wpdb->prepare("DELETE FROM wp_pmpro_memberships_users WHERE user_id = %d", $id));
			}
		}
	
	wp_die();
	}
	
function conferma_pagamento_nonric_callback()
	{
	global $wpdb;
	
	$ids = $_POST['id'];
	$nextyear = max(2017, date("Y")+1);
	$endsub = date("Y-m-d 00:00:00", mktime(0, 0, 0, 1, 1, $nextyear));
	
	foreach ($ids as $id)
		{
		$dati = get_user_meta($id, "dati_personali");
		$dati = $dati[0];
		
		if ($dati["tipo_utente"] == 2)
			{
			$q = $wpdb->prepare("UPDATE wp_pmpro_memberships_users SET enddate = '%s' WHERE user_id = %d", $endsub, $id);
			$wpdb->query($q);
			}
		}
	
	wp_die();
	}
	
function stats_soci_callback()
	{
	global $wpdb;
	
	// I membri di AIRIcerca avranno una entry wp_capabilities nella tabella wp_usermeta
	// (Gli utenti AIRInforma hanno wp12_capabilities, AIRIsocial wp13_capabilities).
	$res = $wpdb->get_results("SELECT DISTINCT(pm.user_id) id FROM `wp_pmpro_memberships_users` pm 
		LEFT JOIN wp_usermeta um ON pm.user_id = um.user_id 
		WHERE pm.status = 'active' AND um.meta_key = 'wp_capabilities'");
	
	$ids = [];
	
	foreach ($res as $id)
		{
		$ids[] = $id->id;
		}

	$ids = implode(",", $ids);
	
	$utenti = $wpdb->get_results("SELECT MAX(membership.membership_id) lvl, COUNT(membership.membership_id) num 
			FROM
			wp_users AS t_users 
			LEFT JOIN 
			wp_pmpro_memberships_users membership ON 
			membership.user_id = t_users.ID 
			WHERE t_users.ID IN ($ids) 
			GROUP BY membership.membership_id");

	$txt .= "Numero totale di soci: ".((int)$utenti[0]->num + (int)$utenti[1]->num)."<br />";
	$txt .= "Amici: ".$utenti[2]->num."<br />";
	
	$dati = array();

	$ids = explode(",", $ids);

	$ambiti = array("Scienze Mediche/Biologiche", "Scienze Chimiche/Fisiche/Geologiche", "Scienze Umane", "Scienze Giuridiche/Economiche",  "Ingegneria", "Architettura/Design", "Matematica");
	$ambitiCountsRic = array(0, 0, 0, 0, 0, 0, 0);
	$ambitiCountsPhD = array(0, 0, 0, 0, 0, 0, 0);

	foreach ($ids as $id)
		{
		$dati[] = get_user_meta($id, "dati_personali", true);
		}
		
//	print_r($dati);
	for ($i=0; $i<count($dati); $i++)
		{
		if (isset($dati[$i]["professione"]) && $dati[$i]["professione"] == "Ricercatore")
			{
			$ambitiCountsRic[(int)$dati[$i]["ambito"]]++;
			}
		else if (isset($dati[$i]["professione"]) && $dati[$i]["professione"] == "Dottorando")
			{
			$ambitiCountsPhD[(int)$dati[$i]["ambito"]]++;
			}
		}
		
	$numRic = 0;
	$numPhD = 0;
	
	for ($i = 0; $i < count($ambiti); $i++)
		{
		$numRic += $ambitiCountsRic[$i];
		$numPhD += $ambitiCountsPhD[$i];
		}
		
		
	$ids = implode($ids, ",");
	
	$newregs = $wpdb->get_results("SELECT DATE(user_registered) data, COUNT(*) num FROM wp_users WHERE ID IN($ids) AND user_registered > '2015-08-29' GROUP BY data");
	
	$date = array();
	$num = array();
	
	foreach($newregs as $n)
		{
		$date[] = $n->data;
		$num[] = $n->num;
		}

	$countries = $wpdb->get_results("SELECT meta_value, COUNT(*) counts FROM wp_usermeta WHERE user_id IN($ids) AND meta_key LIKE 'rev_geoc_country' GROUP BY meta_value");

	$countryName = array();
	$countryCounts = array();
	
	foreach ($countries as $c)
		{
		$countryName[] = $c->meta_value;
		$countryCounts[] = $c->counts;
		}
	
	echo json_encode(array("numRic" => $numRic,
				"numPhD" => $numPhD,
				"countsRic" => $ambitiCountsRic,
				"countsPhD" => $ambitiCountsPhD,
				"newRegDate" => $date,
				"newRegNum" => $num,
				"countryName" => $countryName,
				"countryCounts" => $countryCounts));

	wp_die();
	}

/******************* SHORTCODES *******************/

// [ReCAPTCHA]

function reCAPTCHA()
	{
 	return '<div class="g-recaptcha" style="margin:auto" data-sitekey="6LfXiAMTAAAAAPjD7lpymcqLACCehoJguTiWeDzu"></div>';
 	}

add_shortcode('reCAPTCHA', 'reCAPTCHA');

//[privacyblurb]
function print_privacy_blurb()
	{
	return '<div class="font-small"><strong>INFORMAZIONI SULLA PRIVACY: </strong>Tutti i dati personali forniti saranno trattati nel rispetto del D.Lgs. 30 giugno 2003, n. 196 '.
		'recante il Codice in materia di protezione dei dati personali. I dati personali verranno trattati elettronicamente e saranno conservati all\'interno del database '.
		'digitale a tal uopo predisposto. Tutti i dati personali saranno trattati rispettando le misure minime di sicurezza prescritte dalla Legge, in modo da ridurne al '.
		'minimo i rischi di distruzione o perdita, di accesso non autorizzato o di trattamento non conforme alle finalità della raccolta. In relazione al trattamento dei '.
		'dati personali, il socio iscritto può esercitare i diritti riconosciutigli dall\'art. 7 del D.Lgs. 196/2003, e dunque, il diritto di accedere ai dati, il diritto '.
		'di ottenere rettifica e/o aggiornamento e/o integrazione dei dati, il diritto di ottenere cancellazione/trasformazione".</div>';
	}
add_shortcode('privacy-blurb', 'print_privacy_blurb');

// Chiama una pagina remota. Usata per la verifica del CAPTCHA
function getCurlData($url)
	{
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curl, CURLOPT_TIMEOUT, 10);
	curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.2.16) Gecko/20110319 Firefox/3.6.16");
	$curlData = curl_exec($curl);
	curl_close($curl);
	return $curlData;
	}
	

function create_username($nome, $cognome)
	{
	$username = strtolower(remove_accents($nome)).strtolower(remove_accents($cognome));

	$counter=2;
	while (username_exists($username))
		{
		$username = strtolower(remove_accents($nome)).strtolower(remove_accents($cognome)).$counter;
		$counter++;
		}

	$username = str_replace(' ', '', $username);
	
	return ($username);
	}
	
// Il form di registrazione per soci (t=1), soci non ricercatori (t=2) o amici (t=0)
//[registrationform]
function print_registration_form($atts)
	{
	$txt = "";
	$tipo = (int)$_GET["t"];
	$_POST = stripslashes_deep( $_POST );

	$nome = isset($_POST["nome"]) ? $_POST["nome"] : "";
	$cognome = isset($_POST["cognome"]) ? $_POST["cognome"] : "";
	$email = isset($_POST["email"]) ? $_POST["email"] : "";
	$codice_fiscale = isset($_POST["codice_fiscale"]) ? $_POST['codice_fiscale'] : "";
	$affiliazione = isset($_POST['affiliazione']) ? $_POST['affiliazione'] : "";
	$ambito = isset($_POST['ambito']) ? (int)$_POST['ambito'] : "";
	$prova = isset($_POST['prova']) ? $_POST['prova'] : "";
	$professione = isset($_POST['professione']) ? $_POST['professione'] : "";
	$candidatura = isset($_POST['candidatura']) ? substr($_POST['candidatura'], 0, 1000) : "";
	$cv = isset($_POST['cv']) ? substr($_POST['cv'], 0, 1000) : "";
	
	if ($prova != "")
		{
		// Se stiamo modificando i dati e abbiamo già caricato un file (che è stato rinominato come <username>.<ext>)
		// lo cancelliamo, per evitare accumulo di files...
		$username = create_username($nome, $cognome);
		
		$ext = pathinfo($prova, PATHINFO_EXTENSION);
		@unlink("wp-content/prove-affiliazione/".$username.".".$ext);
		}
		
	if ($tipo == 1 || $tipo == 2)
		{
		$nextyear = max(2017, date("Y")+1);
		$endsub = date("d/m/Y", mktime(0, 0, 0, 1, 1, $nextyear));

		$txt .= '<div style="text-align: center;">Grazie per aver scelto di diventare socio di AIRIcerca! Dopo aver inserito i tuoi dati in questa pagina 
		ti daremo le istruzioni per il pagamento della quota associativa annuale di 5&euro;<br />L\'iscrizione sar&agrave; valida fino al <strong>'.$endsub.
		'</strong></div><div style="line-height=2em">&nbsp;</div>
		<form id="form-registrazione" action="../conferma/" method="POST" enctype="multipart/form-data">
		<div class="cols-wrapper cols-2" style="width: 750px; margin: auto;">
		<div class="col" style="width: 200px; text-align: right;">Nome</div>
		<div class="col nomargin" style="width: 500px; text-align: left;"><input id="nome" name="nome" autofocus="autofocus" required="required" size="50" type="text" value="'.$nome.'" /></div>
		<div class="col" style="width: 200px; text-align: right;">Cognome</div>
		<div class="col nomargin" style="width: 500px; text-align: left;"><input id="cognome" name="cognome" required="required" size="50" type="text" value="'.$cognome.'" /></div>
		<div class="col" style="width: 200px; text-align: right;">Codice fiscale</div>
		<div class="col nomargin" style="width: 500px; text-align: left;"><input maxlength="16" id="codice_fiscale" name="codice_fiscale" required="required" 
		size="50" type="text" value="'.$codice_fiscale.'" />
		<div class="error-box" style="display:none;" id="invalidCF"><span class="box-icon"></span>Codice fiscale non valido</div></div>
		<div class="col" style="width: 200px; text-align: right;">Indirizzo e-mail</div>
		<div class="col nomargin" style="width: 500px; text-align: left;"><input id="email" name="email" required="required" size="50" type="text" value="'.$email.'" />
		<div class="error-box" style="display:none;" id="invalidEmail"><span class="box-icon"></span>Indirizzo email non valido</div> </div>';
		
		if ($tipo == 1)
			{
			$professioni = array("Ricercatore", "Studente di dottorato", "Studente all'ultimo anno di università");
			$values = array("Ricercatore", "Dottorando", "Studente");
			
			$txt .= '<div class="col" style="width: 200px; text-align: right;" required="required">Professione</div>
				<div class="col nomargin" style="width: 500px; text-align: left;">
				<select id="professione" name="professione" id="professione">';
			for ($p=0; $p<count($professioni); $p++)
					{
					$sel = ($values[$p] == $professione) ? "selected = 'selected'" : "";
					$txt .= "<option value='".$values[$p]."' $sel>".$professioni[$p]."</option>";
					}
			$txt .= "</select></div>";

			$txt .= '<div class="col" id="affiliazione_txt" style="width: 200px; text-align: right;" required="required">Affiliazione</div>
			<div class="col nomargin" style="width: 500px; text-align: left;"><input id="affiliazione" name="affiliazione" size="50" type="text" required="required" value="'.
				$affiliazione.'"/></div>
			<div class="col" style="width: 200px; text-align: right;">Prova di affiliazione<br />(<strong>max 1Mb</strong>, formati accettati: PDF, JPG, GIF, BMP, PNG)</div>
			<div class="col nomargin" style="width: 500px; text-align: left;"><br />
			<input type="file" id="prova" name="prova" accept="image/jpeg,image/gif,image/png,application/pdf,image/bmp" required="required" />
			<div class="error-box" style="display:none;" id="invalidFile"><span class="box-icon"></span>Massima dimensione consentita: 1Mb</div>
			<div class="font-small">Forniscici un documento che attesti la tua affiliazione presso un istituto di ricerca o iscrizione all\'Università, per gli studenti.';

			$txt .= do_shortcode('[showhide type="affiliazione" more_text="Di che documento avete bisogno?" less_text=""]Può essere lo screenshot del sito web 
			della pagina dell\'istituto dove lavori con indicazione del tuo nome, la prima pagina di un paper in cui siano indicati nomi ed affiliazione, 
			o qualsiasi altro documento che ci consenta di validare la tua affiliazione.L\'affiliazione non deve necessariamente essere attuale, è
			possibile fornire una prova di affiliazione ad un istituto di ricerca in cui si è operato in passato.
			Senza una prova di effettiva affiliazione, il processo di registrazione non potrà essere completato.</div>
			[/showhide]');

			$ambiti = array("Scienze Mediche/Biologiche", "Scienze Chimiche/Fisiche/Geologiche", "Scienze Umane", "Scienze Giuridiche/Economiche",  "Ingegneria", "Architettura/Design", "Matematica");
			
			$txt .= '&nbsp;</div>
			<div id="ambito_ricerca">
			<div class="col" style="width: 200px; text-align: right;">Ambito di ricerca</div>
			<div class="col nomargin" style="width: 500px; text-align: left;"><select name="ambito">';

			for ($i=0; $i<count($ambiti); $i++)
				{
				$sel = ($i==$ambito) ? "selected='selected'": "";
				$txt .= "<option value='$i' $sel>$ambiti[$i]</option>";
				}
			
			$txt .= '</select></div></div>';
			$txt .= '<div id="cvdiv">Inserisci un <strong>breve</strong> testo/CV per darci un\'idea delle tue competenze <span style = "color:gray;"> <em>[opzionale, max 1000 caratteri]</em>
				<br />';
			$txt .= "<textarea id='cv' name='cv' cols='100' rows='10' maxlength='1000'>".$cv."</textarea></div><br />";
			
			$txt .= 'Pensi di poter contribuire in qualche modo ad AIRIcerca? Facci sapere come puoi aiutarci! <span style = "color:gray;"> <em>[opzionale, max 1000 caratteri]</em>
				<br />';
			$txt .= "<textarea id='candidatura' name='candidatura' cols='100' rows='10' maxlength='1000'>".$candidatura."</textarea></div>";
			}
		else if ($tipo == 2)
			{
			$txt .= '<div class="col" style="width: 200px; text-align: right;">Professione</div>'.
				'<div class="col nomargin" style="width: 500px; text-align: left;">'.
				'<input id="professione" name="professione" required="required" size="50" type="text" value="'.$professione.'" /></div></div>';
			$txt .= '<div>In che modo contribuirai ad AIRIcerca? <i>[max 1000 caratteri]</i><br /><textarea id="candidatura" name="candidatura" cols="100" rows="10" 
				maxlength="1000" required="required">'.$candidatura.'</textarea></div>';
			}
			
			
		$txt .= '<div style="margin: auto; width: 305px;">'.do_shortcode('[reCAPTCHA]').'</div>
		<p style="text-align: center;"><input type="submit" value="Procedi con la registrazione" /></p>
		<input type="hidden" name="t" value="'.$tipo.'" />
		</form>';
		}
	else
		{
		$txt .= '<div style="text-align: center;">Grazie per aver scelto di diventare amico di AIRIcerca!</div><div style="line-height=2em">&nbsp;</div>
		<form id="form-registrazione" action="../conferma/" method="POST">
		<div class="cols-wrapper cols-2" style="width: 750px; margin: auto;">
		<div class="col" style="width: 200px; text-align: right;">Nome</div>
		<div class="col nomargin" style="width: 500px; text-align: left;"><input id="nome" name="nome" autofocus="autofocus" required="required" size="50" type="text" value="'.$nome.'" /></div>
		<div class="col" style="width: 200px; text-align: right;">Cognome</div>
		<div class="col nomargin" style="width: 500px; text-align: left;"><input id="cognome" name="cognome" required="required" size="50" type="text" value="'.$cognome.'" /></div>
		<div class="col" style="width: 200px; text-align: right;">Indirizzo e-mail</div>
		<div class="col nomargin" style="width: 500px; text-align: left;"><input id="email" name="email" required="required" size="50" type="text" value="'.$email.'" />
		<div class="error-box" style="display:none;" id="invalidEmail"><span class="box-icon"></span>Indirizzo email non valido</div> </div>
		</div><div style="margin: auto; width: 305px;">'.do_shortcode('[reCAPTCHA]').'</div>
		<p style="text-align: center;"><input type="submit" value="Procedi con la registrazione" /></p>
		<input type="hidden" name="t" value="0" />
		</form>';
		}
	
	return $txt;
	}
add_shortcode('registration-form', 'print_registration_form');

// La pagina di conferma dei dati
//[riassunto-dati]

// Crea un form per tornare alla pagina di inserimento dati (in seguito ad errore o per modifica dati)
function hiddenFields($t, $nome, $cognome, $codice_fiscale, $email, $affiliazione, $prova, $ambito, $professione, $cv, $candidatura, $buttonTxt)
	{
	$txt = "<form action='../dati-personali/?t=$t' method='POST'>
	<div style='text-align: center'><input type='submit' value='$buttonTxt' /></div>
	<input type=\"hidden\" name=\"t\" value=\"$t\" />
	<input type=\"hidden\" name=\"nome\" value=\"$nome\" />
	<input type=\"hidden\" name=\"cognome\" value=\"$cognome\" /><br />
	<input type=\"hidden\" name=\"codice_fiscale\" value=\"$codice_fiscale\" /><br />
	<input type=\"hidden\" name=\"email\" value=\"$email\" /><br />
	<input type=\"hidden\" name=\"affiliazione\" value=\"$affiliazione\" /><br />
	<input type=\"hidden\" name=\"prova\" value=\"$prova\" /><br />
	<input type=\"hidden\" name=\"ambito\" value=\"$ambito\" /><br />
	<input type=\"hidden\" name=\"professione\" value=\"$professione\" /><br />
	<input type=\"hidden\" name=\"cv\" value=\"$cv\" /><br />
	<input type=\"hidden\" name=\"candidatura\" value=\"$candidatura\" /><br />

	</form></div>";
	
	return $txt;
	}

function riassunto_dati($atts)
	{
	// Importante per gli apostrofi!!! (es. in cognomi e affiliazioni)
	$_POST = stripslashes_deep($_POST);

	$nome = isset($_POST['nome']) ? $_POST['nome'] : "";
	$cognome = isset($_POST['cognome']) ? $_POST['cognome'] : "";
	$email = isset($_POST['email']) ? $_POST['email'] : "";
	$codice_fiscale = isset($_POST['codice_fiscale']) ? $_POST['codice_fiscale'] : "";
	$affiliazione = isset($_POST['affiliazione']) ? $_POST['affiliazione'] : "";
	$prova = isset($_FILES['prova']) ? $_FILES['prova']['name'] : "";
	$ambito = isset($_POST['ambito']) ? $_POST['ambito'] : -1;
	$professione = isset($_POST['professione']) ? $_POST['professione'] : "";
	$t = isset($_POST['t']) ? (int)$_POST['t'] : -1;
	$cv = isset($_POST['cv']) ? substr($_POST['cv'], 0, 1000) : "";
	$candidatura = isset($_POST['candidatura']) ? substr($_POST['candidatura'], 0, 1000) : "";

	// Verifichiamo il noCaptcha reCaptcha
	if ($_SERVER["REQUEST_METHOD"] == "POST")
		{
		$recaptcha = $_POST['g-recaptcha-response'];
		if (!empty($recaptcha))
			{
			$google_url="https://www.google.com/recaptcha/api/siteverify";
			$secret = '6LfXiAMTAAAAAHBTv39MamRfreBhMF4uNidSBGr6';
			$ip = $_SERVER['REMOTE_ADDR'];
			$url = $google_url."?secret=".$secret."&response=".$recaptcha."&remoteip=".$ip;
			
			$res = getCurlData($url);
			$res = json_decode($res, true);

			if (!$res['success'])
				{
				return "<div class='error-box'><span class='box-icon'></span>Devi confermare di non essere un robot</div>".
					hiddenFields($t, $nome, $cognome, $codice_fiscale, $email, $affiliazione, $prova, $ambito, $professione, $cv, $candidatura, "Torna indietro");
				}
			}
		else
			{
			return "<div class='error-box'><span class='box-icon'></span>Devi confermare di non essere un robot</div>".
				hiddenFields($t, $nome, $cognome, $codice_fiscale, $email, $affiliazione, $prova, $ambito, $professione, $cv, $candidatura, "Torna indietro");
			}
		}
		
	$username = create_username($nome, $cognome);
		
	if ($t == 0)
		{
		if ($nome == "" || $cognome == "" || $email == "")
				{
				return "<div class='error-box'><span class='box-icon'></span>Tutti i campi devono essere completati</div>".
				hiddenFields($t, $nome, $cognome, $codice_fiscale, $email, $affiliazione, $prova, $ambito, $professione, $cv, $candidatura, "Torna indietro");
				}
				
		$txt = "<div style='line-height:2em'>Prima di terminare l'iscrizione ad AIRIcerca come amico, conferma che tutti i dati inseriti siano corretti <br />
			<form action='../add-user/?t=0' method='POST'>
			<strong>Nome:</strong> $nome<input type='hidden' name='nome' value='$nome' /><br />
			<strong>Cognome:</strong> $cognome<input type='hidden' name='cognome' value='$cognome' /><br />
			<strong>e-mail:</strong> $email<input type='hidden' name='email' value='$email' /><br />
			<div style='line-height:2em'>&nbsp;</div>
			<div style='text-align: center'><input type='submit' value='Procedi' /></div>
			</form>";
		}
	else if ($t == 1)
		{
		if ($nome == "" || $cognome == "" || $email == "" || $codice_fiscale == "" || $affiliazione == "" || $ambito == -1 || $prova == "" || $professione == "")
				{
				return "<div class='error-box'><span class='box-icon'></span>Tutti i campi devono essere completati</div>".
				hiddenFields($t, $nome, $cognome, $codice_fiscale, $email, $affiliazione, $prova, $ambito, $professione, $cv, $candidatura, "Torna indietro");
				}
				
		// Se stiamo gestendo l'iscrizione di un socio, controlliamo la prova di affiliazione
		try {
			// Controlliamo che non ci siano stati errori durante l'upload
			if (!isset($_FILES['prova']['error']) || is_array($_FILES['prova']['error']))
				{
				throw new RuntimeException('Errore  interno - parametri non validi.');
				}
	
			switch ($_FILES['prova']['error']) 
				{
				// Tutto OK
				case UPLOAD_ERR_OK:
					break;
				// Niente file
				case UPLOAD_ERR_NO_FILE:
					throw new RuntimeException("E' richiesta una prova di affiliazione");
				// Nota: questo semplicemente controlla che non abbiamo caricato un file > max_file_size in php.ini
				// quindi in teoria non dovremmo mai essere in questa situazione
				// Vedi anche: http://stackoverflow.com/questions/8300331/php-whats-the-point-of-upload-err-ini-size
				case UPLOAD_ERR_INI_SIZE:
				case UPLOAD_ERR_FORM_SIZE:
					throw new RuntimeException('Il file caricato è troppo grande.');
				default:
					throw new RuntimeException('Errore interno.');
				}
				
			if ($_FILES['prova']['size'] > 1000000)
				{
				throw new RuntimeException('La prova di affiliazione deve essere al massimo 1Mb.');
				}
	
			// Controlliamo il MIME Type
			$finfo = finfo_open(FILEINFO_MIME_TYPE);
			$allowedMIME = array(
				    'jpg' => 'image/jpeg',
				    'png' => 'image/png',
				    'gif' => 'image/gif',
				    'pdf' => 'application/pdf');
				    
			$MIME = finfo_file($finfo, $_FILES['prova']['tmp_name']);
				    
			if (false === $ext = array_search($MIME, $allowedMIME, true))
				{
				throw new RuntimeException('La prova di affiliazione deve essere un\'immagine o un file PDF.');
				}
				
			if (!move_uploaded_file($_FILES['prova']['tmp_name'], sprintf('wp-content/prove-affiliazione/%s.%s', $username, $ext))) 
				{
				throw new RuntimeException('Errore interno - impossibile copiare la prova di affiliazione, 
					<a href="mailto:webmaster@airicerca.org" target="_blank">contattare l\'amministratore del sito</a>.');
				}
			}
		catch (RuntimeException $e)
			{
			return "<div class='error-box'><span class='box-icon'></span>".$e->getMessage()."</div>".
				hiddenFields($t, $nome, $cognome, $codice_fiscale, $email, $affiliazione, $prova, $ambito, $professione, $cv, $candidatura, "Torna indietro");
			}

		$ambiti = array("Scienze Mediche/Biologiche", "Scienze Chimiche/Fisiche/Geologiche", "Scienze Umane", "Scienze Giuridiche/Economiche", "Ingegneria", "Architettura/Design", "Matematica");

		$txt = "<div style='line-height:2em'>Prima di terminare l'iscrizione ad AIRIcerca come socio, conferma che tutti i dati inseriti siano corretti <br />
			<form action='../add-user/?t=1' method='POST'>
			<strong>Nome:</strong> $nome<input type='hidden' name='nome' value=\"$nome\" /><br />
			<strong>Cognome:</strong> $cognome<input type='hidden' name='cognome' value=\"$cognome\" /><br />
			<strong>Codice fiscale:</strong> $codice_fiscale<input type='hidden' name='codice_fiscale' value='$codice_fiscale' /></br>
			<strong>e-mail:</strong> $email<input type='hidden' name='email' value='$email' /><br />
			<strong>Professione:</strong> $professione<input type='hidden' name='professione' value=\"$professione\"><br />
			<strong>Affiliazione:</strong> $affiliazione<input type='hidden' name='affiliazione' value=\"$affiliazione\" /><br />
			<strong>Prova di affiliazione:</strong> $prova<input type='hidden' name='prova' value=\"$prova\" /><br />";
		if ($professione != "Studente")
			{
			$txt .= "<strong>Ambito:</strong> $ambiti[$ambito]<input type='hidden' name='ambito' value='$ambito' /><br />
			<strong>Le tue competenze</strong>:<br />
			<textarea name='cv' readonly='readonly' cols='100' rows='10'>".$cv."</textarea>";
			}
			
		$txt .=	"<strong>Come puoi aiutarci</strong>:<br />
			<textarea name='candidatura' readonly='readonly' cols='100' rows='10'>".$candidatura."</textarea>
			<div style='line-height:2em'>&nbsp;</div>
			<div style='text-align: center'><input type='submit' value='Procedi' /></div>
			</form>";
		}
	else if ($t == 2)
		{
		if ($nome == "" || $cognome == "" || $email == "" || $codice_fiscale == "")
				{
				return "<div class='error-box'><span class='box-icon'></span>Tutti i campi devono essere completati</div>".
				hiddenFields($t, $nome, $cognome, $codice_fiscale, $email, $affiliazione, $prova, $ambito, $professione, $cv, $candidatura, "Torna indietro");
				}

		$txt = "<div style='line-height:2em'>Prima di terminare l'iscrizione ad AIRIcerca come socio, conferma che tutti i dati inseriti siano corretti <br />
			<form action='../add-user/?t=2' method='POST'>
			<strong>Nome:</strong> $nome<input type='hidden' name='nome' value=\"$nome\" /><br />
			<strong>Cognome:</strong> $cognome<input type='hidden' name='cognome' value=\"$cognome\" /><br />
			<strong>Codice fiscale:</strong> $codice_fiscale<input type='hidden' name='codice_fiscale' value='$codice_fiscale' /></br>
			<strong>e-mail:</strong> $email<input type='hidden' name='email' value='$email' /><br />
			<strong>Professione:</strong> $professione<input type='hidden' name='professione' value=\"$professione\"><br />
			<strong>Come puoi aiutarci</strong>:<br />
			<textarea name='candidatura' readonly='readonly' cols='100' rows='10'>$candidatura</textarea>
			<div style='line-height:2em'>&nbsp;</div>
			<div style='text-align: center'><input type='submit' value='Procedi' /></div>
			</form>";
		}
	else
		{
		return "<div class='error-box'><span class='box-icon'></span>Errore interno</div>".
			hiddenFields($t, $nome, $cognome, $codice_fiscale, $email, $affiliazione, $prova, $ambito, $professione, $cv, $candidatura, "Torna indietro");
		}
			
	$txt .= hiddenFields($t, $nome, $cognome, $codice_fiscale, $email, $affiliazione, $prova, $ambito, $professione, $cv, $candidatura, "Modifica i dati");
		
	return $txt;
	}

add_shortcode('riassunto-dati', 'riassunto_dati');

// Da http://stackoverflow.com/q/20444042/
function multiline_sanitize($str)
	{
	return (implode("\n", array_map( 'sanitize_text_field', explode("\n", $str))));
	}

//[adduser]
function adduser_shorttag($atts)
	{
	// La variabile globale che ci permette di accedere al DB
	global $wpdb;
	// Importante per gli apostrofi!!! (es. in cognomi e affiliazioni)
	$_POST = stripslashes_deep($_POST);

	$nome = isset($_POST['nome']) ? $_POST['nome'] : "";
	$cognome = isset($_POST['cognome']) ? $_POST['cognome'] : "";
	$email = isset($_POST['email']) ? $_POST['email'] : "";
	$codice_fiscale = isset($_POST['codice_fiscale']) ? $_POST['codice_fiscale'] : "";
	$affiliazione = isset($_POST['affiliazione']) ? $_POST['affiliazione'] : "";
	$prova = isset($_POST['prova']) ? $_POST['prova'] : "";
	$ambito = isset($_POST['ambito']) ? $_POST['ambito'] : -1;
	$professione = isset($_POST['professione']) ? $_POST['professione'] : "";
	$cv = isset($_POST['cv']) ? substr(multiline_sanitize($_POST['cv']), 0, 1000) : "";
	$candidatura = isset($_POST['candidatura']) ? substr(multiline_sanitize($_POST['candidatura']), 0, 1000) : "";
	$t = isset($_GET['t']) ? (int)$_GET['t'] : -1;
	
	// Controlliamo di avere tutti i dati
	if ($t==-1)
		die("Errore interno - 0");

	if ($t < 0 || $t > 2)
		die("Errore interno - 1");

	if ($t == 0)
		{
		if ($nome == "" || $cognome == "" || $email =="")
			die("Errore interno - 2");
		}
	else if ($t == 1)
		{
		if ($professione != "Studente")
			{
			if ($nome == "" || $cognome == "" || $email == "" || $codice_fiscale == "" || $affiliazione == "" || $prova == "" || $ambito == -1 || $professione == "")
				die("Errore interno - 3");
			}
		else
			{
			if ($nome == "" || $cognome == "" || $email == "" || $codice_fiscale == "" || $affiliazione == "" || $prova == "" || $professione == "")
				die("Errore interno - 3");
			}
		}
	else if ($t == 2)
		{
		//echo "Nome: $nome<br />Cognome: $cognome<br />e-mail: $email<br />Codice fiscale: $codice_fiscale<br />Professione: $professione<br />Candidatura: $candidatura";
		
		if ($nome == "" || $cognome == "" || $email == "" || $codice_fiscale == "" || $professione == "" || $candidatura == "")
			die("Errore interno - 4");
		}

	$pwd = wp_generate_password();
	
	$username = create_username($nome, $cognome);
	$txt = "";
	
	// wp_create_user si occupa del "sanitizing" degli input internamente. 
	$user_id = wp_create_user($username, $pwd, $email);
		
	if(is_wp_error($user_id))
		{
		$txt .= "<div class='error-box'><span class='box-icon'></span>C'&egrave; stato un errore durante l'inserimento dell'utente nel database di AIRIcerca<br />
			<div style='text-align:center'><strong>".$user_id->get_error_message()."</strong></div></div>";

		return $txt;
		}
	else
		{
		// Aggiungiamo un campo in meta con il codice 
		$confirmation_key = substr(sha1(uniqid(true)), 0, 15);
		update_user_meta($user_id, 'payment_confirmation', $confirmation_key);
		}
		
	if ($t == 0) // Amico
		{
		$res = true;
		
		$res &= update_user_meta($user_id, 'first_name', $nome);
		$res &= update_user_meta($user_id, 'last_name', $cognome);
		
		$res &= $wpdb->insert("wp_pmpro_memberships_users", 
			array("user_id" => $user_id, "membership_id" => 4, "code_id" => 0,
				"initial_payment" => 0.00, "billing_amount" => 0.00, "cycle_number" => 0, "cycle_period" => NULL,
				"startdate" => date("Y-m-d H:i:s"), "enddate" => "0000-00-00 00:00:00"));

		if (!$res)
			{
			$txt .= "<div class='error-box'><span class='box-icon'></span>C'&egrave; stato un errore durante l'inserimento dell'utente nel database di AIRIcerca<br />";
			$txt .= "L'iscrizione proceder&agrave; comunque, ma il profilo utente potrebbe non essere completo.</div>";
			}

		$txt .= "<div style='text-align:center'><img src='/wp-content/uploads/2014/03/airi-logo-256x62.png' /><br />
			Congratulazioni <strong>$nome $cognome</strong>, la tua iscrizione come amico di AIRIcerca &egrave; completa!<br />
			Sei stato iscritto alla nostra mailing list e ti terremo informato sulle nostre iniziative!<br />
			<hr />
			Vuoi aiutare AIRIcerca? Puoi <a href='/collabora-con-noi/' target='_blank'>collaborare</a> con noi o <a href='/dona-ora' target='_blank'>contribuire</a> con una donazione! 
			</div>";
			
			

		$to = $email;
		$subject = "Benvenuto su AIRIcerca!";
		$body = "<div style='text-align:center'><img src='http://www.airicerca.org/wp-content/uploads/2014/03/airi-logo-256x62.png' /><br />
			Congratulazioni <strong>$nome $cognome</strong>, la tua iscrizione come amico di AIRIcerca &egrave; completa!<br />
			Sei stato iscritto alla nostra mailing list e ti terremo informato sulle nostre iniziative!<br />
			<hr />
			Vuoi aiutare AIRIcerca? Puoi <a href='http://www.airicerca.org/collabora-con-noi/' target='_blank'>collaborare</a> con noi 
			o <a href='http://www.airicerca.org/dona-ora/' target='_blank'>contribuire</a> con una donazione! 
			</div>";
		
		$headers[] = "From: Iscrizioni AIRIcerca <webmaster@airicerca.org>";
		$headers[] = "Reply-To: webmaster@airicerca.org";
		$headers[] = "Content-Type: text/html";
		$headers[] = "charset=UTF-8";
		$headers[] = "MIME-Version: 1.0";
		$headers[] = "X-Mailer: PHP/".phpversion();
		
		wp_mail($to, $subject, $body, $headers);
		
		// Aggiungiamo l'utente alla lista di MailChimp
		// Da: http://stackoverflow.com/q/30481979/176923
		$PLUGIN_BASE = $_SERVER['DOCUMENT_ROOT']."/wp-content/plugins/AIRIsoci/";
		
		$fname = $PLUGIN_BASE."/mchimp.txt";
    
		$keyfile = fopen($fname, "r");
		$apikey = trim(fgets($keyfile));
		$listID = trim(fgets($keyfile));
		fclose($keyfile);

		$auth = base64_encode( 'user:'.$apikey );

		$data = array(
			'apikey'	=> $apikey,
			'email_address'	=> $to,
			'status'	=> 'subscribed',
			'merge_fields'	=> array(
					'FNAME' => $nome,
					'LNAME' => $cognome)
			);
		$json_data = json_encode($data);
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, 'https://us11.api.mailchimp.com/3.0/lists/'.$listID.'/members/');
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json',
							'Authorization: Basic '.$auth));
		curl_setopt($ch, CURLOPT_USERAGENT, 'PHP-MCAPI/2.0');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
		$result = curl_exec($ch);
		}
	// SOCIO
	else if ($t == 1 || $t == 2)
		{
		$res = update_user_meta($user_id, 'first_name', $nome);
		$res1 = update_user_meta($user_id, 'last_name', $cognome);

		if ($t == 1)
			{
			$extprova = pathinfo($prova, PATHINFO_EXTENSION);
		
			if ($extprova == "jpeg")
				$extprova = "jpg";

			$res2 = add_user_meta($user_id, 'dati_personali', array('codice_fiscale' => $codice_fiscale,
								'affiliazione'	=> stripslashes($affiliazione),
								'prova'		=> $username.".".strtolower($extprova),
								'ambito'	=> $ambito,
								'professione'	=> $professione,
								'cv'		=> $cv,
								'candidatura'	=> $candidatura,
								'tipo_utente'	=> $t));
			}
		else
			{
			$res2 = add_user_meta($user_id, 'dati_personali', array('codice_fiscale' => $codice_fiscale,
								'professione'	=> $professione,
								'candidatura'	=> $candidatura,
								'tipo_utente'	=> $t));
			}

		if (!$res || !$res1 || !$res2)
			{
			$txt .= "<div class='error-box'><span class='box-icon'></span>C'&egrave; stato un errore durante l'inserimento dell'utente nel database di AIRIcerca<br />";
			$txt .= "L'iscrizione proceder&agrave; comunque, ma il profilo utente potrebbe non essere completo.</div>";
			}

		// Aggiungiamo l'utente come socio, ma lasciamo la scadenza a 0000-00-00 fino a che non avrà pagato
		$res &= $wpdb->insert("wp_pmpro_memberships_users", 
			array("user_id" => $user_id, "membership_id" => 2, "code_id" => 0,
			"initial_payment" => 0.00, "billing_amount" => 0.00, "cycle_number" => 0, 
			"startdate" => date("Y-m-d H:i:s"), "enddate" => "0000-00-00 00:00:00"));

		if ($t == 1) // Prima email - socio ricercatore / PhD Student / Studente ultimo anno
			{
			$txt.= "<div style='text-align:center'><img src='/wp-content/uploads/2014/03/airi-logo-256x62.png' /><br />
				Congratulazioni <strong>$nome $cognome</strong>, la tua iscrizione come socio di AIRIcerca &egrave; quasi completa!<br />
				Non ti resta che versare la quota di iscrizione di minimo 5&euro; tramite Paypal o bonifico bancario.<br />
				Una volta che avremo confermato il tuo pagamento ti invieremo la tessera e le credenziali per accedere all'area soci.<br />
				<hr /><br />
				<strong>Per pagare tramite Bonifico Bancario</strong><br />
				Conto Corrente numero 1-075936-3<br />
				IBAN IT37H0537274300000010759363 (BIC: POCAIT3c)<br >
				Banca Popolare del Cassinate<br />
				<strong>Indicare chiaramente il nome del socio nella causale</strong><br />
				<div style='line-height:3em'>&nbsp;</div>
				<hr /><br />
				<strong>Per pagare tramite PayPal</strong><br />
				<form action='https://www.paypal.com/cgi-bin/webscr' method='post' target='_blank'>
				<input type='hidden' name='cmd' value='_s-xclick'>
				<input type='hidden' name='hosted_button_id' value='97TJ8YGQNYZUY'>
				<div style='text-align:center'>Premi il bottone \"Paga adesso\" e nella prossima pagina inserisci l'importo che desideri pagare (minimo 5&euro;)</div>
				<input type='image' src='https://www.paypalobjects.com/it_IT/IT/i/btn/btn_buynowCC_LG.gif' border='0' name='submit' alt='PayPal - 
					Il metodo rapido, affidabile e innovativo per pagare e farsi pagare.'>
				<img alt='' border='0' src='https://www.paypalobjects.com/it_IT/i/scr/pixel.gif' width='1' height='1'>
				</form>
				<br /><hr/><br />
				Ricordiamo che la quota di iscrizione &egrave; dovuta entro 30 GIORNI dalla data odierna.<br/>
				A breve riceverai un'email di conferma con tutte le istruzioni per il pagamento della quota di iscrizione.</div>";
		
			$to = $email;
			$subject = "Benvenuto su AIRIcerca!";
			$body = "<div style='text-align:center'><img src='http://www.airicerca.org/wp-content/uploads/2014/03/airi-logo-256x62.png' /><br />
				Congratulazioni <strong>$nome $cognome</strong>, la tua iscrizione come socio di AIRIcerca &egrave; quasi completa!<br />
				Non ti resta che versare la quota di iscrizione di minimo 5&euro; tramite Paypal o bonifico bancario.<br />
				Una volta che avremo confermato il tuo pagamento ti invieremo la tessera e le credenziali per accedere all'area soci.<br />
				<hr /><br />
				<strong>Per pagare tramite Bonifico Bancario</strong><br />
				Conto Corrente numero 1-075936-3<br />
				IBAN IT37H0537274300000010759363 (BIC: POCAIT3c)<br >
				Banca Popolare del Cassinate<br />
				<strong>Indicare chiaramente il nome del socio nella causale</strong><br />
				<div style='line-height:3em'>&nbsp;</div>
				<hr /><br />
				<strong>Per pagare tramite PayPal</strong><br />
				<form action='https://www.paypal.com/cgi-bin/webscr' method='post' target='_blank'>
				<input type='hidden' name='cmd' value='_s-xclick'>
				<input type='hidden' name='hosted_button_id' value='97TJ8YGQNYZUY'>
				<div style='text-align:center'>Premi il bottone \"Paga adesso\" e nella prossima pagina inserisci l'importo che desideri pagare (minimo 5&euro;)</div>
				<input type='image' src='https://www.paypalobjects.com/it_IT/IT/i/btn/btn_buynowCC_LG.gif' border='0' name='submit' alt='PayPal - 
				Il metodo rapido, affidabile e innovativo per pagare e farsi pagare.'>
				<img alt='' border='0' src='https://www.paypalobjects.com/it_IT/i/scr/pixel.gif' width='1' height='1'><br />
				Il bottone per pagare con Paypal non funziona? Prova invece a visitare <a href='http://www.airicerca.org/iscrizione/modalita-di-pagamento/' target='_blank'>questa pagina</a>!
				</form>
				<br /><hr/><br />
				Ricordiamo che la quota di iscrizione &egrave; dovuta entro 30 GIORNI dalla data odierna.<br/><hr />
				Vuoi aiutare AIRIcerca? Puoi <a href='http://www.airicerca.org/collabora-con-noi/' target='_blank'>collaborare</a> con noi 
				o <a href='http://www.airicerca.org/dona-ora/' target='_blank'>contribuire</a> con una donazione! 
				</div>";
			}
		else if ($t == 2) // Prima email - socio non ricercatore
			{
			$txt.= "<div style='text-align:center'><img src='/wp-content/uploads/2014/03/airi-logo-256x62.png' /><br />
				Grazie <strong>$nome $cognome</strong>, <br />
				abbiamo ricevuto la tua domanda per diventare socio di AIRIcerca!<br />
				Una volta che la tua candidatura sarà stata approvata dal Direttivo, riceverai un'email con le istruzioni per entrare nell'area riservata ai soci, 
				e per versare la quota di iscrizione (di minimo 5&euro;), che dovr&agrave; essere versata entro 30 GIORNI dalla data di approvazione.</div>";
		
			$to = $email;
			$subject = "Conferma richiesta di iscrizione AIRIcerca";
			$body = "<div style='text-align:center'><img src='http://www.airicerca.org/wp-content/uploads/2014/03/airi-logo-256x62.png' /><br />
				Grazie <strong>$nome $cognome</strong>, <br />
				abbiamo ricevuto la tua domanda per diventare socio di AIRIcerca!<br />
				Una volta che la tua candidatura sarà stata approvata dal Direttivo, riceverai un'email con le istruzioni per entrare nell'area riservata ai soci, 
				e per versare la quota di iscrizione (di minimo 5&euro;), che dovr&agrave; essere versata entro 30 GIORNI dalla data di approvazione.
				<br /><hr/><br />
				Vuoi aiutare AIRIcerca? Puoi <a href='http://www.airicerca.org/dona-ora/' target='_blank'>contribuire</a> con una donazione! 
				</div>";
			}

		$headers[] = "From: Iscrizioni AIRIcerca <webmaster@airicerca.org>";
		$headers[] = "Reply-To: webmaster@airicerca.org";
		$headers[] = "Content-Type: text/html";
		$headers[] = "charset=UTF-8";
		$headers[] = "MIME-Version: 1.0";
		$headers[] = "X-Mailer: PHP/".phpversion();
		
		wp_mail($to, $subject, $body, $headers);
		}

	return $txt;
	}

add_shortcode('adduser', 'adduser_shorttag');

function manage_subscriptions($atts)
	{
	// La variabile globale che ci permette di accedere al DB
	global $wpdb;

	// Cerchiamo gli ID dei membri
	// I membri di AIRIcerca avranno una entry wp_capabilities nella tabella wp_usermeta
	// (Gli utenti AIRInforma hanno wp12_capabilities, AIRIsocial wp13_capabilities).
	$res = $wpdb->get_results("SELECT DISTINCT(pm.user_id) FROM `wp_pmpro_memberships_users` pm 
		LEFT JOIN wp_usermeta um ON pm.user_id = um.user_id 
		WHERE pm.status = 'active' AND um.meta_key = 'wp_capabilities'");
	
	$ids = [];
	foreach ($res as $id)
		{
		$ids[] = $id->user_id;
		}
		
	$ids = implode(",", $ids);
	
	$utenti = $wpdb->get_results("SELECT t_users.ID id, t_users.user_login username, t_users.user_email email, 
			DATE_FORMAT(t_users.user_registered, '%d %b %y') data_registrazione, 
			DATE_FORMAT(t_users.user_registered, '%Y%m%d') data_registrazione_sort, 
			DATEDIFF(NOW(), t_users.user_registered) giorni_reg, membership.membership_id livello, membership.enddate fine_pagamento 
			FROM
			wp_users AS t_users 
			LEFT JOIN 
			wp_pmpro_memberships_users membership ON membership.user_id = t_users.ID WHERE t_users.ID IN ($ids) ORDER BY t_users.ID ASC");

	$nomi = $wpdb->get_results("SELECT meta_value nome FROM `wp_usermeta` WHERE meta_key LIKE 'first_name' AND user_id IN ($ids) ORDER BY user_id ASC");
	$cognomi = $wpdb->get_results("SELECT meta_value cognome FROM `wp_usermeta` WHERE meta_key LIKE 'last_name' AND user_id IN($ids) ORDER BY user_id ASC");
	$tessere = $wpdb->get_results("SELECT user_id, GROUP_CONCAT( (CASE WHEN meta_key = 'card_number' THEN meta_value ELSE '' END) SEPARATOR '') 'tessera'
		FROM wp_usermeta WHERE user_ID IN($ids) GROUP BY user_id ORDER BY user_id ASC");
	$txt = "";
	
	$nextyear = max(2017, date("Y")+1);
	$endsub = date("Y-m-d 00:00:00", mktime(0, 0, 0, 1, 1, $nextyear));
	$endsubShort = date("d/m/Y", mktime(0, 0, 0, 1, 1, $nextyear));

	$txt .= "<div style='text-align:center; margin:0.5em;'><strong>La conferma di pagamento o l'accettazione della candidatura attivano l'iscrizione fino al: ".$endsubShort."</strong></div><br />";

	$txt .="<table id='subscribers-table' style='color:black'>
		<thead>
			<tr>
			<th></th>
			<th>Nome</th>
			<th>e-mail</th>
			<th>Tipo</th>
			<th>Professione</th>
			<th>Prova</th>
			<th>Tessera</th>
			<th>Pagato</th>
			<th>Approvato</th>
			<th>CV</th>
			<th>Candidatura</th>
			<th>Affiliazione</th>
			<th>Ricercatore</th>
			<th>Registrato</th>
			</tr>
		</thead>
		<tbody>";

	$livelli = array("Fond", "Ordin", "Onor", "Amico");
	$ambiti = array("Bio/Med", "Chim/Fis/Geo", "Uman", "Giur/Econ", "Ingegn", "Arch/Des", "Matem");
	
	$i = 0;
	foreach ($utenti as $u)
		{
		$livello = ($u->livello == NULL) ? "N/A" : $livelli[$u->livello-1]; 
		
		if ($u->fine_pagamento == "")
			$pagato = "N/A";
		else if ($u->fine_pagamento == "0000-00-00 00:00:00") 
			$pagato = 0;
		else if ($u->fine_pagamento > date("Y-m-d H:i:s"))
			$pagato = 1;
		else
			$pagato = -1;

		$dati_personali = get_user_meta($u->id, "dati_personali");
		$dati_personali = $dati_personali[0];
		
		$conferma_prova = get_user_meta($u->id, "finalizzato", TRUE);
		$conferma_prova = ($conferma_prova == "") ? 0 : $conferma_prova;

		if ($dati_personali['tipo_utente'] == 1)
			$ricercatore = 1;
		else
			$ricercatore = 0;

		if ($dati_personali["prova"] == "")
			{
			$prova = "";
			}
		else
			{
			$prova = "<a href=\"/wp-content/prove-affiliazione/".$dati_personali["prova"]."\" target='_blank'><img src='".plugin_dir_url( __FILE__ )."images/document.png' width='35' /></a>";
			// $conferma_prova viene usato per i non ricercatori come conferma pagamento!
			if ($conferma_prova == 1 && $ricercatore == 1)
				$prova .= "<span class='font-bigger'>&#10003;</span>";
			}
			
		if (in_array($dati_personali['professione'], array('Ricercatore', 'Dottorando')))
			$professione = $dati_personali['professione']." - ".$ambiti[$dati_personali["ambito"]];
		else
			$professione = $dati_personali['professione'];

		if ($tessere[$i]->tessera != "")
			$tessera = '<a title="Mostra tessera" target="_blank" href="'.plugins_url('tessera.php?id='.$u->id, __FILE__ ).'">'.$tessere[$i]->tessera.'</a>';
//$tessera = $u->tessera;
		else
			$tessera = "Non generato";
			
		if ($u->giorni_reg < 30)
			{
			$giorni = $u->giorni_reg." giorni";
			}
		else if ($u->giorni_reg < 365)
			{
			$giorni = floor($u->giorni_reg/30)."+ mesi";
			}
		else
			{
			$giorni = floor($u->giorni_reg/365)."+ anni";
			}
			
		$registrato = "<span style='display:none'>".$u->data_registrazione_sort."</span>".$u->data_registrazione."<br />".$giorni;
		$txt .= "<tr id='row_$u->id'>
			<td><input type='checkbox' id='user_$u->id' /></td>
			<td>".$nomi[$i]->nome." ".$cognomi[$i]->cognome."</td>
			<td>$u->email</td>
			<td>$livello</td>
			<td>".$professione."</td>
			<td>$prova</td>
			<td>".$tessera."</td>
			<td>$pagato</td>
			<td>$conferma_prova</td>
			<td>".$dati_personali['cv']."</td>
			<td>".$dati_personali['candidatura']."</td>
			<td>".$dati_personali['affiliazione']."</td>
			<td>".$ricercatore."</td>
			<td>".$registrato."</td>
			</tr>";
		
		$i++;
		}
	$txt .= "</tbody>
		</table>";
		
	return $txt;
	}
	
add_shortcode('manage-subscriptions', 'manage_subscriptions');

function choose_password()
	{
	global $wpdb;
	
	if (isset($_POST['token']) && isset($_POST['newpassword']) && isset($_GET['uid'])) // Aggiorniamo la password
		{
		$id = (int)$_GET['uid'];
		$confirmation = $_POST['token'];
		
		$token = get_user_meta($id, "payment_confirmation", true);
	
		if ($confirmation != $token)
			{
			$txt = "<div class='error-box'><span class='box-icon'></span>Il link non è valido.</div></div>";
			return $txt;
			}
		
		// Cambiamo la password
		wp_set_password($_POST['newpassword'], $id);
		// Il link è one-time only, cancelliamo il token dopo la visita
		delete_user_meta($id, "payment_confirmation", $token);
		$txt = "La tua password è stata aggiornata! Visita <a href='http://www.airicerca.org/wp-login.php?redirect_to=/area-soci/'>questa pagina</a> per effettuare il login ed accedere all'area soci!";
	
		return $txt;
		}
	else if (!isset($_GET['token']) || !isset($_GET['uid']))
		{
		$txt = "<div class='error-box'><span class='box-icon'></span>Il link non è valido.</div></div>";
		return $txt;
		}

	// Form per scegliere la password
	$id = (int)$_GET['uid'];
	$confirmation = $_GET['token'];
	
	$token = get_user_meta($id, "payment_confirmation", true);

	if ($confirmation != $token)
		{
		$txt = "<div class='error-box'><span class='box-icon'></span>Il link non è valido.</div></div>";
		return $txt;
		}
	
	$nome = get_user_meta($id, "first_name", true);
	$cognome = get_user_meta($id, "last_name", true);
	$user_info = get_userdata($id);
	$username = $user_info->user_login;

	$txt = "<img class='img-frame aligncenter' src='http://www.airicerca.org/wp-content/uploads/2014/03/airi-logo-320x75.png' alt='Logo AIRIcerca' width='400' />
		<br />
		
		<div style='text-align:center' class='font-big'>Benvenuto $nome $cognome, e grazie per avere scelto di diventare socio di AIRIcerca!<br />
		Inserisci qui di seguito una password che potrai utilizzare per accedere ai contenuti riservati ai soci.<br />
		Potrai accedere all'area soci utilizzando il nome utente <b>$username</b></div>
		<div class='error-box' style='display:none;' id='err-pwd'><span class='box-icon'></span>Le due password non coincidono.</div>
		<form id='choose-pwd' style='text-align:center' action='../scegli-pwd?uid=$id' method='POST' >
		<label for='newpassword'>Scegli password: </label><input id='newpassword' name='newpassword' type='password' required='required' autofocus='autofocus' /><br />
		<label for='newpassword-repeat'>Ripeti password: </label><input id='newpassword-repeat' name='newpassword-repeat' type='password' required='required' /><br />
		<input type='hidden' name='token' value='$confirmation' />
		<button>Scegli password!</button>
		</form>";
	
	return $txt;
	}

add_shortcode('choose-password', 'choose_password');

function statistiche_soci()
	{
	global $wpdb;
	
	$txt = "<div id='loading'>Calcolo statistiche in corso...</div>";

	$txt .= "<div id='numRic' style = 'font-size: 1.3em;'><strong></strong></div>";
	$txt .= "<div id='perc_ric_div'></div><br />";
	
	$txt .= "<div id='numPhD' style = 'font-size: 1.3em;'><strong></strong></div><br />";
	$txt .= "<div id='perc_dott_div'></div><br />";
	
	$txt .= "<div id='numTotal' style = 'font-size: 1.3em;'><strong></strong></div><br />";
	$txt .= "<div id='perc_total_div'></div><br />";

	$txt .= "<div id='histoTitle' style = 'font-size: 1.3em;'></div>";
	$txt .= "<div id='histo_iscrizioni_div'></div><br />";

	$txt .= "<div id='MapTitle' style = 'font-size: 1.3em;'></div>";
	$txt .= "<div id='mappa_soci_div'></div><br />";

	return $txt;
	}

add_shortcode('statistiche-soci', 'statistiche_soci');

/*function addMetaField()
	{
	global $wpdb;
	
	// Cerchiamo gli ID dei membri
	$res = $wpdb->get_results("SELECT DISTINCT(pm.user_id) FROM `wp_pmpro_memberships_users` pm 
		LEFT JOIN wp_usermeta um ON pm.user_id = um.user_id 
		WHERE pm.status = 'active' AND um.meta_key = 'wp_capabilities'");

	foreach ($res as $user)
		{		
		$id = $user->user_id;
		
		$info = get_user_meta($id, "dati_personali");
		$info = $info[0];
		unset($info['tipo']);
		$info['tipo_utente'] = 1;
		//echo "<pre>";
		//print_r($info);
		//echo "</pre>";
		update_user_meta($id, "dati_personali", $info);
		}
	}

add_shortcode('add-meta-field', 'addMetaField');*/

/*function esempioTessera()
	{
	$id = (int)$_GET['id'];
	$txt = "<img src='".plugins_url('tessera.php?id=$id', __FILE__ )."' />";
	return $txt;
	}
	
add_shortcode('esempio-tessera', 'esempioTessera');*/

function scriviAffiliazioni()
	{
	global $wpdb;
	
	$res = $wpdb->get_results("SELECT DISTINCT(pm.user_id) FROM `wp_pmpro_memberships_users` pm 
		LEFT JOIN wp_usermeta um ON pm.user_id = um.user_id 
		WHERE pm.status = 'active' AND um.meta_key = 'wp_capabilities'");
	
	$ids = [];
	foreach ($res as $id)
		{
		$ids[] = $id->user_id;
		}
		
	$ids = implode(",", $ids);

	$res = $wpdb->get_results("SELECT m.user_ID, m.meta_value dati FROM wp_usermeta m 
				WHERE user_id IN ($ids) AND meta_key = 'dati_personali' ORDER BY user_id ASC");
	
	foreach($res as $u)
		{
		$nome = get_user_meta($u->user_ID, "first_name", true)."~".get_user_meta($u->user_ID, "last_name", true);
		echo $u->user_ID."~".$nome."~".unserialize($u->dati)["affiliazione"]."<br />";
		}
	}
	
add_shortcode('affiliazioni-soci', 'scriviAffiliazioni');


?>
