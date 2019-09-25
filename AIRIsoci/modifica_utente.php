<?php

// Questa funzione rimanda semplicemente all'opportuna funzione per i vari step
function as_dispatcher_modifica()
{
    $txt = "";
    
    if (!isset($_GET['ps']))
    {
        $txt .= "Errore interno.<br />";
        $txt .= "<a href = '/'>".as_logo_AIRIcerca(TRUE)."</a>";
        return $txt;
    }
    
    $step = (int)$_GET['ps'];
    
    if ($step == 1)
    {
        $txt .= as_modifica_utente();
    }
    else if ($step == 2)
    {
        $txt .= as_conferma_modifiche();
    }
    else if ($step == 3)
    {
        $txt .= as_modify_user();
    }
    
    return $txt;
}

function as_modifica_utente() {
    global $wpdb;
    
    $user_id = get_current_user_id();
    $page_ID = get_the_ID();
        
    $livelli = array("Fond", "Ordin", "Onor", "Amico");
    $ambiti = array("Bio/Med", "Chim/Fis/Geo", "Uman", "Giur/Econ", "Ingegn", "Arch/Des", "Matem");
    
    $nome = get_user_meta($user_id, "first_name", true);
    $cognome = get_user_meta($user_id, "last_name", true);
    
    $dp_email = $wpdb->get_results("SELECT user_email FROM `wp_users` WHERE `ID` = $user_id")[0]->user_email;
        
    $dati_personali = get_user_meta($user_id, "dati_personali", true);
        
    $codice_fiscale    = $dati_personali['codice_fiscale'];
    $dp_citta          = $dati_personali['citta'];
    $dp_cip2010        = $dati_personali['cip2010'];
    $dp_2010_desc      = $dati_personali['cip2010_desc'];
    $dp_professione    = $dati_personali['professione'];
    $dp_affiliazione   = $dati_personali['affiliazione'];
    $dp_cv             = $dati_personali['cv'];
    $dp_lat            = $dati_personali['lat'];
    $dp_lng            = $dati_personali['lng'];
    $dp_candidatura    = $dati_personali['candidatura'];
    $dp_t              = $dati_personali['tipo_utente'];
    
    $dp_revisore = get_user_meta($user_id, "revisoreGrant", true) == 1;
    
    $_POST = stripslashes_deep($_POST);
    
    $email = isset($_POST['email']) ? $_POST['email'] : $dp_email;
    $affiliazione = isset($_POST['affiliazione']) ? $_POST['affiliazione'] : $dp_affiliazione;
    $citta = isset($_POST['citta']) ? $_POST['citta'] : $dp_citta;
    $cip2010 = isset($_POST['cip2010']) ? $_POST['cip2010'] : $dp_cip2010;
    $cip2010_desc = isset($_POST['cip2010_desc']) ? $_POST['cip2010_desc'] : $dp_2010_desc;
    $professione = isset($_POST['professione']) ? $_POST['professione'] : $dp_professione;
    $t = isset($_POST['t']) ? (int)$_POST['t'] : $dp_t;
    $cv = isset($_POST['cv']) ? substr($_POST['cv'], 0, 1000) : $dp_cv ;
    $candidatura = isset($_POST['candidatura']) ? substr($_POST['candidatura'], 0, 1000) : $dp_candidatura ;
    $revisore = isset($_POST['revisore']) ? $_POST['revisore'] : $dp_revisore;
    $lat = isset($_POST['lat']) ? (float)$_POST['lat'] : $dp_lat;
    $lng = isset($_POST['lng']) ? (float)$_POST['lng'] : $dp_lng ;
    
    $txt = '';
    
    if ($t == 0) {
        $txt = '<div style="text-align: center;">Come amico di AIRIcerca, non ci sono dati personali da modificare. </br>Però puoi sempre decidere di diventare socio!</div>';
    } else {
    
    $txt = '<div style="text-align: center;">Ecco qui i tuoi dati personali. I dati in grigio non possono essere modificati.</div>
	    
	<div style="line-height=2em">&nbsp;</div>
	<form id="form-registrazione" action="'.get_site_url().'/?page_id='.$page_ID.'&ps=2" method="POST" enctype="multipart/form-data">
	<div class="cols-wrapper cols-2" style="width: 750px; margin: auto;">
	<div class="col" style="width: 200px; text-align: right;"><label for="nome">Nome</label></div>
	<div class="col nomargin" style="width: 500px; text-align: left;"><input id="nome" name="nome" style="background-color: lightgrey;" disabled size="50" type="text" value="'.$nome.'" /></div>
	<div class="col" style="width: 200px; text-align: right;"><label for="cognome">Cognome</label></div>
	<div class="col nomargin" style="width: 500px; text-align: left;"><input id="cognome" name="cognome" style="background-color: lightgrey;" disabled size="50" type="text" value="'.$cognome.'" /></div>
	<div class="col" style="width: 200px; text-align: right;"><label for="codice_fiscale">Codice fiscale</label></div>
	<div class="col nomargin" style="width: 500px; text-align: left;"><input maxlength="16" id="codice_fiscale" name="codice_fiscale" style="background-color: lightgrey;" disabled size="50" type="text" value="'.$codice_fiscale.'" /></div>
	<div class="col" style="width: 200px; text-align: right;"><label for="email">Indirizzo e-mail</label></div>
	<div class="col nomargin" style="width: 500px; text-align: left;"><input id="email" name="email" required="required" size="50" type="email" placeholder="Il tuo indirizzo e-mail" value="'.$email.'" /></div>
	<div class="col" style="width: 200px; text-align: right;"><label for="confirmemail">Ripeti indirizzo e-mail</label></div>
	<div class="col nomargin" style="width: 500px; text-align: left;"><input id="confirmemail" name="confirmemail" required="required" size="50" type="email" placeholder="Il tuo indirizzo e-mail" value="'.$email.'" />
	<div class="error-box" style="display:none;" id="invalidEmail"><span class="box-icon"></span>Gli indirizzi email non corrispondono</div></div>
	<div class="col" style="width: 200px; text-align: right;"><label for="citta">Città</label></div>
	<div class="col nomargin" style="width: 500px; text-align: left;"><input id="citta" name="citta" required="required" size="50" type="text" placeholder="La città in cui vivi: es. Roma" value="'.$citta.'" /><input type="hidden" name="lat" id="lat" /> <input type="hidden" name="lng" id="lng" /></div>';

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
        
    
    if ($professione != "Studente")
    {
        if ($cip2010 != -1)
            $select_opt = '<option value="'.$cip2010.'" selected>'.$cip2010_desc.'</option>';
            
        $txt .= '<div class="col" style="width: 200px; text-align: right;">Ambito di ricerca</div>
        <div class="col nomargin" style="width: 500px; text-align: left;">
		<select id="cip_select" class="js-example-basic-single form-control" name="cip2010" onchange="cipSelectFunction()" style="width: 500px;">
		'.$select_opt.'</select><input type="hidden" id="cip2010_desc" name="cip2010_desc" value="'.$cip2010_desc.'" /></div>';    
    }
    
    $txt .= '<div class="col" id="affiliazione_txt" style="width: 200px; text-align: right;" required="required" >Affiliazione</div>
		<div class="col nomargin" style="width: 500px; text-align: left;"><input id="affiliazione" name="affiliazione" size="50" type="text" required="required" value="'.
		$affiliazione.'" placeholder="Dove fai ricerca?" /></div>';
    
    $rev_checked = $revisore ? "checked" : "";
    
    $txt .= '<div class="col" style="width: 200px; text-align: right;"><label for="revisore">Volontariato</label></div>
		<div class="col nomargin" style="width: 500px; text-align: left;">
		<input type="checkbox" id="revisore" name="revisore" value="true" '.$rev_checked.' /> <strong>Revisore</strong> &mdash; voglio aiutare con la revisione di abstracts e grants.</div>';

    if ($t == 1) {
        $txt .=	'<div id="cvdiv" class = "col">Inserisci un <strong>breve</strong> testo/CV per darci un\'idea delle tue competenze <span style = "color:gray;"> <em>[max 1000 caratteri]</em></span>
    		<textarea id="cv" name="cv" cols="100" rows="10" maxlength="1000" placeholder="Una breve descrizione delle tue competenze">'.$cv.'</textarea></div><br />';
    } else if ($t==2) {
        $txt .= '<div class = "col">In che modo contribuirai ad AIRIcerca? <span style = "color:gray;"> <em>[max 1000 caratteri]</em></span><br /><textarea id="candidatura" name="candidatura" cols="100" rows="10"
    			maxlength="1000" required="required">'.$candidatura.'</textarea></div>';
    }
    
    $txt .= '<div style="margin: auto; width: 305px; clear: both; margin-bottom: 1em;">'.do_shortcode('[as_reCAPTCHA]').'</div>
    	<div style="text-align: center;"><input type="submit" value="Modifica dati" /></div>
    	<input type="hidden" name="t" value="'.$t.'" />
        </div>
    	</form><br />'.do_shortcode("[as_privacy-blurb]");
    }
    
    return $txt;
}

