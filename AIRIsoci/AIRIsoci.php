<?php
/**
 * Plugin Name: AIRIsoci
 * Plugin URI: https://github.com/AIRIOpenLab/AIRIplugin
 * Description: Plugin per la gestione dei soci di AIRIcerca.
 * Version: 1.1.2
 * Author: Nicola Romanò
 * Author URI: https://github.com/nicolaromano
 * License: GPL3
 */
 
/* Copyright 2015 Nicola Romanò (romano.nicola@gmail.com)

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


const REVIEWER_BIT = 0;

// utilities per identificare volontari
function as_encode_volunteer($code, $bit) {
    return $code | (1 << $bit); 
}

function as_check_volunteer($code, $bit) {
    return (1 & ($code >> $bit)) === 1;
}

function as_build_volunteer_code($revisore) {
    $code = 0;
    if ($revisore) 
        $code = as_encode_volunteer($code, REVIEWER_BIT);
   
    return $code; 
}

// Loading condizionale di Javascript
function as_load_custom_scripts() 
	{
	$PLUGIN_BASE = $_SERVER['DOCUMENT_ROOT']."/wp-content/plugins/AIRIsoci/";
	$page_ID = get_the_ID();
	//echo $page_ID;
	if ($page_ID == 6822 || $page_ID == 13583) // Pagina iscrizione soci + amici, modifica dati utente
		{		    
		wp_enqueue_script('reCAPTCHA', 'https://www.google.com/recaptcha/api.js?hl=it', array(), '1', true);
		
		wp_enqueue_script('jquery-ui', "https://code.jquery.com/ui/1.11.4/jquery-ui.min.js", array("jquery"), '1', true);
		wp_enqueue_script('jeoquery', plugins_url( 'jeoquery.js', __FILE__ ), array("jquery"), '1', true);
		
		if (strpos(get_site_url(), 'localhost') !== true) {
		    wp_register_style('jqueryuicss', 'https://code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css?ver=1.11.4');
		    wp_enqueue_style('jqueryuicss');
		}
		
		wp_register_style('select2css', 'https://unpkg.com/select2@4.0.5/dist/css/select2.min.css');
		wp_enqueue_style('select2css');
		
		wp_enqueue_script('select2', 'https://unpkg.com/select2@4.0.5/dist/js/select2.min.js', array(), '1', true);
		wp_enqueue_script('cip2010', plugins_url('cips2010_data.js', __FILE__), array(), '1', true);
		wp_enqueue_script('select2-i18n', plugins_url('i18n/it.js', __FILE__), array(), '1', true);
		
		wp_enqueue_script('select-cip2010', plugins_url( 'select_cip2010.js', __FILE__ ), array('jquery', 'select2', 'cip2010', 'select2-i18n'), '1', true);
		
		wp_enqueue_script('checkData', plugins_url( 'registration.js', __FILE__ ), array("jquery", "jquery-ui", "jeoquery"), '1', true);
		
		
		
		}
	else if ($page_ID == 7336) // Gestione soci
		{
		wp_enqueue_style('DataTablesCSS', 'http://cdn.datatables.net/1.10.7/css/jquery.dataTables.css');
		wp_enqueue_script('DataTables', 'http://cdn.datatables.net/1.10.7/js/jquery.dataTables.js', array("jquery"), '1', true);
		wp_enqueue_script('initDataTable', plugins_url('gestione-soci.js', __FILE__ ), array("jquery"), '1', true);
		wp_localize_script('initDataTable', 'DataTablesLoadData', array('ajaxURL' => admin_url('admin-ajax.php')));
		}

/*	if ($page_ID == 3355 || $page_ID == 3480) // Pagina iscrizione soci + amici
		{
		wp_enqueue_script('reCAPTCHA', 'https://www.google.com/recaptcha/api.js?hl=it', array(), '1', true);
		wp_enqueue_script('checkData', plugins_url( 'registration.js', __FILE__ ), array("jquery"), '1', true);
		}
	else if ($page_ID == 4116)
		{
		wp_enqueue_script('checkData', plugins_url('setpwd.js', __FILE__ ), array("jquery"), '1', true);
		}
	else if ($page_ID == 6420) // Statistiche soci
		{
		wp_enqueue_script('GoogleAPI', 'https://www.google.com/jsapi', '1', array(), '1', true);
		wp_enqueue_script('initStats', plugins_url('statistiche-soci.js', __FILE__ ), array("jquery"), '1', true);
		wp_localize_script('initStats', 'DataTablesLoadData', array('ajaxURL' => admin_url('admin-ajax.php')));
		}*/
	}

if (is_admin())
	{
	// Rimuovi un utente
	add_action('wp_ajax_act_rem_user', 'as_rem_user_callback');
	add_action('wp_ajax_nopriv_act_rem_user', 'as_rem_user_callback');
	// Accetta candidatura
	add_action('wp_ajax_act_approve_user', 'as_accetta_candidatura_callback');
	add_action('wp_ajax_nopriv_act_approve_user', 'as_accetta_candidatura_callback');
	// Rinnova iscrizione
	add_action('wp_ajax_act_renew_user', 'as_rinnova_iscrizione_callback');
	add_action('wp_ajax_nopriv_act_renew_user', 'as_rinnova_iscrizione_callback');

	// Conferma pagamento ricercatore
/*	add_action('wp_ajax_act_conferma', 'conferma_callback');
	add_action('wp_ajax_nopriv_act_conferma', 'conferma_callback');
	// Conferma prova di affiliazione
	add_action('wp_ajax_act_approva', 'approva_prova_callback');
	add_action('wp_ajax_nopriv_act_approva', 'approva_prova_callback');
	// Rifiuta candidatura
	add_action('wp_ajax_act_rifiuta_cand', 'rifiuta_candidatura_callback');
	add_action('wp_ajax_nopriv_act_rifiuta_cand', 'rifiuta_candidatura_callback');
	// Conferma pagamento non ricercatore
	add_action('wp_ajax_act_conferma_nonric', 'conferma_pagamento_nonric_callback');
	add_action('wp_ajax_nopriv_act_conferma_nonric', 'conferma_pagamento_nonric_callback');
	// Restituisce le statistiche dei soci
	add_action('wp_ajax_act_stats', 'stats_soci_callback');
	add_action('wp_ajax_nopriv_act_stats', 'stats_soci_callback');*/
	}

	
add_action('wp_enqueue_scripts', 'as_load_custom_scripts');

// Helper function per aggiungere un tag img con il logo di AIRIcerca
function as_logo_AIRIcerca()
	{
	return "<img src='/wp-content/uploads/2014/03/airi-logo-256x62.png' />";
	}
	
// Questa funzione rimanda semplicemente all'opportuna funzione per i vari step dell'iscrizione
function as_dispatcher_iscrizione()
	{
	$txt = "";
	
	if (!isset($_GET['tp']) || !isset($_GET['ps']))
		{
		$txt .= "Errore interno.<br />";
		$txt .= "<a href = '/'>".as_logo_AIRIcerca(TRUE)."</a>";
		return $txt;
		}
		
	$tipo = (int)$_GET['tp'];
	$step = (int)$_GET['ps'];
	
	if ($step == 1)
		{
		$txt .= as_dati_personali($tipo);
		}
	else if ($step == 2)
		{
		$txt .= as_conferma_dati($tipo);
		}
	else if ($step == 3)
		{
		$txt .= as_add_user($tipo);
		}
	
	return $txt;
	}

