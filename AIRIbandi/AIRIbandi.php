<?php
/**
 * Plugin Name: AIRIbandi
 * Description: Plugin per la gestione della sezione bandi.
 * Version: 0.1.0
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
function AIRIbandi_load_custom_scripts() 
	{
	$page_ID = get_the_ID();
	//echo $page_ID;
	if ($page_ID == 6028) // Pagina AIRIbandi
		{
		wp_enqueue_style('DataTablesCSS', 'http://cdn.datatables.net/1.10.7/css/jquery.dataTables.css');
		wp_enqueue_script('DataTables', 'http://cdn.datatables.net/1.10.7/js/jquery.dataTables.js', array("jquery"), '1', true);
		wp_enqueue_script('initDataTable', plugins_url('AIRIbandi.js', __FILE__), array("jquery"), '1', true);
		wp_enqueue_style('AIRIbandiCSS', plugins_url('AIRIbandi.css', __FILE__ ));

		// Vedi http://www.koolkatwebdesigns.com/using-jquery-datatables-with-wordpress-and-ajax/
		wp_localize_script('DataTables', 'DataTablesLoadData', array('ajaxURL' => admin_url('admin-ajax.php')));
		}
	else if ($page_ID == 6439 || $page_ID == 6698) // Pagina nuovo bando
		{
		wp_enqueue_style('AIRIbandiCSS', plugins_url('AIRIbandi.css', __FILE__ ));
		wp_enqueue_script('countrySelector', plugins_url('jquery.select-to-autocomplete.js', __FILE__ ), array("jquery", "jquery-ui-autocomplete"), '1', true);
		wp_enqueue_script('initPage', plugins_url('AIRIbandi-insert.js', __FILE__), array("jquery"), '1', true);
		wp_localize_script('initPage', 'scriptVars', array('flagsURL' => content_url()."/media/flags/"));
		}
	}

add_action('wp_enqueue_scripts', 'AIRIbandi_load_custom_scripts');

add_action('wp_ajax_getBandi', 'getBandiAJAX');
add_action('wp_ajax_nopriv_getBandi', 'getBandiAJAX');

add_action('wp_ajax_act_getPaesi', 'getPaesiAJAX');
add_action('wp_ajax_nopriv_act_getPaesi', 'getPaesiAJAX');

/******************* SHORTCODES *******************/
function getPaesiAJAX()
	{
	global $wpdb;

	if (isset($_POST['all']) && $_POST['all'] == 1)
		{
		$res = $wpdb->get_results("SELECT id, paese, codice, weight, nomi_alternativi FROM wp_bandi_nazioni ORDER BY paese ASC");
		}
	else
		{
		$res = $wpdb->get_results("SELECT DISTINCT n.id, n.paese, n.codice, n.weight, n.nomi_alternativi FROM wp_bandi b
			LEFT JOIN wp_bandi2nazioni b2n ON b.id = b2n.id_bando 
			LEFT JOIN wp_bandi_nazioni n ON b2n.id_nazione = n.id 
			WHERE DATEDIFF(NOW(), b.data_chiusura) < 7 AND n.paese IS NOT NULL");
		}
		
	$paesi = array();
	$altern = array();
	$codici = array();
	$pesi = array();
	
	foreach($res as $paese)
		{
		$ids[] = $paese->id;
		$paesi[] = $paese->paese;
		$altern[] = $paese->codice." ".$paese->nomi_alternativi;
		$codici[] = strtolower($paese->codice);
		$pesi[] = $paese->weight;
		}
	
	echo json_encode(array("paesi" => $paesi, "alternative" => $altern, "pesi" => $pesi, "codici" => $codici, "ids" => $ids));
	
	wp_die();
	}
	
function getBandiAJAX()
	{
	global $wpdb;
	
	/* Nomi delle colonne PER IL SORTING */
	$columns = array(
		"nome",
		"ente",
		"destinatari",
		"data_chiusura",
		"data_apertura",
		"data_chiusura",
		"paese"
		);
	
	$showall = (int)$_POST['sa'];
	$paese = (int)$_POST['country'];
	$dest = (int)$_POST['dest'];

	$searchFilter = trim($_POST["search"]);

	if ($showall == 0)
		{
		$timeClause = "DATEDIFF(NOW(), b.data_chiusura) < 7 ";
		}
	else
		{
		$timeClause = "1 ";
		}

	if ($paese != -1)
		{
		$paeseClause = "AND b2n.id_nazione = %d ";
		}
	else
		{
		// Se il paese non è settato sarà -1 e quindi id_nazione è sempre > -1
		$paeseClause = "AND (b2n.id_nazione > %d OR b2n.id_nazione IS NULL) ";
		}

	if ($dest != -1)
		{
		$destClause = "AND bd.destinatario = %d ";
		}
	else
		{
		// Se il destinatario non è settato sarà -1 e quindi destinatario è sempre > -1
		$destClause = "AND (bd.destinatario > %d OR bd.destinatario IS NULL) ";
		}
		
	$filterClause = "$timeClause AND (b.nome LIKE '%%%s%%' OR b.descrizione LIKE '%%%s%%' OR b.ente LIKE '%%%s%%') $paeseClause $destClause ";

	$orderClause = "ORDER BY ".$columns[(int)$_POST["sorting"][0]]." ".$_POST["sorting"][1];
	
	$queryIDs = "SELECT SQL_CALC_FOUND_ROWS b.id ID 
		FROM wp_bandi b 
		LEFT JOIN wp_bandi2nazioni b2n ON b.id = b2n.id_bando 
		LEFT JOIN wp_bandi_destinatari bd ON b.id = bd.id_bando, 
		wp_bandi_nazioni bn WHERE b2n.id_nazione = bn.id AND $filterClause 
		GROUP BY b.id $orderClause LIMIT %d, %d";
		
	$res = $wpdb->get_results($wpdb->prepare($queryIDs, $searchFilter, $searchFilter, $searchFilter, $paese, $dest, (int)$_POST["start"], (int)$_POST["length"]));
	$totres = $wpdb->get_row("SELECT FOUND_ROWS() total");

	$ids = array();
	foreach($res as $rowID)
		{
		$ids[] = $rowID->ID;
		}
	$total = $totres->total;
	$ids = implode(",", $ids);
		
	$query = "SELECT b.id, b.nome, b.url, b.ente, b.data_apertura, b.data_chiusura, b.descrizione,
		IF(b.data_apertura >= NOW(), 0, 1) aperto, IF(b.data_chiusura > NOW(), 0, 1) chiuso, 
		GROUP_CONCAT(DISTINCT bn.codice ORDER BY bn.weight DESC, bn.codice ASC SEPARATOR ',') codice_paese, 
		GROUP_CONCAT(DISTINCT bn.paese ORDER BY bn.weight DESC, bn.codice ASC SEPARATOR ',') paese, 
		GROUP_CONCAT(DISTINCT bd.destinatario ORDER BY bd.destinatario ASC SEPARATOR ', ') destinatari 
		FROM wp_bandi b
		LEFT JOIN wp_bandi2nazioni b2n ON b.id = b2n.id_bando 
		LEFT JOIN wp_bandi_destinatari bd ON b.id = bd.id_bando, 
		wp_bandi_nazioni bn WHERE b2n.id_nazione = bn.id AND b.id IN ($ids) 
		GROUP BY b.id $orderClause";
		
	$res = $wpdb->get_results($query);
	
	$bandi = array();
	$bandiAdmin = get_usermeta(get_current_user_id(), "bandiAdmin", TRUE);
	
	foreach($res as $bando)
		{
		$paesi = explode(",", $bando->paese);
		$codici = explode(",", $bando->codice_paese);
		$flags = "";

		for ($p=0; $p<count($paesi); $p++)
			{
			if ($paesi[$p] != "")
				$flags .= "<img src='".content_url()."/media/flags/".strtolower($codici[$p]).".png' title='".$paesi[$p]."' alt='".$paesi[$p]."' />";
			}

		if ($bandiAdmin != "")
			{
			$tools = "<a href = '../modifica-bando?id=".$bando->id."'><img src='".content_url()."/media/modifica.png' title='Modifica bando' alt='Modifica bando' /></a>".
				 "<a href = '../cancella-bando?id=".$bando->id."'><img src='".content_url()."/media/cancella.png' title='Cancella bando' alt='Cancella bando' /></a>";
			}
		else
			{
			$tools = "";
			}
			
		if ($bando->chiuso)
			{
			$apertura = "<strong style='color:red'>SCADUTO</strong>";
			}
		else if ($bando->aperto)
			{
			$apertura = "Scade il ".$bando->data_chiusura;
			}
		else
			{
			$apertura = "Apre il ".$bando->data_apertura;
			}
			
		if (trim($searchFilter) !== "")
			{
			$bando->nome = preg_replace("/($searchFilter)/i", "<strong>\\1</strong>", $bando->nome);
			$bando->ente = preg_replace("/($searchFilter)/i", "<strong>\\1</strong>", $bando->ente);
			$bando->descrizione = preg_replace("/($searchFilter)/i", "<strong>\\1</strong>", $bando->descrizione);
			}

		$tmp = array(
			"<a href='".$bando->url."' rel='nofollow' target='_blank'>".$bando->nome."</a><br /><span class='font-small'>".$bando->descrizione."</span>",
			$bando->ente,
			$bando->destinatari,
			$apertura,
			$bando->data_apertura,
			$bando->data_chiusura,
			$flags,
			$tools);
			
		$bandi[] = $tmp;
		}
		
	echo json_encode(
		array(
			draw => (int)$_POST['draw'],
			recordsTotal => $total, 
			recordsFiltered => $total,
			data => $bandi,
			showall => $_POST['sa'],
			ord => $orderClause,
			sql => $queryIDs
			)
		);
	wp_die();
	}
	
//[tabella-bandi]
function print_tabella_bandi()
	{
	global $wpdb;
	
	/*
	Colonne: Nome, Ente, Destinatari, Data di apertura, Data di apertura (per sort), Data di chiusura (per sort), Paese
	*/
	
	$txt = "";
	
	$bandiAdmin = get_usermeta(get_current_user_id(), "bandiAdmin", TRUE);
	if ($bandiAdmin != "")
		$txt .= "<input id='showall' name='showall' type='checkbox' /><label for='showall'>Mostra bandi scaduti</label>";
	
	$txt .= "<table id='tabella-bandi'>
		<thead>
		<tr>
		<th>Nome</th>
		<th>Ente</th>
		<th>Destinatari</th>
		<th>Apertura</th>
		<th></th>
		<th></th>
		<th></th>
		<th></th>
		</tr>
		</thead>
		</table>";
	
	return $txt;
	}
add_shortcode('tabella-bandi', 'print_tabella_bandi');

function add_bando()
	{
	if (isset($_POST['bando_modifica']))
		{
		modifica_bando();
		return;
		}
		
	global $wpdb;
	
	$_POST = array_map('stripslashes_deep', $_POST);
	$res = $wpdb->insert('wp_bandi', 
		array('nome' => $_POST['bando_titolo'], 'ente' => $_POST['bando_ente'], 'url' => $_POST['bando_URL'], 'descrizione' => $_POST['bando_descrizione'], 
			'data_apertura' => $_POST['bando_data_apertura'], 'data_chiusura' => $_POST['bando_data_chiusura'], 'autore' => get_current_user_id(),
			'ultima_modifica' => date('Y/m/d H:i:s')),
		array('%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s'));
	
	$id_bando = $wpdb->insert_id;

	if (isset($_POST['bando_id_paesi']))
		{
		if (!count($_POST['bando_id_paesi']))
			$_POST['bando_id_paesi'] = array(248); // Nessuna restrizione di Paese

		foreach($_POST['bando_id_paesi'] as $p)
			{
			$wpdb->insert('wp_bandi2nazioni', 
				array('id_bando' => $id_bando, 'id_nazione' => $p),
				array('%d', '%d'));
			}
		}
	else
		{
		// Tutto il mondo
		$wpdb->insert('wp_bandi2nazioni', 
			array('id_bando' => $id_bando, 'id_nazione' => 248),
			array('%d', '%d'));
		}
	
	if (isset($_POST['bando_recipients']))
		{
		foreach($_POST['bando_recipients'] as $d)
			{
			$wpdb->insert('wp_bandi_destinatari', 
				array('id_bando' => $id_bando, 'destinatario' => $d),
				array('%d', '%d'));
			}
		}

	echo "Il bando è stato inserito nel database. Grazie per il tuo contributo!<br /><a href='../'>Torna alla lista dei bandi</a>";
	}

function modifica_bando()
	{
	global $wpdb;

	$_POST = array_map('stripslashes_deep', $_POST);

	$_POST['bando_titolo'] = substr($_POST['bando_titolo'], 0, 150);
	$_POST['bando_ente'] = substr($_POST['bando_ente'], 0, 150);
	$_POST['bando_url'] = substr($_POST['bando_url'], 0, 300);
	$_POST['bando_descrizione'] = substr($_POST['bando_descrizione'], 0, 300);
	$id_bando = $_POST['bando_ID'];
	
	$wpdb->update('wp_bandi', 
		array('nome' => $_POST['bando_titolo'], 
			'ente' => $_POST['bando_ente'], 
			'url' => $_POST['bando_URL'], 
			'descrizione' => $_POST['bando_descrizione'], 
			'data_apertura' => $_POST['bando_data_apertura'], 
			'data_chiusura' => $_POST['bando_data_chiusura'], 
			'autore' => get_current_user_id(),
			'ultima_modifica' => date('Y/m/d H:i:s')),
		array('id' => $id_bando),
		array('%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s'),
		array('%d'));

	$wpdb->query($wpdb->prepare("DELETE FROM wp_bandi2nazioni WHERE id_bando = %d", $id_bando));
	$wpdb->query($wpdb->prepare("DELETE FROM wp_bandi_destinatari WHERE id_bando = %d", $id_bando));
	
	if (isset($_POST['bando_id_paesi']))
		{
		foreach($_POST['bando_id_paesi'] as $p)
			{
			$wpdb->insert('wp_bandi2nazioni', 
				array('id_bando' => $id_bando, 'id_nazione' => $p),
				array('%d', '%d'));
			}
		}
	
	if (isset($_POST['bando_recipients']))
		{
		foreach($_POST['bando_recipients'] as $d)
			{
			$wpdb->insert('wp_bandi_destinatari', 
				array('id_bando' => $id_bando, 'destinatario' => $d),
				array('%d', '%d'));
			}
		}

	echo "Il bando è stato modificato. Grazie per il tuo contributo!<br /><a href='../'>Torna alla lista dei bandi</a>";
	}

add_shortcode('inserimento-bando', 'add_bando');

function crea_form()
	{
	$txt = "";

	$txt .= "<form action='../inserisci-bando/?noheader=true' method='POST' style='font-size=1.2em'>

		<label style='margin-top:0.4em;' for='bando_titolo'>Titolo <span style='font-size:0.8em'>(max. 150 caratteri)</span></label><br />
		<input style='padding:0.4em;' name='bando_titolo' placeholder='Il nome del bando' required='required' maxlength='150' size='50'/><br />
		<br />
		<label style='margin-top:0.4em;' for='bando_URL'>URL</label><br />
		<input style='padding:0.4em;' name='bando_URL' placeholder='http://' required='required' type='url' maxlength='300' size='50'/><br />
		<br />
		<label style='margin-top:0.4em;' for='bando_ente'>Ente <span style='font-size:0.8em'>(max. 150 caratteri)</span></label><br />
		<input style='padding:0.4em;' name='bando_ente' placeholder='Ente che distribuisce il finanziamento' required='required' maxlength='150' size='50'/><br />
		<br />
		<label style='margin-top:0.4em;' for='bando_descrizione'>Descrizione <span style='font-size:0.8em'>(max. 500 caratteri)</span></label><br />
		<textarea style='padding:0.4em;' name='bando_descrizione' placeholder='Breve descrizione del bando' required='required' maxlength='500' cols='60' rows='6' /></textarea>
		<br />
		<label style='margin-top:0.4em;' for='bando_data_apertura'>Data di apertura</label><br />
		<input style='padding:0.4em;' name='bando_data_apertura' required='required' type='date'/>
		<br />
		<label style='margin-top:0.4em;' for='bando_data_chiusura'>Data di scadenza</label><br />
		<input style='padding:0.4em;' name='bando_data_chiusura' required='required' type='date'/>
		<br />
		<fieldset style='margin-top: 1em;'>
		<legend>Indirizzato a:</legend>
		<input name='bando_recipients[]' id='rec_studenti' type='checkbox' value='1'/> <label for='rec_studenti'>Studenti</label><br />
		<input name='bando_recipients[]' id='rec_laureati' type='checkbox' value='2'/> <label for='rec_laureati'>Laureati</label><br />
		<input name='bando_recipients[]' id='rec_dottorandi' type='checkbox' value='3'/> <label for='rec_dottorandi'>Dottorandi</label><br />
		<input name='bando_recipients[]' id='rec_postdoc' type='checkbox' value='4'/> <label for='rec_postdoc'>Postdoc</label><br />
		<input name='bando_recipients[]' id='rec_ricercatori' type='checkbox' value='5'/> <label for='rec_ricercatori'>Ricercatori</label><br />
		</fieldset>

		Paese (puoi scegliere più di un Paese, selezionali uno alla volta, oppure scegli 'Mondo' se non ci sono restrizioni di Paese!):<br />
		<select id='bando_paesi'><option value='-1'>Caricamento lista...</option></select>
		<div id='nomipaesi'><span class='nomePaese' id='noCountrySpan'>Nessun paese selezionato</span></div>

		<br />

		<input type='submit' value='Inserisci' />
		</form>";
		
	return $txt;
	}
	
add_shortcode('inserisci-bando', 'crea_form');

function modifica_form()
	{
	global $wpdb;
	
	if (!isset($_GET['id']))
		{
		$txt = "";
		return $txt;
		}
		
	$id = (int)$_GET['id'];
	
	$query = "SELECT b.nome, b.url, b.ente, b.descrizione, b.data_apertura, b.data_chiusura, b.autore, b.ultima_modifica FROM wp_bandi b WHERE id = %d";
	$querydest = "SELECT destinatario FROM wp_bandi_destinatari WHERE id_bando = %d";
	$querypaesi = "SELECT bn.id, paese, codice FROM wp_bandi2nazioni b2n LEFT JOIN wp_bandi_nazioni bn ON bn.id = b2n.id_nazione WHERE id_bando = %d";
	
	$bando = $wpdb->get_row($wpdb->prepare($query, $id));
	
	$tmp = $wpdb->get_results($wpdb->prepare($querydest, $id));
	$destinatari = array();
	foreach($tmp as $t)
		$destinatari[] = $t->destinatario;

	$paesi = $wpdb->get_results($wpdb->prepare($querypaesi, $id));
	$txt = "";

	$autore = get_usermeta($bando->autore, "first_name", TRUE)." ".get_usermeta($bando->autore, "last_name", TRUE);
	
	$txt .= "<form action='../inserisci-bando/' method='POST' style='font-size=1.2em'>
	
		<input type = 'hidden' name='bando_ID' value='".$id."'>
		<em>Ultima modifica di $autore il ".$bando->ultima_modifica."</em><br />
		<label style='margin-top:0.4em;' for='bando_titolo'>Titolo</label><br />
		<input style='padding:0.4em;' name='bando_titolo' placeholder='Il nome del bando' required='required' maxlength='150' size='50' value = '".
			htmlspecialchars($bando->nome, ENT_QUOTES)."' /><br />
		<br />
		<label style='margin-top:0.4em;' for='bando_URL'>URL</label><br />
		<input style='padding:0.4em;' name='bando_URL' placeholder='http://' required='required' type='url' maxlength='300' size='50' value = '".$bando->url."'/><br />
		<br />
		<label style='margin-top:0.4em;' for='bando_ente'>Ente</label><br />
		<input style='padding:0.4em;' name='bando_ente' placeholder='Ente che distribuisce il finanziamento' required='required' maxlength='150' size='50' value = '".
			htmlspecialchars($bando->ente, ENT_QUOTES)."'/><br />
		<br />
		<label style='margin-top:0.4em;' for='bando_descrizione'>Descrizione</label><br />
		<textarea style='padding:0.4em;' name='bando_descrizione' placeholder='Breve descrizione del bando' required='required' maxlength='500' cols='60' rows='6' />".
		htmlspecialchars($bando->descrizione, ENT_QUOTES)."</textarea>
		<br />
		<label style='margin-top:0.4em;' for='bando_data_apertura'>Data di apertura</label><br />
		<input style='padding:0.4em;' name='bando_data_apertura' required='required' type='date' value='".$bando->data_apertura."'/>
		<br />
		<label style='margin-top:0.4em;' for='bando_data_chiusura'>Data di scadenza</label><br />
		<input style='padding:0.4em;' name='bando_data_chiusura' required='required' type='date' value='".$bando->data_chiusura."'/>
		<br />
		<fieldset style='margin-top: 1em;'>
		<legend>Indirizzato a:</legend>";
	
		$ids = array("rec_studenti", "rec_laureati", "rec_dottorandi", "rec_postdoc", "rec_ricercatori");
		$labels = array("Studenti", "Laureati", "Dottorandi", "Postdoc", "Ricercatori");

		for ($i = 0; $i < count($ids); $i++)
			{
			if (in_array($labels[$i], $destinatari))
				{
				$sel = "checked = 'checked'";
				}
			else
				{
				$sel = "";
				}

			$txt .= "<input name='bando_recipients[]' id='".$ids[$i]."' type='checkbox' value='".($i+1)."' $sel/> <label for='".$ids[$i]."'>".$labels[$i]."</label><br />";
			}
			
		$txt .= "</fieldset>

		Paese (puoi scegliere più di un Paese, selezionali uno alla volta!):<br />
		<select id='bando_paesi'><option value='-1'>Caricamento lista...</option></select>";
		
		if (count($paesi) > 0)
			{
			$txt .= "<div id='nomipaesi'>";
			
			foreach ($paesi as $p)
				{
				$txt .= "<span id='paese_".strtolower($p->codice)."' class='nomePaese'><img src='".content_url()."/media/flags/".strtolower($p->codice).".png'
					 style='vertical-align:middle'> ".$p->paese."<span class='PaeseCloseButton'>X</span></span>
					 <input type='hidden' name='bando_id_paesi[]' value=".$p->id." />";
				}
				
			$txt .= "</div>";
			}
		else
			{
			$txt .= "<div id='nomipaesi'><span class='nomePaese' id='noCountrySpan'>Nessun paese selezionato</span></div>";
			}

		$txt .="<br />
		<input type='submit' name='bando_modifica' value='Modifica' />
		</form>";
		
	return $txt;
	}

add_shortcode('modifica-bando', 'modifica_form');

function cancella_bando()
	{
	global $wpdb;
	
	$id = (int)$_GET['id'];
	
	$bandiAdmin = get_usermeta(get_current_user_id(), "bandiAdmin", TRUE);
	
	if ($bandiAdmin == "")
		wp_die();
	
	$wpdb->query($wpdb->prepare("DELETE FROM wp_bandi WHERE id=%d", $id));
	$wpdb->query($wpdb->prepare("DELETE FROM wp_bandi2nazioni WHERE id_bando=%d", $id));
	$wpdb->query($wpdb->prepare("DELETE FROM wp_bandi_destinatari WHERE id_bando=%d", $id));
	
	echo "Il bando è stato eliminato!<br /><a href='../'>Torna alla lista dei bandi</a>";
	}
	
add_shortcode('cancella-bando', 'cancella_bando');

?>
