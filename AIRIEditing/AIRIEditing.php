<?php
/**
 * Plugin Name: AIRIEditing
 * Plugin URI: https://github.com/AIRIOpenLab/AIRIplugin
 * Description: Shortcodes per AIRIEditing.
 * Version: 1.0.1
 * Author: Nicola Romanò
 * Author URI: https://github.com/nicolaromano
 * License: GPL3
 */
 
/*  Copyright 2019 Nicola Romanò (romano.nicola@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 3, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301 USA
*/

// base per inclustione file di testo
$PLUGIN_BASE = $_SERVER['DOCUMENT_ROOT']."/wp-content/plugins/AIRIEditing/";

// Loading condizionale di Javascript
function ae_load_custom_scripts() 
	{
	$page_ID = get_the_ID();
	//echo $page_ID;
	if ($page_ID == 12275) // Pagina contatti AIRIEditing
		{
		wp_enqueue_script('reCAPTCHA', 'https://www.google.com/recaptcha/api.js?hl=it', array(), '1', true);
		}
	if ($page_ID == 12324) // Pagina contatti AIRIEditing/EN
		{
		wp_enqueue_script('reCAPTCHA', 'https://www.google.com/recaptcha/api.js?hl=en', array(), '1', true);
		}
	}

add_action('wp_enqueue_scripts', 'ae_load_custom_scripts');

$editors = array("Susanna Grazzini", "Sivia Licciulli", "Anna Napolitano", "Valentina Petrolini", "Giulia Petrovich",
                 "Giovanna Piazzese", "Hartmut Pohl", "Mariano Maffei", "Carlo Rindi Nuzzolo", "Stgilesmedical");
$editorslugs = array("susannagrazzini", "silvialicciulli", "annanapolitano", "valentinapetrolini", "giuliapetrovich",
                     "giovannapiazzese", "hartmutpohl", "marianomaffei", "carlorindinuzzolo", "stgilesmedical");
$editoremails = array("susanna.grazzini@gmail.com", "slicciulli@gmail.com", "anna-napolitano@hotmail.it", "hegel.eva@gmail.com",
                      "contact@editando-services.com", "emailpiazzese@gmail.com", "Hartmut.Pohl@8knut.de", "mariano.maffei@embl.it",
                      "rindi.carlo@gmail.com", "steven.walker@stgmed.com");