function as_rem_user_callback()
	{
	global $wpdb;
	
	$id = (int)$_POST["id"];
	
	$wpdb->delete('wp_users', array('ID' => $id), array('%d'));
	$wpdb->delete('wp_usermeta', array('user_id' => $id), array('%d'));
	$wpdb->delete('wp_pmpro_memberships_users', array('user_id' => $id), array('%d'));
	wp_die(); // this is required to terminate immediately and return a proper response
	}

function as_rinnova_iscrizione_callback()
	{
	global $wpdb;
	
	$id = (int)$_POST['id'];

	$nextyear = date("Y") + 1;
	// Aggiungiamo una entry nella tabella del plugin delle memberships
	$q = $wpdb->prepare("UPDATE wp_pmpro_memberships_users SET enddate = '$nextyear-01-31 00:00:00', status='active' WHERE user_id = %d", $id);
	$res = $wpdb->query($q);
	
	$ud = get_userdata($id);

	$email = $ud->user_email;
	$nome = get_user_meta($id, "first_name", true);
	$cognome = get_user_meta($id, "last_name", true);
	// Mail di conferma all'utente
	$to = $email;
	$subject = "Rinnovo iscrizione ad AIRIcerca!";
	$body = "<div style='text-align:center'><img src='https://www.airicerca.org/wp-content/uploads/2014/03/airi-logo-256x62.png' /></div><br />
		Gentile $nome $cognome,<br />la tua iscrizione ad AIRIcerca è stata rinnovata!<br />
		La tua iscrizione è valida fino al <strong>31 gennaio $nextyear</strong><br />.
		Grazie per aver confermato ancora una volta il supporto alla nostra Associazione.
		
		Il team di AIRIcerca.
		<hr /><br />
		Vuoi aiutare AIRIcerca? Puoi <a href='https://www.airicerca.org/collabora-con-noi/' target='_blank'>collaborare</a> con noi 
		o <a href='https://www.airicerca.org/dona-ora/' target='_blank'>contribuire</a> con una donazione!";

	$headers[] = "From: Iscrizioni AIRIcerca <webmaster@airicerca.org>";
	$headers[] = "Reply-To: webmaster@airicerca.org";
	$headers[] = "Content-Type: text/html";
	$headers[] = "charset=UTF-8";
	$headers[] = "MIME-Version: 1.0";
	$headers[] = "X-Mailer: PHP/".phpversion();

	wp_mail($to, $subject, $body, $headers);

	wp_die(); // this is required to terminate immediately and return a proper response
	}

function as_accetta_candidatura_callback()
{
	global $wpdb;
	
	$PLUGIN_BASE = $_SERVER['DOCUMENT_ROOT']."/wp-content/plugins/AIRIsoci/";
	
	$id = (int)$_POST['id'];

	$email = array();
	
	$dati = get_user_meta($id, "dati_personali");
	$dati = $dati[0];
	$nextyear = date("Y") + 1;

	// Aggiungiamo una entry nella tabella del plugin delle memberships
	$q = $wpdb->prepare("UPDATE wp_pmpro_memberships_users SET enddate = '$nextyear-01-31 00:00:00', status='active' WHERE user_id = %d", $id);
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
	$confAddress = "https://www.airicerca.org/iscrizione/scegli-pwd/?uid=$uid&token=$token";
	// Mail di conferma all'utente
	$to = $email;
	$subject = "Finalizzazione iscrizione ad AIRIcerca!";
	$body = "<div style='text-align:center'><img src='https://www.airicerca.org/wp-content/uploads/2014/03/airi-logo-256x62.png' /></div><br />
		Congratulazioni $nome $cognome, la tua candidatura come socio di AIRIcerca è stata accettata!<br />
		<hr />
		In allegato a questa email trovi la tua tessera di socio di AIRIcerca, pronta per essere stampata in formato biglietto da visita (85mm x 55mm).<br />

		Ti abbiamo inoltre aggiunto alla mailing list dei soci, che viene usata esclusivamente per comunicazioni riguardanti l'Associazione.<br /> 

		Il tuo nome utente per accedere alla sezione soci di AIRIcerca è: <b>$username</b>.<br />
		Puoi scegliere una password visitando il seguente indirizzo:<br />
		<a href='$confAddress'>$confAddress</a><br />
		
		Il team di AIRIcerca.
		<hr />
		Vuoi aiutare AIRIcerca? Puoi <a href='https://www.airicerca.org/collabora-con-noi/' target='_blank'>collaborare</a> con noi 
		o <a href='https://www.airicerca.org/dona-ora/' target='_blank'>contribuire</a> con una donazione!";

	$headers[] = "From: Iscrizioni AIRIcerca <webmaster@airicerca.org>";
	$headers[] = "Reply-To: webmaster@airicerca.org";
	$headers[] = "Content-Type: text/html";
	$headers[] = "charset=UTF-8";
	$headers[] = "MIME-Version: 1.0";
	$headers[] = "X-Mailer: PHP/".phpversion();

	$numero_tessera = get_user_meta($id, "card_number", true);
	$attachment = WP_PLUGIN_DIR."/AIRIsoci/tmp/$numero_tessera.jpg";
	$response = wp_remote_get(plugin_dir_url( __FILE__ )."tessera.php?id=$id&out=1", array('timeout' => 20));

	wp_mail($to, $subject, $body, $headers, $attachment);
	unlink(WP_PLUGIN_DIR."/AIRISoci/tmp/$numero_tessera.jpg");

	// Aggiungiamo l'utente alla lista di MailChimp
	// Non fare niente se si tratta di un test
	if (strpos(get_site_url(), 'localhost') !== false) {
	    $body .= "<BR /><BR /><BR /> Mailchimp list=".$listID." key=".$apikey;
	} else { 
	    as_add_mailchimp(1, $nome, $cognome, $to);
	}
	 		
	wp_die();
	}
	