function as_conferma_modifiche()
{
    // Importante per gli apostrofi!!! (es. in cognomi e affiliazioni)
    $_POST = stripslashes_deep($_POST);
    $page_ID = get_the_ID();
    $PLUGIN_BASE = $_SERVER['DOCUMENT_ROOT']."/wp-content/plugins/AIRIsoci/";
    
    // i campi grigi non vengono inviati dal post
    global $wpdb;
    
    $user_id = get_current_user_id();
    
    $nome = get_user_meta($user_id, "first_name", true);
    $cognome = get_user_meta($user_id, "last_name", true);
        
    $dati_personali = get_user_meta($user_id, "dati_personali", true);
    
    $codice_fiscale = $dati_personali['codice_fiscale'];

    $email = isset($_POST['email']) ? $_POST['email'] : "";
    $affiliazione = isset($_POST['affiliazione']) ? $_POST['affiliazione'] : "";
    $citta = isset($_POST['citta']) ? $_POST['citta'] : "";
    $cip2010 = isset($_POST['cip2010']) ? $_POST['cip2010'] : -1;
    $cip2010_desc = isset($_POST['cip2010_desc']) ? $_POST['cip2010_desc'] : "";
    $professione = isset($_POST['professione']) ? $_POST['professione'] : "";
    $t = isset($_POST['t']) ? (int)$_POST['t'] : -1;
    $cv = isset($_POST['cv']) ? substr($_POST['cv'], 0, 1000) : "";
    $candidatura = isset($_POST['candidatura']) ? substr($_POST['candidatura'], 0, 1000) : "";
    $prova_pagamento = isset($_FILES['prova_pagamento']) ? $_FILES['prova_pagamento']['name'] : "";
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
    
    if ($t == 0)
    {
        if ($nome == "" || $cognome == "" || $email == "")
        {
            return "<div class='error-box'><span class='box-icon'></span>Tutti i campi devono essere completati</div>".
                as_hiddenFields($t, $nome, $cognome, $codice_fiscale, $email, $affiliazione, $citta, $lat, $lng, $prova_pagamento, $cip2010, $cip2010_desc,
                    $professione, $cv, $candidatura, $vcode, "Torna indietro");
        }
        
        $txt = "<div style='line-height:2em'>Prima di terminare l'iscrizione ad AIRIcerca come amico, conferma che tutti i dati inseriti siano corretti <br />
			<form action='".get_site_url()."/?page_id=".$page_ID."&ps=3' method='POST'>
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
            $professione == "" || $citta == "")
        {
            return "<div class='error-box'><span class='box-icon'></span>Tutti i campi devono essere completati</div>".
                as_hiddenFields($t, $nome, $cognome, $codice_fiscale, $email, $affiliazione, $citta, $lat, $lng, $prova_pagamento, $cip2010, $cip2010_desc,
                    $professione, $cv, $candidatura, $vcode, "Torna indietro");
        }
                
        $txt = "<div style='line-height:2em'>Prima di terminare l'iscrizione ad AIRIcerca come socio, conferma che tutti i dati inseriti siano corretti <br />
			<form action='".get_site_url()."/?page_id=".$page_ID."&ps=3' method='POST'>
			<strong>Nome:</strong> $nome<input type='hidden' name='nome' value=\"$nome\" /><br />
			<strong>Cognome:</strong> $cognome<input type='hidden' name='cognome' value=\"$cognome\" /><br />
			<strong>Codice fiscale:</strong> $codice_fiscale<input type='hidden' name='codice_fiscale' value='$codice_fiscale' /></br>
			<strong>e-mail:</strong> $email<input type='hidden' name='email' value='$email' /><br />
			<strong>Professione:</strong> $professione<input type='hidden' name='professione' value=\"$professione\"><br />
			<strong>Affiliazione:</strong> $affiliazione<input type='hidden' name='affiliazione' value=\"$affiliazione\" /><br />
			<strong>Città:</strong> $citta<input type='hidden' name='citta' value=\"$citta\" /><input type='hidden' name='lat' value=\"$lat\" /><input type='hidden' name='lng' value=\"$lng\" /><br />";
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
			<div style='text-align: center'><input type='submit' value='Procedi con l&#39;aggiornamento' /></div>
			</form>";
    }
    else if ($t == 2)
    {
        if ($nome == "" || $cognome == "" || $email == "" || $codice_fiscale == "")
        {
            return "<div class='error-box'><span class='box-icon'></span>Tutti i campi devono essere completati</div>".
                as_hiddenFields($t, $nome, $cognome, $codice_fiscale, $email, $affiliazione, $citta, $lat, $lng, $prova_pagamento, $cip2010, $cip2010_desc,
                    $professione, $cv, $candidatura, $vcode, "Torna indietro");
        }
        
        $txt = "<div style='line-height:2em'>Prima di terminare l'iscrizione ad AIRIcerca come socio, conferma che tutti i dati inseriti siano corretti <br />
			<form action='".get_site_url()."/?page_id=".$page_ID."&ps=3' method='POST'>
			<strong>Nome:</strong> $nome<input type='hidden' name='nome' value=\"$nome\" /><br />
			<strong>Cognome:</strong> $cognome<input type='hidden' name='cognome' value=\"$cognome\" /><br />
			<strong>Codice fiscale:</strong> $codice_fiscale<input type='hidden' name='codice_fiscale' value='$codice_fiscale' /></br>
			<strong>e-mail:</strong> $email<input type='hidden' name='email' value='$email' /><br />
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
			<div style='text-align: center'><input type='submit' value='Procedi con l&#39;aggiornamento' /></div>
			</form>";
    }
    else
    {
        return "<div class='error-box'><span class='box-icon'></span>Errore interno</div>".
            as_hiddenFields($t, $nome, $cognome, $codice_fiscale, $email, $affiliazione, $citta, $lat, $lng, $prova_pagamento, $cip2010, $cip2010_desc,
                $professione, $cv, $candidatura, $vcode, "Torna indietro");
    }
    
    $txt .= as_hiddenFields($t, $nome, $cognome, $codice_fiscale, $email, $affiliazione, $citta, $lat, $lng, $prova_pagamento, $cip2010, $cip2010_desc,
        $professione, $cv, $candidatura, $vcode, "Torna indietro e modifica i dati");
    
    return $txt;
}