/******************* SHORTCODES *******************/
// [AIRIEditing-contact-form]
function ae_contact()
	{
        global $editors, $editorslugs, $editoremails;

        if (isset($_POST['firstname']))
           {
           $res = ae_sendmsg();
           if ($res == 1)
              {
              $id_editor = array_search($_POST["editor"], $editorslugs);

              $txt = "<div class='info-box'>Messaggio inviato con successo a ".$editors[$id_editor]."</div><div><a class='button' href='/airiediting/'>Indietro</a></div>";
              }
           else if ($res == -1)
              {
              $txt = "<div class='error-box'><span class='box-icon'></span>Devi confermare di non essere un robot</div><div><a class='button' href='/airiediting/contact/'>Indietro</a></div>";
              }
           else
              {
              $txt = "<div class='error-box'><span class='box-icon'></span>C'&egrave; stato un errore. Assicurati di aver riempito correttamente il form. </div><div><a class='button' href='/airiediting/contact/'>Indietro</a></div>";
              }
           return $txt;
           }

	$editor = isset($_GET['editor']) ? sanitize_title($_GET['editor']) : "";
        $txt = "";
        if (!is_user_logged_in())
           {
           $txt .= '<div class="info-box">Importante! Se sei socio di AIRIcerca, ricordati di <a href="http://www.airicerca.org/wp-login.php?redirect_to=/airiediting/contact">effettuare il login</a> prima di inviarci la richiesta per usufruire degli sconti riservati ai soci. Non sei socio? <a href = "/iscrizione/diventa-socio/" target="_blank">Iscriviti subito!</a></div>';
           }

        $txt .= '<div class="container"><form action="." method="POST">
                <table style="width:80%; text-align:center; margin:auto">
                <tbody>
                <tr>
                <td width="40%"><label for="firstname">Nome:</label></td>
                <td><input id="firstname" name="firstname" type="text" required="required" maxlength="50" size="50"></td>
                </tr>
                <tr>
                <td><label for="surname">Cognome:</label></td>
                <td><input id="surname" name="surname" type="text" required="required" maxlength="50" size="50"></td>
                </tr>
                <tr>
                <td><label for="email">Indirizzo email:</label></td>
                <td><input id="email" name="email" type="text" required="required" maxlength="50" size="50"></td>
                </tr>
                <tr>
                <td><label for="service">Tipo di servizio:</label></td>
                <td><select id="service" name="service">
                <option value="lang">Editing linguistico</option>
                <option value="substantial">Editing sostanziale</option>
                </select></td>
                </tr>
                <tr>
                <td><label for="doctype">Tipo di documento (tesi, articolo, lettera, ...): </label></td>
                <td><input id="doctype" name="doctype" type="text" maxlength="50" size="50" required="required"></td>
                </tr>
                <tr>
                <td><label for="numword">Numero di parole approssimativo: </label></td>
                <td><input id="numword" name="numword" type="number" min="0" max="1000000" value="3000" size="10"></td>
                </tr>
                <tr>
                <td><label for="format">Formato del file: </label></td>
                <td><input id="format" name="format" type="text" size = "50"></td>
                </tr>
                <tr>
                <td><label for="field">Campo accademico: </label></td>
                <td><input id="field" name="field" type="text" maxlength="50" size="50"></td>
                </tr>
                <tr>
                <td><label for="deadline">Data di consegna: </label></td>
                <td><input id="deadline" name="deadline" type="date" required="required"></td>
                </tr>
                <tr>
                <td><label for="submission">Quando pensi di inviarci il documento per la revisione? </label></td>
                <td><input id="submission" name="submission" type="date" required="required"></td>
                </tr>
                <tr>
                <td><label for="journal">Se stai mandando un articolo, a quale giornale pensi di inviarlo? </label></td>
                <td><input id="journal" name="journal" type="text" maxlength="100" size="50"></td>
                </tr>
                <tr>
                <td><label for="language">Inglese britannico o americano?</label></td>
                <td><select id="language" name="language">
                <option value="enuk">Inglese britannico</option>
                <option value="enus">Inglese americano</option>
                </select></td>
                </tr>
                <tr>
                <td><label for="editor">Che editor vuoi contattare?</label></td>
                <td><select id="editor" name="editor">';

        for ($i=0; $i<count($editors); $i++)
            {
            if (!strcmp($editor, $editorslugs[$i]))
               $sel = 'selected = "selected"';
            else
               $sel = '';
            $txt .= '<option value="'.$editorslugs[$i].'" '.$sel.'>'.$editors[$i].'</option>';
            }
                  
        $txt .= '</select></td>
                </tr>
                <tr>
                <td>Altre note</td>
                <td><textarea cols="50" rows="10" id="notes" name="notes"></textarea></td>
                </tr>
                <tr>
                <td colspan="2">
                <div class="g-recaptcha" align = "center" style="margin:auto" data-sitekey="6LfXiAMTAAAAAPjD7lpymcqLACCehoJguTiWeDzu"></div>
                </td>
                </tr>
                </tbody>
                </table>
                <div align = "center" style="margin-top:2em"><input type="submit" value="Invia"></div>
                </form></div>';

        $txt .= "<div class='verysmall' style='margin-top:2em'>L'invio di questo modulo di contatto e l'accesso a qualsiasi area del servizio di AIRIEditing implica l'accettazione dei <a href='/airiediting/termini/' target='_blank'>termini d'uso e linee guida</a> di AIRIEditing. Se non sei d'accordo, ti vietiamo l'utilizzo o l'accesso a qualsiasi nostro servizio.

Gli editor di AIRIEditing raccolgono solamente le informazioni che tu fornisci loro direttamente completando questo modulo. Per favore nota che l'editor che contatti potrebbe condividere i tuoi dati con gli altri editor di AIRIEditing con il solo fine di monitorare l'uso del servizio.

Gli editor assicurano di aver messo in atto misure di sicurezza per la salvaguardia e sicurezza dei dati personali. Le informazioni che fornisci attraverso questo modulo di contatto non verranno conservate sui server di AIRIcerca.</div>";
	
 	return $txt;
 	}