// Aggiungiamo l'utente alla lista di MailChimp
// Da: http://stackoverflow.com/q/30481979/176923
function as_add_mailchimp($tipo, $nome, $cognome, $email) {
    
    $PLUGIN_BASE = $_SERVER['DOCUMENT_ROOT']."/wp-content/plugins/AIRIsoci/";
    
    // Leggiamo i segreti da file
    $fname = '';
    
    if ($tipo == 0)
        $fname = $PLUGIN_BASE."/mchimp-amici.txt";
    else 
        $fname = $PLUGIN_BASE."/mchimp.txt";
    
    $keyfile = fopen($fname, "r");
    $apikey = trim(fgets($keyfile));
    $listID = trim(fgets($keyfile));
    fclose($keyfile);
    
    $auth = base64_encode('user:'.$apikey);
    
    $data = array(
        'apikey'	=> $apikey,
        'email_address'	=> $email,
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
	
function as_dati_personali($tipo)
	{
	// Importante per gli apostrofi!
	$_POST = stripslashes_deep( $_POST );

	$page_ID = get_the_ID();
	
	$nome = isset($_POST["nome"]) ? $_POST["nome"] : "";
	$cognome = isset($_POST["cognome"]) ? $_POST["cognome"] : "";
	$email = isset($_POST["email"]) ? $_POST["email"] : "";
	$codice_fiscale = isset($_POST["codice_fiscale"]) ? $_POST['codice_fiscale'] : "";
	$affiliazione = isset($_POST['affiliazione']) ? $_POST['affiliazione'] : "";
	$citta = isset($_POST['citta']) ? $_POST['citta'] : "";
	$cip2010 = isset($_POST['cip2010']) ? $_POST['cip2010'] : "";
	$cip2010_desc = isset($_POST['cip2010_desc']) ? $_POST['cip2010_desc'] : "";
	$prova_pagamento = isset($_POST['prova_pagamento']) ? $_POST['prova_pagamento'] : "";
	$professione = isset($_POST['professione']) ? $_POST['professione'] : "";
	$candidatura = isset($_POST['candidatura']) ? substr($_POST['candidatura'], 0, 1000) : "";
	$cv = isset($_POST['cv']) ? substr($_POST['cv'], 0, 1000) : "";
	$vcode = isset($_POST['vcode']) ? (int)$_POST['vcode'] : 0;
	$lat = isset($_POST['lat']) ? (float)$_POST['lat'] : 90;
	$lng = isset($_POST['lng']) ? (float)$_POST['lng'] : 0;

	$nextyear = date("Y") + 1;
	$endsub = date("d/m/Y", mktime(0, 0, 0, 1, 31, $nextyear));
	
	$txt = "";
	
	$txt .= '<div style="text-align: center;">Grazie per aver scelto di diventare socio di AIRIcerca!<br /><strong>Prima di inserire i tuoi dati in questa pagina 
	assicurati di aver pagato la quota associativa annuale di minimo 5&euro; e di avere una prova di pagamento da caricare.</strong> Segui questo link per <a href="modalita-di-pagamento/" title="Istruzioni di pagamento" target="_blank">le istruzioni di pagamento</a>.
	<br />La tua iscrizione sar&agrave; valida fino al <strong>'.$endsub.'</strong></div>
	
	<div style="line-height=2em">&nbsp;</div>
	<form id="form-registrazione" action="'.get_site_url().'/?page_id='.$page_ID.'&tp='.$tipo.'&ps=2" method="POST" enctype="multipart/form-data">
	<div class="cols-wrapper cols-2" style="width: 750px; margin: auto;">
	<div class="col" style="width: 200px; text-align: right;"><label for="nome">Nome</label></div>
	<div class="col nomargin" style="width: 500px; text-align: left;"><input id="nome" name="nome" autofocus="autofocus" required="required" placeholder="Il tuo nome" size="50" type="text" value="'.$nome.'" /></div>
	<div class="col" style="width: 200px; text-align: right;"><label for="cognome">Cognome</label></div>
	<div class="col nomargin" style="width: 500px; text-align: left;"><input id="cognome" name="cognome" required="required" size="50" type="text" placeholder="Il tuo cognome" value="'.$cognome.'" /></div>
	<div class="col" style="width: 200px; text-align: right;"><label for="codice_fiscale">Codice fiscale</label></div>
	<div class="col nomargin" style="width: 500px; text-align: left;"><input maxlength="16" id="codice_fiscale" placeholder="Il tuo codice fiscale" name="codice_fiscale" required="required" 
	size="50" type="text" value="'.$codice_fiscale.'" />
	<div class="error-box" style="display:none;" id="invalidCF"><span class="box-icon"></span>Codice fiscale non valido</div>
	</div>
	<div class="col" style="width: 200px; text-align: right;"><label for="email">Indirizzo e-mail</label></div>
	<div class="col nomargin" style="width: 500px; text-align: left;"><input id="email" name="email" required="required" size="50" type="email" placeholder="Il tuo indirizzo e-mail" value="'.$email.'" />
	</div>
	<div class="col" style="width: 200px; text-align: right;"><label for="confirmemail">Ripeti indirizzo e-mail</label></div>
	<div class="col nomargin" style="width: 500px; text-align: left;"><input id="confirmemail" name="confirmemail" required="required" size="50" type="email" placeholder="Il tuo indirizzo e-mail" value="'.$email.'" />
	<div class="error-box" style="display:none;" id="invalidEmail"><span class="box-icon"></span>Gli indirizzi email non corrispondono</div>
	</div>
	<div class="col" style="width: 200px; text-align: right;"><label for="citta">Città</label></div>
	<div class="col nomargin" style="width: 500px; text-align: left;"><input id="citta" name="citta" required="required" size="50" type="text" placeholder="La città in cui vivi: es. Roma" value="'.$citta.'" /><input type="hidden" name="lat" id="lat" /> <input type="hidden" name="lng" id="lng" /></div>';


	if ($tipo == 1)
		{
		$professioni = array("Ricercatore", "Studente di dottorato", "Studente all'ultimo anno di università");
		$values = array("Ricercatore", "Dottorando", "Studente");
		
		$txt .= '<div class="col" style="width: 200px; text-align: right;" required="required">Professione</div>
			<div class="col nomargin" style="width: 500px; text-align: left;">
			<select id="professione" name="professione">';
		for ($p=0; $p<count($professioni); $p++)
				{
				$sel = ($values[$p] == $professione) ? "selected = 'selected'" : "";
				$txt .= "<option value='".$values[$p]."' $sel>".$professioni[$p]."</option>";
				}
		$txt .= "</select></div>";

		
		$select_opt = '<option/>';
		
		if ($cip2010 != -1) 
		    $select_opt = '<option value="'.$cip2010.'" selected>'.$cip2010_desc.'</option>';

		$txt .= '<div class="col" style="width: 200px; text-align: right;">Ambito di ricerca</div>
        <div class="col nomargin" style="width: 500px; text-align: left;">
		<select id="cip_select" class="js-example-basic-single form-control" name="cip2010" onchange="cipSelectFunction()" style="width: 500px;">
		'.$select_opt.'</select><input type="hidden" id="cip2010_desc" name="cip2010_desc" value="'.$cip2010_desc.'" /></div>';
		
		$txt .= '<div class="col" style="width: 200px; text-align: right;">Prova di pagamento<br />(<strong>max 1Mb</strong>, formati accettati: PDF, JPG, GIF, BMP, PNG)</div>
		<div class="col nomargin" style="width: 500px; text-align: left;"><br />
		<input type="file" id="prova_pagamento" name="prova_pagamento" accept="image/jpeg,image/gif,image/png,application/pdf,image/bmp" required="required" />
		<div class="error-box" style="display:none;" id="invalidFile"><span class="box-icon"></span>Massima dimensione consentita: 1Mb</div>
		<div class="font-small">Forniscici una ricevuta del pagamento della quota di iscrizione.<br /><a href="modalita-di-pagamento/" title="Istruzioni di pagamento" target="_blank">Come pago?</a></div></div></div>';
		

		$txt .= '<div class="col" id="affiliazione_txt" style="width: 200px; text-align: right;" required="required" >Affiliazione</div>
		<div class="col nomargin" style="width: 500px; text-align: left;"><input id="affiliazione" name="affiliazione" size="50" type="text" required="required" value="'.
			$affiliazione.'" placeholder="Dove fai ricerca?" /></div>';
		
		$rev_checked = as_check_volunteer($vcode, REVIEWER_BIT) ? "checked" : "";
			
		$txt .= '<div class="col" style="width: 200px; text-align: right;"><label for="volontariato">Volontariato</label></div>
		<div class="col nomargin" style="width: 500px; text-align: left;">
		<input type="checkbox" name="revisore" value="true" '.$rev_checked.'> <strong>Revisore</strong> &mdash; voglio aiutare con la revisione di abstracts e grants.</div>';
		
			
        $txt .=		'<div id="cvdiv" class = "col">Inserisci un <strong>breve</strong> testo/CV per darci un\'idea delle tue competenze <span style = "color:gray;"> <em>[max 1000 caratteri]</em></span>
		<textarea id="cv" name="cv" cols="100" rows="10" maxlength="1000" placeholder="Una breve descrizione delle tue competenze">'.$cv.'</textarea></div><br />';
		}
	else if ($tipo == 2)
		{
		$txt .= '<div class="col" style="width: 200px; text-align: right;">Prova di pagamento<br />(<strong>max 1Mb</strong>, formati accettati: PDF, JPG, GIF, BMP, PNG)</div>
		<div class="col nomargin" style="width: 500px; text-align: left;"><br />
		<input type="file" id="prova_pagamento" name="prova_pagamento" accept="image/jpeg,image/gif,image/png,application/pdf,image/bmp" required="required" />
		<div class="error-box" style="display:none;" id="invalidFile"><span class="box-icon"></span>Massima dimensione consentita: 1Mb</div>
		<div class="font-small">Forniscici una ricevuta del pagamento della quota di iscrizione.<br /><a href="modalita-di-pagamento/" title="Istruzioni di pagamento" target="_blank">Come pago?</a></div></div>';

		$txt .= '<div class="col" style="width: 200px; text-align: right;">Professione</div>'.
			'<div class="col nomargin" style="width: 500px; text-align: left;">'.
			'<input id="professione" name="professione" required="required" size="50" type="text" value="'.$professione.'" /></div></div>';
		$txt .= '<div>In che modo contribuirai ad AIRIcerca? <span style = "color:gray;"> <em>[max 1000 caratteri]</em></span><br /><textarea id="candidatura" name="candidatura" cols="100" rows="10" 
			maxlength="1000" required="required">'.$candidatura.'</textarea></div>';
		
		$rev_checked = as_check_volunteer($vcode, REVIEWER_BIT) ? "checked" : "";
		
		$txt .= '<div class="col" style="width: 200px; text-align: right;"><label for="revisore">Volontariato</label></div>
		<div class="col nomargin" style="width: 500px; text-align: left;">
		<input type="checkbox" id="revisore" name="revisore" value="true" '.$rev_checked.' /> <strong>Revisore</strong> &mdash; voglio aiutare con la revisione di abstracts e grants.</div>';
		}

	$txt .= '<div style="margin: auto; width: 305px; clear: both; margin-bottom: 1em;">'.do_shortcode('[as_reCAPTCHA]').'</div>
	<div style="text-align: center;"><input type="submit" value="Procedi con la registrazione" /></div>
	<input type="hidden" name="t" value="'.$tipo.'" />
	</form><br />'.do_shortcode("[as_privacy-blurb]");

	return $txt;
	}

function as_conferma_dati($t)
	{
	// Importante per gli apostrofi!!! (es. in cognomi e affiliazioni)
	$_POST = stripslashes_deep($_POST);
	$page_ID = get_the_ID();
	$PLUGIN_BASE = $_SERVER['DOCUMENT_ROOT']."/wp-content/plugins/AIRIsoci/";

	$nome = isset($_POST['nome']) ? $_POST['nome'] : "";
	$cognome = isset($_POST['cognome']) ? $_POST['cognome'] : "";
	$email = isset($_POST['email']) ? $_POST['email'] : "";
	$codice_fiscale = isset($_POST['codice_fiscale']) ? $_POST['codice_fiscale'] : "";
	$affiliazione = isset($_POST['affiliazione']) ? $_POST['affiliazione'] : "";
	$citta = isset($_POST['citta']) ? $_POST['citta'] : "";
	$prova_pagamento = isset($_FILES['prova_pagamento']) ? $_FILES['prova_pagamento']['name'] : "";
	$cip2010 = isset($_POST['cip2010']) ? $_POST['cip2010'] : -1;
	$cip2010_desc = isset($_POST['cip2010_desc']) ? $_POST['cip2010_desc'] : "";
	$professione = isset($_POST['professione']) ? $_POST['professione'] : "";
	$t = isset($_POST['t']) ? (int)$_POST['t'] : -1;
	$cv = isset($_POST['cv']) ? substr($_POST['cv'], 0, 1000) : "";
	$candidatura = isset($_POST['candidatura']) ? substr($_POST['candidatura'], 0, 1000) : "";
	$revisore = isset($_POST['revisore']) ? $_POST['revisore'] : false;
	$vcode = isset($_POST['vcode']) ? (int)$_POST['vcode'] : as_build_volunteer_code($revisore);
	$lat = isset($_POST['lat']) ? (float)$_POST['lat'] : 90;
	$lng = isset($_POST['lng']) ? (float)$_POST['lng'] : 0;

	// Verifichiamo il noCaptcha reCaptcha
	if ($_SERVER["REQUEST_METHOD"] == "POST")
		{
		$recaptcha = $_POST['g-recaptcha-response'];
		if (!empty($recaptcha))
			{
			$cp = fopen($PLUGIN_BASE."/recaptcha-secret.txt", "r");
			$secret = trim(fgets($cp));
			fclose($cp);
			    
			$google_url="https://www.google.com/recaptcha/api/siteverify";
			$ip = $_SERVER['REMOTE_ADDR'];
			$url = $google_url."?secret=".$secret."&response=".$recaptcha."&remoteip=".$ip;
			
			$res = getCurlData($url);
			$res = json_decode($res, true);

			if (!$res['success'])
				{
				return "<div class='error-box'><span class='box-icon'></span>Devi confermare di non essere un robot</div>".
					as_hiddenFields($t, $nome, $cognome, $codice_fiscale, $email, $affiliazione, $citta, $lat, $lng, $prova_pagamento, $cip2010, $cip2010_desc, 
						$professione, $cv, $candidatura, $vcode, "Torna indietro");
				}
			}
		else
			{
			return "<div class='error-box'><span class='box-icon'></span>Devi confermare di non essere un robot</div>".
				as_hiddenFields($t, $nome, $cognome, $codice_fiscale, $email, $affiliazione, $citta, $lat, $lng, $prova_pagamento, $cip2010, $cip2010_desc, 
					$professione, $cv, $candidatura, $vcode, "Torna indietro");   
			}
		}
		
	$username = as_create_username($nome, $cognome);
	
	if ($t == 0)
		{
		if ($nome == "" || $cognome == "" || $email == "")
				{
				return "<div class='error-box'><span class='box-icon'></span>Tutti i campi devono essere completati</div>".
				as_hiddenFields($t, $nome, $cognome, $codice_fiscale, $email, $affiliazione, $citta, $lat, $lng, $prova_pagamento, $cip2010, $cip2010_desc, 
					$professione, $cv, $candidatura, $vcode, "Torna indietro");
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
		if ($nome == "" || $cognome == "" || $email == "" || $codice_fiscale == "" || $affiliazione == "" || $cip2010 == -1 || 
			$prova_pagamento == "" || $professione == "" || $citta == "")
				{
				return "<div class='error-box'><span class='box-icon'></span>Tutti i campi devono essere completati</div>".
				as_hiddenFields($t, $nome, $cognome, $codice_fiscale, $email, $affiliazione, $citta, $lat, $lng, $prova_pagamento, $cip2010, $cip2010_desc, 
					$professione, $cv, $candidatura, $vcode, "Torna indietro");
				}
		
		// controlliamo la prova di pagamento
		try {
			// Controlliamo che non ci siano stati errori durante l'upload
			if (!isset($_FILES['prova_pagamento']['error']) || is_array($_FILES['prova_pagamento']['error']))
				{
				throw new RuntimeException('Errore interno - parametri non validi.');
				}
	
			switch ($_FILES['prova_pagamento']['error']) 
				{
				// Tutto OK
				case UPLOAD_ERR_OK:
					break;
				// Niente file
				case UPLOAD_ERR_NO_FILE:
					throw new RuntimeException("E' richiesta una prova di pagamento");
				// Nota: questo semplicemente controlla che non abbiamo caricato un file > max_file_size in php.ini
				// quindi in teoria non dovremmo mai essere in questa situazione
				// Vedi anche: http://stackoverflow.com/questions/8300331/php-whats-the-point-of-upload-err-ini-size
				case UPLOAD_ERR_INI_SIZE:
				case UPLOAD_ERR_FORM_SIZE:
					throw new RuntimeException('Il file caricato è troppo grande.');
				default:
					throw new RuntimeException('Errore interno.');
				}
				
			if ($_FILES['prova_pagamento']['size'] > 1000000)
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
				    
			$MIME = finfo_file($finfo, $_FILES['prova_pagamento']['tmp_name']);

			if (false === $ext = array_search($MIME, $allowedMIME, true))
				{
				throw new RuntimeException('La prova di pagamento deve essere un\'immagine o un file PDF.');
				}
				
			if (!move_uploaded_file($_FILES['prova_pagamento']['tmp_name'], sprintf('wp-content/uploads/prove-pagamento/%s.%s', $username, $ext))) 
				{
				throw new RuntimeException('Errore interno - impossibile copiare la prova di affiliazione, 
					<a href="mailto:webmaster@airicerca.org" target="_blank">contattare l\'amministratore del sito</a>.');
				}
			}
		catch (RuntimeException $e)
			{
			return "<div class='error-box'><span class='box-icon'></span>".$e->getMessage()."</div>".
				as_hiddenFields($t, $nome, $cognome, $codice_fiscale, $email, $affiliazione, $citta, $lat, $lng, $prova_pagamento, $cip2010, $cip2010_desc, 
					$professione, $cv, $candidatura, $vcode, "Torna indietro");
			}


		$txt = "<div style='line-height:2em'>Prima di terminare l'iscrizione ad AIRIcerca come socio, conferma che tutti i dati inseriti siano corretti <br />
			<form action='".get_site_url()."/?page_id=".$page_ID."&tp=1&ps=3' method='POST'>
			<strong>Nome:</strong> $nome<input type='hidden' name='nome' value=\"$nome\" /><br />
			<strong>Cognome:</strong> $cognome<input type='hidden' name='cognome' value=\"$cognome\" /><br />
			<strong>Codice fiscale:</strong> $codice_fiscale<input type='hidden' name='codice_fiscale' value='$codice_fiscale' /></br>
			<strong>e-mail:</strong> $email<input type='hidden' name='email' value='$email' /><br />
			<strong>Professione:</strong> $professione<input type='hidden' name='professione' value=\"$professione\"><br />
			<strong>Affiliazione:</strong> $affiliazione<input type='hidden' name='affiliazione' value=\"$affiliazione\" /><br />
			<strong>Città:</strong> $citta<input type='hidden' name='citta' value=\"$citta\" /><input type='hidden' name='lat' value=\"$lat\" /><input type='hidden' name='lng' value=\"$lng\" /><br />
			<strong>Prova di pagamento:</strong> $prova_pagamento<input type='hidden' name='prova_pagamento' value=\"$prova_pagamento\" /><br />";
		if ($vcode !== 0) {
		    $sfx = ' ';
		    if (as_check_volunteer($vcode, REVIEWER_BIT))
		        $sfx .= 'Revisore';
		        $txt .= "<strong>Volontariato:</strong>$sfx<input type='hidden' name='vcode' value='$vcode' /><br />";
		}
		if ($professione != "Studente")
			{
			$txt .= "<strong>Ambito:</strong> $cip2010_desc<input type='hidden' name='cip2010' value='$cip2010' /><input type='hidden' name='cip2010_desc' value='$cip2010_desc' /><br />
			<strong>Il tuo CV</strong>:<br />
			<textarea name='cv' readonly='readonly' cols='100' rows='10'>".$cv."</textarea>";
			}

		
		$txt .=	"<div style='line-height:2em'>&nbsp;</div>
			<div style='text-align: center'><input type='submit' value='Procedi' /></div>
			</form>";
		}
	else if ($t == 2)
		{
		if ($nome == "" || $cognome == "" || $email == "" || $codice_fiscale == "" || $prova_pagamento == "")
				{
				return "<div class='error-box'><span class='box-icon'></span>Tutti i campi devono essere completati</div>".
				as_hiddenFields($t, $nome, $cognome, $codice_fiscale, $email, $affiliazione, $citta, $lat, $lng, $prova_pagamento, $cip2010, $cip2010_desc, 
					$professione, $cv, $candidatura, $vcode, "Torna indietro");
				}

		$txt = "<div style='line-height:2em'>Prima di terminare l'iscrizione ad AIRIcerca come socio, conferma che tutti i dati inseriti siano corretti <br />
			<form action='".get_site_url()."/?page_id=".$page_ID."&tp=2&ps=3' method='POST'>
			<strong>Nome:</strong> $nome<input type='hidden' name='nome' value=\"$nome\" /><br />
			<strong>Cognome:</strong> $cognome<input type='hidden' name='cognome' value=\"$cognome\" /><br />
			<strong>Codice fiscale:</strong> $codice_fiscale<input type='hidden' name='codice_fiscale' value='$codice_fiscale' /></br>
			<strong>e-mail:</strong> $email<input type='hidden' name='email' value='$email' /><br />
			<strong>Prova di pagamento:</strong> $prova_pagamento<input type='hidden' name='prova_pagamento' value=\"$prova_pagamento\" /><br />
			<strong>Professione:</strong> $professione<input type='hidden' name='professione' value=\"$professione\"><br />";
		
		if ($vcode !== 0) {
		    $sfx = ' ';
		    if (as_check_volunteer($vcode, REVIEWER_BIT))
		        $sfx .= 'Revisore';
		        $txt .= "<strong>Volontariato:</strong>$sfx<input type='hidden' name='vcode' value='$vcode' /><br />";
		}
		
		$txt.= "<strong>Come puoi aiutarci</strong>:<br />
			<textarea name='candidatura' readonly='readonly' cols='100' rows='10'>$candidatura</textarea>
			<div style='line-height:2em'>&nbsp;</div>
			<div style='text-align: center'><input type='submit' value='Procedi' /></div>
			</form>";
		}
	else
		{
		return "<div class='error-box'><span class='box-icon'></span>Errore interno</div>".
			as_hiddenFields($t, $nome, $cognome, $codice_fiscale, $email, $affiliazione, $citta, $lat, $lng, $prova_pagamento, $cip2010, $cip2010_desc, 
			    $professione, $cv, $candidatura, $vcode, "Torna indietro");
		}
			
	$txt .= as_hiddenFields($t, $nome, $cognome, $codice_fiscale, $email, $affiliazione, $citta, $lat, $lng, $prova_pagamento, $cip2010, $cip2010_desc, 
	    $professione, $cv, $candidatura, $vcode, "Modifica i dati");
		
	return $txt;
	}
	
function as_add_user($t)
	{
	// La variabile globale che ci permette di accedere al DB
	global $wpdb;
	// Importante per gli apostrofi!!! (es. in cognomi e affiliazioni)
	$_POST = stripslashes_deep($_POST);
	$PLUGIN_BASE = $_SERVER['DOCUMENT_ROOT']."/wp-content/plugins/AIRIsoci/";

	$nome = isset($_POST['nome']) ? ucwords(mb_strtolower($_POST['nome'])) : "";
	$cognome = isset($_POST['cognome']) ? ucwords(mb_strtolower($_POST['cognome'])) : "";
	$email = isset($_POST['email']) ? $_POST['email'] : "";
	$codice_fiscale = isset($_POST['codice_fiscale']) ? $_POST['codice_fiscale'] : "";
	$affiliazione = isset($_POST['affiliazione']) ? $_POST['affiliazione'] : "";
	$citta = isset($_POST['citta']) ? $_POST['citta'] : "";
	//$prova = isset($_POST['prova']) ? $_POST['prova'] : "";
	$prova_pagamento = isset($_POST['prova_pagamento']) ? $_POST['prova_pagamento'] : "";
	$cip2010 = isset($_POST['cip2010']) ? $_POST['cip2010'] : -1;
	$cip2010_desc = isset($_POST['cip2010_desc']) ? $_POST['cip2010_desc'] : "";
	$professione = isset($_POST['professione']) ? $_POST['professione'] : "";
	$cv = isset($_POST['cv']) ? substr(multiline_sanitize($_POST['cv']), 0, 1000) : "";
	$candidatura = isset($_POST['candidatura']) ? substr(multiline_sanitize($_POST['candidatura']), 0, 1000) : "";
	$vcode = isset($_POST['vcode']) ? (int)$_POST['vcode'] : 0;
	$t = isset($_GET['tp']) ? (int)$_GET['tp'] : -1;
	$lat = isset($_POST['lat']) ? (float)$_POST['lat'] : 90;
	$lng = isset($_POST['lng']) ? (float)$_POST['lng'] : 0;
	
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
			if ($nome == "" || $cognome == "" || $email == "" || $codice_fiscale == "" || $affiliazione == "" || // $cv == "" || 
				$prova_pagamento == "" || $cip2010 == -1 || $professione == "" || $citta == "")
				die("Errore interno - 3");
			}
		else
			{
			if ($nome == "" || $cognome == "" || $email == "" || $codice_fiscale == "" || $affiliazione == "" || 
				$prova_pagamento == "" || $professione == "" || $citta == "")
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
	
	$username = as_create_username($nome, $cognome);
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
		// Aggiungiamo un campo in meta con il codice che verrà usato per scegliere la password una volta che l'utente sarà confermato dal Tesoriere
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
		$body = "<div style='text-align:center'><img src='https://www.airicerca.org/wp-content/uploads/2014/03/airi-logo-256x62.png' /><br />
			Congratulazioni <strong>$nome $cognome</strong>, la tua iscrizione come amico di AIRIcerca &egrave; completa!<br />
			Sei stato iscritto alla nostra mailing list e ti terremo informato sulle nostre iniziative!<br />
			<hr />
			Vuoi aiutare AIRIcerca? Puoi <a href='https://www.airicerca.org/collabora-con-noi/' target='_blank'>collaborare</a> con noi 
			o <a href='https://www.airicerca.org/dona-ora/' target='_blank'>contribuire</a> con una donazione! 
			</div>";
		
		$headers[] = "From: Iscrizioni AIRIcerca <webmaster@airicerca.org>";
		$headers[] = "Reply-To: webmaster@airicerca.org";
		$headers[] = "Content-Type: text/html";
		$headers[] = "charset=UTF-8";
		$headers[] = "MIME-Version: 1.0";
		$headers[] = "X-Mailer: PHP/".phpversion();
		
		wp_mail($to, $subject, $body, $headers);
		
		// Aggiungiamo l'utente alla lista di MailChimp
		// Non fare niente se si tratta di un test
		if (strpos(get_site_url(), 'localhost') !== false) {
		    $body .= "<BR /><BR /><BR /> Mailchimp list=".$listID." key=".$apikey;
		} else {
		    as_add_mailchimp($t, $nome, $cognome, $to);
		}
		
	}
	// SOCIO
	else if ($t == 1 || $t == 2)
		{
		$res = update_user_meta($user_id, 'first_name', $nome);
		$res1 = update_user_meta($user_id, 'last_name', $cognome);
		$extprova_pagamento = pathinfo($prova_pagamento, PATHINFO_EXTENSION);
		if ($extprova_pagamento == "jpeg")
			$extprova_pagamento = "jpg";

		if ($t == 1)
			{
			$res2 = add_user_meta($user_id, 'dati_personali', array('codice_fiscale' => $codice_fiscale,
								'affiliazione'	=> stripslashes($affiliazione),
								'prova_pagamento'=> $username.".".strtolower($extprova_pagamento),
								'citta'		=> $citta,
			                    'lat'       => $lat,
			                    'lng'       => $lng,
								'cip2010'	=> $cip2010,
			                    'cip2010_desc'	=> $cip2010_desc,
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
								'prova_pagamento' => $username.".".strtolower($extprova_pagamento),
								'citta'		=> $citta,
			                    'lat'       => $lat,
			                    'lng'       => $lng,
								'tipo_utente'	=> $t));
			}

		if (as_check_volunteer($vcode, REVIEWER_BIT))
		  $res3 = add_user_meta($user_id, "revisoreGrant", 1);
		else 
		  $res3 = true;
			
		if (!$res || !$res1 || !$res2 || !$res3)
			{
			$txt .= "<div class='error-box'><span class='box-icon'></span>C'&egrave; stato un errore durante l'inserimento dell'utente nel database di AIRIcerca<br />";
			$txt .= "L'iscrizione proceder&agrave; comunque, ma il profilo utente potrebbe non essere completo.</div>";
			}

		// Aggiungiamo l'utente come socio, ma lasciamo la scadenza a 0000-00-00 fino a che non avremo confermato il pagamento
		$res &= $wpdb->insert("wp_pmpro_memberships_users", 
			array("user_id" => $user_id, "membership_id" => 2, "code_id" => 0,
			"initial_payment" => 0.00, "billing_amount" => 0.00, "cycle_number" => 0, 
			"startdate" => date("Y-m-d H:i:s"), "enddate" => "0000-00-00 00:00:00"));

		if ($t == 1 || $t == 2) // Prima email
			{
			$testoMail = "Congratulazioni <strong>$nome $cognome</strong>, abbiamo ricevuto la tua richiesta di iscrizione come socio di AIRIcerca!<br />
				Una volta che la tua iscrizione sarà confermata dal Direttivo, ti invieremo la tessera e le credenziali per accedere all'area soci.<br />
				Ti chiediamo gentilmente di portare pazienza in quanto questo processo non è automatico, quindi ci potrebbe volere qualche giorno.<br />
				<br />
				Per ogni domanda non esitare a <a href='mailto:webmaster@airicerca.org'>contattarci!</a>.
				<hr /><br />
				Vuoi aiutare AIRIcerca? Puoi <a href='https://www.airicerca.org/collabora-con-noi/' target='_blank'>collaborare</a> con noi 
				o <a href='https://www.airicerca.org/dona-ora/' target='_blank'>contribuire</a> con una donazione!";

			$txt.= "<div style='text-align:center'><img src='/wp-content/uploads/2014/03/airi-logo-256x62.png' /><br />".$testoMail."</div>";

			$to = $email;
			$subject = "Benvenuto su AIRIcerca!";
			$body = "<div style='text-align:center'><img src='https://www.airicerca.org/wp-content/uploads/2014/03/airi-logo-256x62.png' /><br />".$testoMail."</div>";
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
	
function as_create_username($nome, $cognome)
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

	
// Crea un form per tornare alla pagina di inserimento dati (in seguito ad errore o per modifica dati)
function as_hiddenFields($t, $nome, $cognome, $codice_fiscale, $email, $affiliazione, $citta, $lat, $lng, $prova_pagamento, $cip2010, $cip2010_desc, $professione, $cv, $candidatura, $vcode, $buttonTxt)
	{
	$page_ID = get_the_ID();
	$txt = "<form action='".get_site_url()."/?page_id=".$page_ID."&tp=$t&ps=1' method='POST'>
	<div style='text-align: center'><input type='submit' value='$buttonTxt' /></div>
	<input type=\"hidden\" name=\"t\" value=\"$t\" />
	<input type=\"hidden\" name=\"nome\" value=\"$nome\" />
	<input type=\"hidden\" name=\"cognome\" value=\"$cognome\" /><br />
	<input type=\"hidden\" name=\"codice_fiscale\" value=\"$codice_fiscale\" /><br />
	<input type=\"hidden\" name=\"email\" value=\"$email\" /><br />
	<input type=\"hidden\" name=\"affiliazione\" value=\"$affiliazione\" /><br />
	<input type=\"hidden\" name=\"citta\" value=\"$citta\" /><br />
    <input type=\"hidden\" name=\"lat\" value=\"$lat\" /><br />
    <input type=\"hidden\" name=\"lng\" value=\"$lng\" /><br />
	<input type=\"hidden\" name=\"prova_pagamento\" value=\"$prova_pagamento\" /><br />
	<input type=\"hidden\" name=\"cip2010\" value=\"$cip2010\" /><br />
    <input type=\"hidden\" name=\"cip2010_desc\" value=\"$cip2010_desc\" /><br />
	<input type=\"hidden\" name=\"professione\" value=\"$professione\" /><br />
	<input type=\"hidden\" name=\"cv\" value=\"$cv\" /><br />
	<input type=\"hidden\" name=\"candidatura\" value=\"$candidatura\" /><br />
    <input type=\"hidden\" name=\"vcode\" value=\"$vcode\" /><br />

	</form></div>";
	
	return $txt;
	}

function as_gestione_soci($atts)
	{
	// La variabile globale che ci permette di accedere al DB
	global $wpdb;

	$nextyear = date("Y")+1;
	$endsub = date("Y-m-d 00:00:00", mktime(0, 0, 0, 1, 31, $nextyear));
	$endsubShort = date("d/m/Y", mktime(0, 0, 0, 1, 31, $nextyear));
	
	// Cerchiamo gli ID dei membri
	// I membri di AIRIcerca avranno una entry wp_capabilities nella tabella wp_usermeta
	// (Gli utenti AIRInforma hanno wp12_capabilities, AIRIsocial wp13_capabilities).
	$res = $wpdb->get_results("SELECT DISTINCT(pm.user_id), 
		(pm.enddate > '$nextyear-01-01') rinnovato
		FROM `wp_pmpro_memberships_users` pm 
		LEFT JOIN wp_usermeta um ON pm.user_id = um.user_id 
		WHERE um.meta_key = 'wp_capabilities'"); //  pm.status = 'active' AND
	
	// Concateniamo tutti gli ID con delle virgole
	$ids = [];
	$rinnovato = [];
	foreach ($res as $id)
		{
		$ids[] = $id->user_id;
		$rinnovato[$id->user_id] = $id->rinnovato;
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

	$txt .= "<div style='text-align:center; margin:0.5em;'>La conferma di pagamento o l'accettazione della candidatura attivano l'iscrizione fino al: <strong>".$endsubShort."</strong></div><br />";
	
	$txt .= "<label for='mostra'>Mostra: </label><select id='mostra' class='font-big' style='padding: 0.2em;' name='mostra'>
		<option value='0'>Tutti</option>
		<option selected='selected' value='1'>Soci</option>
		<option value='2'>Soci ricercatori</option>
		<option value='3'>Soci non ricercatori</option>
		<option value='4'>Soci non approvati</option>
		<option value='5'>Amici</option>
		</select>";

	$txt .="<table id='subscribers-table' style='color:black'>
		<thead>
			<tr>
			<th>ID</th>
			<th>Nome</th>
			<th>e-mail</th>
			<th>Tipo</th>
			<th>Professione</th>
			<th>Pagam.</th>
			<th>Tessera</th>
			<th>Pagato</th>
			<th>Approvato</th>
			<th>CV</th>
			<th>Candidatura</th>
			<th>Affiliazione</th>
			<th>Ricercatore</th>
			<th>Registrato</th>
			<th>Rinn.</th>
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

		if (isset($dati_personali["prova_pagamento"]))
			{
			$prova_pag = "<a href=\"/wp-content/uploads/prove-pagamento/".$dati_personali["prova_pagamento"].
				"\" target='_blank'><img src='".plugin_dir_url( __FILE__ )."images/document-euro.png' width='35' /></a>";
			}
		else
			$prova_pag = "";
			
		if (in_array($dati_personali['professione'], array('Ricercatore', 'Dottorando')))
		{
		    if (array_key_exists("cip2010_desc", $dati_personali))
		    {
		        $professione = $dati_personali['professione']." - ".$dati_personali["cip2010_desc"];
		    }
		    else if (array_key_exists("ambito", $dati_personali)) 
		    {
			     $professione = $dati_personali['professione']." - ".$ambiti[$dati_personali["ambito"]];
		    }
		    
		}
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
		if (strcmp($tessera, "Non generato"))
			$rinn = ($rinnovato[$u->id] == 1 ? "&#10004;" : "X");
		else
			$rinn = "";
		
		$txt .= "<tr id='row_$u->id'>
			<td>$u->id</td>
			<td>".$nomi[$i]->nome." ".$cognomi[$i]->cognome."</td>
			<td>$u->email</td>
			<td>$livello</td>
			<td>".$professione."</td>
			<td>$prova_pag</td>
			<td>".$tessera."</td>
			<td>$pagato</td>
			<td>$conferma_prova</td>
			<td>".$dati_personali['cv']."</td>
			<td>".$dati_personali['candidatura']."</td>
			<td>".$dati_personali['affiliazione']."</td>
			<td>$ricercatore</td>
			<td>$registrato</td>
			<td>$rinn".$res->rinnovato[$i]."</td>
			</tr>";
		
		$i++;
		}
	$txt .= "</tbody>
		</table>";
		
	return $txt;
	}
	
function as_reCAPTCHA()
	{
	$PLUGIN_BASE = $_SERVER['DOCUMENT_ROOT']."/wp-content/plugins/AIRIsoci/";
    $cp = fopen($PLUGIN_BASE."/recaptcha-sitekey.txt", "r");
    $key = trim(fgets($cp));
    fclose($cp);
	    
	return '<div class="g-recaptcha" style="margin:auto" data-sitekey="'.$key.'"></div>';
	}


//[privacyblurb]
function as_print_privacy_blurb()
	{
	return '<div class="font-small"><strong>INFORMAZIONI SULLA PRIVACY: </strong>Tutti i dati personali forniti saranno trattati nel rispetto del D.Lgs. 30 giugno 2003, n. 196 '.
		'recante il Codice in materia di protezione dei dati personali. I dati personali verranno trattati elettronicamente e saranno conservati all\'interno del database '.
		'digitale a tal uopo predisposto. Tutti i dati personali saranno trattati rispettando le misure minime di sicurezza prescritte dalla Legge, in modo da ridurne al '.
		'minimo i rischi di distruzione o perdita, di accesso non autorizzato o di trattamento non conforme alle finalità della raccolta. In relazione al trattamento dei '.
		'dati personali, il socio iscritto può esercitare i diritti riconosciutigli dall\'art. 7 del D.Lgs. 196/2003, e dunque, il diritto di accedere ai dati, il diritto '.
		'di ottenere rettifica e/o aggiornamento e/o integrazione dei dati, il diritto di ottenere cancellazione/trasformazione".</div>';
	}

//[lista-soci]
function as_lista_soci()
	{
	global $wpdb;

	// Cerchiamo gli ID dei membri
	// I membri di AIRIcerca avranno una entry wp_capabilities nella tabella wp_usermeta
	// (Gli utenti AIRInforma hanno wp12_capabilities, AIRIsocial wp13_capabilities).
	$res = $wpdb->get_results("SELECT DISTINCT(pm.user_id) id FROM `wp_pmpro_memberships_users` pm 
		LEFT JOIN wp_usermeta um ON pm.user_id = um.user_id 
		WHERE pm.status = 'active' AND um.meta_key = 'wp_capabilities'");
	// Concateniamo tutti gli ID con delle virgole
	$ids = [];
	foreach ($res as $id)
		{
		$ids[] = $id->id;
		}
		
	$ids = implode(",", $ids);
	$nomi = $wpdb->get_results("SELECT meta_value nome FROM `wp_usermeta` WHERE meta_key LIKE 'first_name' AND user_id IN ($ids) ORDER BY user_id ASC");
	$cognomi = $wpdb->get_results("SELECT meta_value cognome FROM `wp_usermeta` WHERE meta_key LIKE 'last_name' AND user_id IN($ids) ORDER BY user_id ASC");
	$tessere = $wpdb->get_results("SELECT user_id, GROUP_CONCAT( (CASE WHEN meta_key = 'card_number' THEN meta_value ELSE '' END) SEPARATOR '') 'tessera'
		FROM wp_usermeta WHERE user_ID IN($ids) GROUP BY user_id ORDER BY user_id ASC");

	$txt = "";

	$txt .= "<table>
		<tr><td>Numero di tessera</td><td>Nome</td></tr>";

	for ($i = 0; $i<count($res); $i++)
		{
		if ($tessere[$i]->tessera != "")
			{
			$txt .= "<tr>
				<td>".$tessere[$i]->tessera."</td>
				<td>".$nomi[$i]->nome." ".$cognomi[$i]->cognome."</td>
				</tr>";
			}
		}

	$txt .= "</table>";

	return $txt;
	}

function as_profilo_utente()
	{
	$txt = "";

	$ui = get_currentuserinfo();

#	$txt .= "<input type='text' value='". $ui->display_name."' style='font-size:3em' placeholder='Il tuo nome' />";
	$txt .= "<h2>".$ui->display_name."</h2>";

	$txt .= get_avatar(get_current_user_id(), 256); 

	$txt .= "Socio fino al ".do_shortcode("[pmpro_expiration_date]");

	return $txt;
	}

function as_get_tessera()
	{
//	file_get_contents(WP_PLUGIN_DIR."/gestione-soci/tmp/$numero_tessera.pdf"
//	https://www.airicerca.org/wp-content/plugins/AIRIsoci/tessera.php?id=901
	}

// function multiline_sanitize($str)
// 	{
// 	    return (implode("\n", array_map( 'sanitize_text_field', explode("\n", $str))));
// 	}

add_shortcode('iscrizione-AIRIcerca', 'as_dispatcher_iscrizione');
add_shortcode('gestione-soci', 'as_gestione_soci');
add_shortcode('as_reCAPTCHA', 'as_reCAPTCHA');
add_shortcode('as_privacy-blurb', 'as_print_privacy_blurb');
add_shortcode('as-lista-soci', 'as_lista_soci');
add_shortcode('as-profilo', 'as_profilo_utente');
add_shortcode('as-get-tessera', 'as_get_tessera');

include 'modifica_utente.php';

?>