function as_modify_user() {
    global $wpdb;
    
    $user_id = get_current_user_id();
    $page_ID = get_the_ID();
        
    $nome = get_user_meta($user_id, "first_name", true);
    $cognome = get_user_meta($user_id, "last_name", true);
    
    $dp_email = $wpdb->get_results("SELECT user_email FROM `wp_users` WHERE `ID` = $user_id")[0]->user_email;
    
    $dati_personali = get_user_meta($user_id, "dati_personali", true);
    
    $codice_fiscale    = $dati_personali['codice_fiscale'];
    $dp_citta          = $dati_personali['citta'];
    $dp_cip2010        = $dati_personali['cip2010'];
    $dp_2010_desc      = $dati_personali['cip2010_desc'];
    $dp_professione    = $dati_personali['professione'];
    $dp_affiliazione   = $dati_personali['affiliazione'];
    $dp_cv             = $dati_personali['cv'];
    $dp_lat            = $dati_personali['lat'];
    $dp_lng            = $dati_personali['lng'];
    $dp_candidatura    = $dati_personali['candidatura'];
    $dp_t              = $dati_personali['tipo_utente'];
    
    $dp_revisore = get_user_meta($user_id, "revisoreGrant", true) == 1;
    
    $_POST = stripslashes_deep($_POST);
    
    $dp_new = $dati_personali;
    
    $dp_new['affiliazione'] =  isset($_POST['affiliazione']) ? $_POST['affiliazione'] : $dp_affiliazione;
    $dp_new['citta'] =  isset($_POST['citta']) ? $_POST['citta'] : $dp_citta;
    $dp_new['cip2010'] =  isset($_POST['cip2010']) ? $_POST['cip2010'] : $dp_cip2010;
    $dp_new['cip2010_desc'] =  isset($_POST['cip2010_desc']) ? $_POST['cip2010_desc'] : $dp_2010_desc;
    $dp_new['professione'] =  isset($_POST['professione']) ? $_POST['professione'] : $dp_professione;
    $dp_new['tipo_utente'] =  isset($_POST['t']) ? (int)$_POST['t'] : $dp_t;
    $dp_new['cv'] =  isset($_POST['cv']) ? substr($_POST['cv'], 0, 1000) : $dp_cv ;
    $dp_new['candidatura'] =  isset($_POST['candidatura']) ? substr($_POST['candidatura'], 0, 1000) : $dp_candidatura ;
    $dp_new['lat'] =  isset($_POST['lat']) ? (float)$_POST['lat'] : $dp_lat;
    $dp_new['lng'] =  isset($_POST['lng']) ? (float)$_POST['lng'] : $dp_lng ;
    
    $txt = '<div><strong>Aggiornamento effettuato.</strong></div>';
    
    if ( $dp_new != $dati_personali) {
    
        $res = update_user_meta($user_id, "dati_personali", $dp_new, $dati_personali);
        
        if ( $res != true) {
            $txt .= "<div><strong>Errore interno update_user_meta</strong></div>";
        } else {
            $txt .= "<div><strong>Dati personali aggiornati.</strong></div>";
        }
    }
    
    $email = isset($_POST['email']) ? $_POST['email'] : $dp_email;
    
    if ($email != $dp_email) {
        
        $res = $wpdb->update('wp_users', array('user_email' => $email), array('ID' => $user_id));
        
        if ( $res != true) {
            $txt .= "<div><strong>Errore interno update email</strong></div>";
        } else { 
            $txt .= "<div><strong>Email aggiornato.</strong></div>";
        }
        
        // Aggiungiamo l'utente alla lista di MailChimp
        // Non fare niente se si tratta di un test
        if (strpos(get_site_url(), 'localhost') !== false) {
            $txt .= "<BR /><BR /><BR /> Mailchimp list=".$listID." key=".$apikey;
        } else {
            as_add_mailchimp($dp_new['tipo_utente'], $nome, $cognome, $email);
        }
    }
    
    $vcode = isset($_POST['vcode']) ? (int)$_POST['vcode'] : 0;
    $revisore = as_check_volunteer($vcode, REVIEWER_BIT);
    
    if ($revisore != $dp_revisore) {
        $res = update_user_meta($user_id, "revisoreGrant", $revisore? 1 : 0, $dp_revisore);
        
        if ( $res != true) {
            $txt .= "<div><strong>Errore interno update revisore</strong></div>";
        } else {
            $txt .= "<div><strong>Revisore aggiornato.</strong></div>";
        }
    }
    
    
    return $txt;
    
}


add_shortcode('AIRIsoci-mostra-profilo', 'as_dispatcher_modifica');
?>