// [AIRIEditing-contact-form-en]
function ae_contact_en()
	{
        global $editors, $editorslugs, $editoremails;

        if (isset($_POST['firstname']))
           {
           $res = ae_sendmsg();
           if ($res == 1)
              {
              $id_editor = array_search($_POST["editor"], $editorslugs);

              $txt = "<div class='info-box'>Succesfully contacted ".$editors[$id_editor]."</div><div><a class='button' href='/airiediting/'>Back</a></div>";
              }
           else if ($res == -1)
              {
              $txt = "<div class='error-box'><span class='box-icon'></span>You need to confirm you are not a bot!</div><div><a class='button' href='/airiediting/contact/'>Back</a></div>";
              }
           else
              {
              $txt = "<div class='error-box'><span class='box-icon'></span>There was an error. Make sure to correctly fill the form.</div><div><a class='button' href='/airiediting/contact/'>Back</a></div>";
              }
           return $txt;
           }

	$editor = isset($_GET['editor']) ? sanitize_title($_GET['editor']) : "";
 
        $txt = "";
        if (!is_user_logged_in())
           {
           $txt .= '<div class="info-box">Are you an AIRIcerca member? Remember to <a href="http://www.airicerca.org/wp-login.php?redirect_to=/airiediting/en/contact">log in</a> before sending your request to take advantage of the membership discounts. Not a member? <a href = "/iscrizione/diventa-socio/" target="_blank">Sign up now!</a></div>';
           }

        $txt .= '<div class="container"><form action="." method="POST">
                <table style="width:80%; text-align:center; margin:auto">
                <tbody>
                <tr>
                <td width="40%"><label for="firstname">First name</label></td>
                <td><input id="firstname" name="firstname" type="text" required="required" maxlength="50" size="50"></td>
                </tr>
                <tr>
                <td><label for="surname">Surname</label></td>
                <td><input id="surname" name="surname" type="text" required="required" maxlength="50" size="50"></td>
                </tr>
                <tr>
                <td><label for="email">email address</label></td>
                <td><input id="email" name="email" type="text" required="required" maxlength="50" size="50"></td>
                </tr>
                <tr>
                <td><label for="service">Service</label></td>
                <td><select id="service" name="service">
                <option value="lang">Language editing</option>
                <option value="substantial">Substantial editing</option>
                </select></td>
                </tr>
                <tr>
                <td><label for="doctype">Type of document (dissertation, paper, letter, ...) </label></td>
                <td><input id="doctype" name="doctype" type="text" maxlength="50" size="50" required="required"></td>
                </tr>
                <tr>
                <td><label for="numword">Approximate number of words </label></td>
                <td><input id="numword" name="numword" type="number" min="0" max="1000000" value="3000" size="10"></td>
                </tr>
                <tr>
                <td><label for="format">File format</label></td>
                <td><input id="format" name="format" type="text" size = "50"></td>
                </tr>
                <tr>
                <td><label for="field">Academic field</label></td>
                <td><input id="field" name="field" type="text" maxlength="50" size="50"></td>
                </tr>
                <tr>
                <td><label for="deadline">Deadline</label></td>
                <td><input id="deadline" name="deadline" type="date"></td>
                </tr>
                <tr>
                <td><label for="submission">When do you plan to submit your work for editing?</label></td>
                <td><input id="submission" name="submission" type="date"></td>
                </tr>
                <tr>
                <td><label for="journal">If you are sending an article, which journal are you planning to submit to?</label></td>
                <td><input id="journal" name="journal" type="text" maxlength="100" size="50"></td>
                </tr>
                <tr>
                <td><label for="language">British or American English?</label></td>
                <td><select id="language" name="language">
                <option value="enuk">British English</option>
                <option value="enus">American English</option>
                </select></td>
                </tr>
                <tr>
                <td><label for="editor">Which of the editors would you like to contact?</label></td>
                <td><select id="editor" name="editor">';

        for ($i=0; $i<count($editors); $i++)
            {
            if (!strcmp($editor, $editorslugs[$i]))
               $sel = 'selected = "selected"';
            else
               $sel = '';
            $txt .= '<option value="'.$editorslugs[$i].'" '.$sel.'>'.$editors[$i].'</option>';
            }
                  
        $txt .= '</select></td>
                </tr>
                <tr>
                <td>Other comments</td>
                <td><textarea cols="50" rows="10" id="notes" name="notes"></textarea></td>
                </tr>
                <tr>
                <td colspan="2">
                <div class="g-recaptcha" align="center" data-sitekey="6LfXiAMTAAAAAPjD7lpymcqLACCehoJguTiWeDzu"></div>
                </td>
                </tr>
                </tbody>
                </table>
                <div align = "center" style="margin-top:2em"><input type="submit" value="Submit"></div>
                </form></div>';

                $txt .= "<div class='verysmall' style='margin-top:2em'>Please note that by submitting this contact form and by accessing any area of the AIRIEditing service, you agree to the <a href='http://www.airicerca.org/airiediting/en/terms/' target='_blank'>guidelines, terms and conditions</a> of the service. If you do not agree, you should immediately cease using any of our services. 

The editors of the AIRIEditing team only collect the information you directly provide them by filling in this contact form. Please note that the editor you contact may share your data with other editors of the team for the sole purpose of monitoring the use of the AIRIEditing service. 

The editors have put in place security measures to safeguard and secure your personal data. The information you provide in the contact form will not be stored on the servers of AIRIcerca. 
</div>";	
 	return $txt;
 	}

add_shortcode('AIRIEditing-contact-form', 'ae_contact');
add_shortcode('AIRIEditing-contact-form-en', 'ae_contact_en');

function ae_sendmsg()
{
    global $editors, $editorslugs, $editoremails, $PLUGIN_BASE;

	$recaptcha = $_POST['g-recaptcha-response'];
	if (!empty($recaptcha))
		{
		$google_url="https://www.google.com/recaptcha/api/siteverify";
		$f = fopen($PLUGIN_BASE."/recaptcha.txt", "r");
		$secret = trim(fgets($f));
		fclose($f);
		$ip = $_SERVER['REMOTE_ADDR'];
		$url = $google_url."?secret=".$secret."&response=".$recaptcha."&remoteip=".$ip;
			
		$res = getCurlData($url);
		$res = json_decode($res, true);

		if (!$res['success'])
			{
		        return -1;
			}
		}

        if (!isset($_POST["firstname"]) ||
            !isset($_POST["surname"]) ||
            !isset($_POST["email"]) ||
            !isset($_POST["service"]) ||
            !isset($_POST["doctype"]) ||
            !isset($_POST["numword"]) ||
            !isset($_POST["format"]) ||
            !isset($_POST["field"]) ||
            !isset($_POST["deadline"]) ||
            !isset($_POST["submission"]) ||
            !isset($_POST["language"]) ||
            !isset($_POST["journal"]) ||
            !isset($_POST["editor"]) ||
            !isset($_POST["notes"]))
        return 0;

        $name = sanitize_text_field($_POST["firstname"]);
        $surname = sanitize_text_field($_POST["surname"]);
        $user_email = sanitize_text_field($_POST["email"]);
        $service = ($_POST["service"] == "lang") ? "Language editing" : "Substantial editing";
        $doctype = sanitize_text_field($_POST["doctype"]);
        $numwords = ((int)($_POST["numword"]) == 0) ? "Not specified" : (int)$_POST["numword"];
        $format = ($_POST["format"] == "") ? "Not specified" : sanitize_text_field($_POST["format"]);
        $field = ($_POST["field"] == "") ? "Not specified" : sanitize_text_field($_POST["field"]);
        $deadline = ($_POST["deadline"] == "") ? "Not specified" : sanitize_text_field($_POST["deadline"]);
        $submission = ($_POST["submission"] == "") ? "Not specified" : sanitize_text_field($_POST["submission"]);
        $lang = ($_POST["language"] == "enuk") ? "UK English" : (($_POST["language"] == "enus") ? "US English" : "Not specified");
        $journal = sanitize_text_field($_POST["journal"]);
        $editor = sanitize_text_field($_POST["editor"]);
        $notes = ($_POST["notes"] == "") ? "None": sanitize_textarea_field($_POST["notes"]);

        // Check all necessary fields are set
        if ($name == "" || $surname == "" || $user_email == "" || $doctype == "" || $editor == "")
            return 0;


	// Mail all'editor
        if (array_search($editor, $editorslugs) === FALSE)
            return 0;

        if (is_user_logged_in())
            $ismember = "Yes";
        else
            $ismember = "No";

	$to = $editoremails[array_search($editor, $editorslugs)];
	$subject = "AIRIEditing - Editing request";
	$body = "<div style='text-align:center'><img src='http://www.airicerca.org/wp-content/uploads/2014/03/airi-logo-256x62.png' /></div><br />
                Dear ".$editors[array_search($editor, $editorslugs)].",
                
                $name $surname contacted you through AIRIediting!<br />
                Following are the details of the request:
                <table width = '80%'>

                <tr>
                <td width = '50%'><b>Name:</b></td>
                <td>$name</td>
                </tr>          

                <tr>
                <td width = '50%'><b>Surname:</b></td>
                <td>$surname</td>
                </tr>

                <tr>
                <td width = '50%'><b>Email address:</b></td>
                <td>$user_email</td>
                </tr>

                <tr>
                <td width = '50%'><b>Member of AIRIcerca?</b></td>
                <td>$ismember</td>
                </tr>

                <tr>
                <td width = '50%'><b>Type of service and document:</b></td>
                <td>$service of: $doctype</td>
                </tr>

                <tr>
                <td width = '50%'><b>File format:</b></td>
                <td>$format</td>
                </tr>

                <tr>
                <td width = '50%'><b>Number of words:</b></td>
                <td>$numwords</td>
                </tr>

                <tr>
                <td width = '50%'><b>Academic field:</b></td>
                <td>$field</td>
                </tr>

                <tr>
                <td width = '50%'><b>Deadline:</b></td>
                <td>$deadline (files will be sent before: $submission)</td>
                </tr>

                <tr>
                <td width = '50%'><b>Document will be sent to this journal:</b></td>
                <td>$journal</td>
                </tr>

                <tr>
                <td width = '50%'><b>Language:</b></td>
                <td>$lang</td>
                </tr>

                <tr>
                <td width = '50%'><b>Notes:</b></td>
                <td>$notes</td>
                </tr>

                </table>";

	$headers[] = "From: ".$name." ".$surname." via AIRIediting <".$user_email.">";
	$headers[] = "Reply-To: ".$user_email;
	$headers[] = "Content-Type: text/html";
	$headers[] = "charset=UTF-8";
	$headers[] = "MIME-Version: 1.0";
	$headers[] = "X-Mailer: PHP/".phpversion();

	wp_mail($to, $subject, $body, $headers);
      
        return 1;
        }
?>