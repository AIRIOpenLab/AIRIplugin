<?php
/**
 * Plugin Name: assemblee
 * Description: Plugin per la gestione delle assemblee dell'associazione.
 * Version: 1.0.0
 * Author: Nicola Romanò
 * License: GPL2
 */
 
/*  Copyright 2015 Nicola Romanò (romano.nicola@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// Loading condizionale di Javascript
function assemblee_load_custom_scripts() 
	{
	$page_ID = get_the_ID();
	//echo $page_ID;
	if ($page_ID == 5208) // Pagina mostra assemblea
		{
		wp_enqueue_script('assembleeJS', plugins_url('js/assemblee.js', __FILE__), array("jquery"), '1', true);
		}
	}

add_action('wp_enqueue_scripts', 'assemblee_load_custom_scripts');


/******************* SHORTCODES *******************/

// [lista-assemblee]
function lista_assemblee($atts)
	{	
	global $wpdb;
	$uid = get_current_user_id();
	$txt = "";
	
	$tipo = isset($atts["tipo"]) ? sanitize_title($atts["tipo"]) : "";
	
	// Controlliamo che il valore di $tipo sia valido
	if (!in_array($tipo, array("incorso", "passate")))
		return;
		
	if (!strcmp($tipo, "incorso"))
		{
		$res_curr = $wpdb->get_results("SELECT id, date_from, vote_date_to, tipo, numero_partecipanti, quorum, seconda_convocazione   
			FROM wp_assemblee_assemblee 
			WHERE CURDATE() BETWEEN date_from AND date_to 
			ORDER BY date_from DESC");
		$res_future = $wpdb->get_results("SELECT id, date_from, vote_date_to, tipo, seconda_convocazione, finalizzata 
			FROM wp_assemblee_assemblee 
			WHERE date_from > CURDATE() 
			ORDER BY date_from DESC");

		if (count($res_curr) + count($res_future) == 0)
			{
			$txt .= "Non c'è alcuna assemblea in corso.";
			}
		else
			{
			$txt .= "<ul class='assemblee_presenti'>";
			foreach ($res_curr as $ac)
				{
				if ($ac->seconda_convocazione)
					$txt .= "<li><a href='mostra-assemblea?id=".$ac->id."'>Assemblea ".$ac->tipo." : ".
						strftime("%d %B %Y", strtotime($ac->date_from))." - ".
						strftime("%d %B %Y", strtotime($ac->vote_date_to))."</a> - <strong>IN CORSO (seconda convocazione)</strong><br /></li>";
				else if ($ac->numero_partecipanti > $ac->quorum)
					$txt .= "<li><a href='mostra-assemblea?id=".$ac->id."'>Assemblea ".$ac->tipo." : ".
						strftime("%d %B %Y", strtotime($ac->date_from))." - ".
						strftime("%d %B %Y", strtotime($ac->vote_date_to))."</a> - <strong>IN CORSO</strong><br /></li>";
				else
					$txt .= "<li><a href='mostra-assemblea?id=".$ac->id."'>Assemblea ".$ac->tipo." : ".
						strftime("%d %B %Y", strtotime($ac->date_from))." - ".
						strftime("%d %B %Y", strtotime($ac->vote_date_to))."</a> - <strong style='color:red'>QUORUM NON RAGGIUNTO</strong><br /></li>";
			}
			$txt .= "</ul>";

			$txt .= "<ul class='assemblee_future'>";
			foreach ($res_future as $af)
				{
				if ($af->finalizzata)
					{
					// Controlliamo se l'utente ha già scelto di partecipare
					$res_user = $wpdb->get_row($wpdb->prepare("SELECT token_partecipazione FROM wp_assemblee_partecipanti WHERE 
						id_assemblea=%d AND id_user=%d", $af->id, $uid));

					if (empty($res_user) || $res_user->token_partecipazione != NULL)
						$txt .= "<li><a href='mostra-assemblea?id=".$af->id."'>Assemblea ".$af->tipo." : ".
							strftime("%d %B %Y", strtotime($af->date_from))." - ".
							strftime("%d %B %Y", strtotime($af->vote_date_to))."<br />".
							"<a class='button' href='conferma-partecipazione?action=choice&aid=".$af->id."'>Conferma la tua presenza</a> oppure
							<a class='button' style='font-size:0.8em' href='delega?action=init&aid=".$af->id."'>Delega un altro socio</a></li>";
					else
						$txt .= "<li><a href='mostra-assemblea?id=".$af->id."'>Assemblea ".$af->tipo." : ".
							strftime("%d %B %Y", strtotime($af->date_from))." - ".
							strftime("%d %B %Y", strtotime($af->vote_date_to))."</a><br /><strong>Hai scelto di partecipare a questa assemblea</strong></li>";
					}
				else
					{
					$txt .= "<li>Assemblea ".$af->tipo." : ".
						strftime("%d %B %Y", strtotime($af->date_from))." - ".
						strftime("%d %B %Y", strtotime($af->date_to))."<br /></li>";
					}
				}
			$txt .= "</ul>";
			}
		}
	else // Assemblee passate
		{
		$res_past = $wpdb->get_results("SELECT id, date_from, vote_date_to, seconda_convocazione, tipo FROM wp_assemblee_assemblee 
			WHERE CURDATE() > date_to  
			ORDER BY date_from DESC");

		if (count($res_past) == 0)
			{
			$txt .= "Non è stata ancora tenuta alcuna assemblea.";
			}
		else
			{
			$txt .= "<ul class='assemblee_passate'>";
			foreach ($res_past as $ap)
				{
				$txt .= "<li><a href='mostra-assemblea?id=".$ap->id."'>Assemblea ".$ap->tipo." : ".
					strftime("%d %B %Y", strtotime($ap->date_from))." - ".
					strftime("%d %B %Y", strtotime($ap->vote_date_to))."</a></li>";
				}
			$txt .= "</ul>";
			}
		}
		
 	return $txt;
 	}

function mostra_assemblea()
	{
	global $wpdb;
	
	$id = isset($_GET['id']) ? (int)$_GET['id'] : -1;
	$user_id = get_current_user_id();

	if ($id == -1)
		return "";

	$txt = "";
		
	// Troviamo l'assemblea
	$res = $wpdb->get_row($wpdb->prepare("SELECT id, finalizzata, date_from, date_to, vote_date_to, 
	seconda_convocazione, tipo, introduzione, quorum, numero_partecipanti, 
	(CURDATE() > vote_date_to) passata, (CURDATE() BETWEEN date_from AND date_to) discussione_in_corso, 
	(CURDATE() BETWEEN (date_to + 1) AND vote_date_to) voti_in_corso, (CURDATE() < date_from) futura 
	FROM wp_assemblee_assemblee WHERE id=%d", $id));

	if (empty($res) || !$res->finalizzata)
		{
		return("<strong>Errore. Questa assemblea non esiste.</strong>");
		}

	// "Attiviamo" l'assemblea se necessario
	if (!$res->futura && $res->numero_partecipanti == NULL)
		{
		// Contiamo i partecipanti
		$res_partecip = $wpdb->get_row($wpdb->prepare("SELECT COUNT(id_user) partecipanti, COUNT(delega) deleghe FROM wp_assemblee_partecipanti WHERE id_assemblea=%d",
				$id));
		// Andiamo in seconda convocazione
		$wpdb->update("wp_assemblee_assemblee", array("numero_partecipanti" => ($res_partecip->partecipanti + $res_partecip->deleghe)), 
				array("id" => $id), array("%d"), array("%d"));
		$res->numero_partecipanti = $res_partecip->partecipanti + $res_partecip->deleghe;
		}

	$res_og = $wpdb->get_results($wpdb->prepare("SELECT id, titolo, testo 
		FROM wp_assemblee_ordini 
		WHERE id_assemblea=%d ORDER BY ordine", $id));

	$quorum_raggiunto = ($res->numero_partecipanti > $res->quorum) || $res->seconda_convocazione;

	// Controlliamo che l'utente stia partecipando
	$res_utente = $wpdb->get_row($wpdb->prepare("SELECT delega FROM wp_assemblee_partecipanti WHERE id_assemblea = %d AND id_user = %d", $id, $user_id));

	$partecipante = !empty($res_utente);

	$txt .= "<h1>Assemblea ".$res->tipo."</h1>
		<h2>Discussione: ".strftime("%d %B %Y", strtotime($res->date_from))." - ".strftime("%d %B %Y", strtotime($res->date_to)).
		"<br />Votazione fino al ".strftime("%d %B %Y", strtotime($res->vote_date_to))."</h2>";
	
	if (!$quorum_raggiunto && $res->numero_partecipanti != NULL && $res->futura == 0)
		$txt .= "<div style='background:red; color:white; font-weight:bold; padding:0.3em; text-align:center'>Questa assemblea è terminata (quorum non raggiunto).</div><br />";
	else if ($res->passata) // Assemblea passata (dopo le votazioni)
		$txt .= "<div style='background:red; color:white; font-weight:bold; padding:0.3em; text-align:center'>Questa assemblea è terminata. Non è più possibile commentare o votare.</div><br />";
	else if (($res->discussione_in_corso || $res->voti_in_corso) && !$partecipante) // Assemblea in corso, ma non abbiamo scelto di partecipare	
		$txt .= "<div style='background:red; color:white; font-weight:bold; padding:0.3em; text-align:center'>Attenzione! Non hai confermato la tua partecipazione a questa assemblea.<br />Non ti sarà quindi possibile commentare o votare.</div>";

	if ($res->futura && !$partecipante) // Assemblea futura
		{
		$res_user = $wpdb->get_row($wpdb->prepare("SELECT token_partecipazione FROM wp_assemblee_partecipanti WHERE id_assemblea=%d AND id_user=%d", $id, $uid));

		if (empty($res_user) || $res_user->token_partecipazione != NULL)
			{
			$txt .= "<div style='background:blue; color:white; font-weight:bold; padding:0.5em; text-align:center; border-radius:0.4em; '>Attenzione! Non hai ancora confermato 
				la tua partecipazione a questa assemblea.<br />
				Hai tempo fino al ".strftime("%d %B %Y", strtotime($res->date_from))." (escluso) altrimenti non ti sarà possibile commentare o votare.<br />".
				"<div><a class='button' target='_blank' href='../conferma-partecipazione?action=choice&aid=".$id."'>Conferma la tua presenza</a></div>
				oppure<br />
				<div><a class='button' style='font-size:0.8em' target='_blank' href='../delega?action=init&aid=".$id."'>Delega un altro socio</a></div>
				</div>";
			}
		}

	$txt .= "<h2>Informazioni generali</h2>";
 
	if ($res->numero_partecipanti != NULL)
		{
		if (!$quorum_raggiunto)
			{
			if ($res->passata)
				$txt .= "<strong style='color:red'>Il quorum non è stato raggiunto per questa assemblea. L'assemblea è stata svolta in seconda convocazione.</strong>";
			else if (!$res->futura)
				$txt .= "<strong style='color:red'>Il quorum non è stato raggiunto per questa assemblea. L'assemblea sarà svolta in seconda convocazione.</strong>";
			}
		else
			{
			if (!$res->seconda_convocazione)
				$txt .= "<strong style='color:green'>Il quorum è stato raggiunto per questa assemblea.</strong>";
			else
				$txt .= "<strong style='color:green'>Questa è la seconda convocazione dell'assemblea; non è richiesto il raggiungimento di un quorum.</strong>";
			}
		}
	
	if (!$res->futura)
		$txt .= "<br />".$res->numero_partecipanti." soci hanno dato conferma della partecipazione all'assemblea. - <a href='../partecipanti/?id=".$res->id.
			"' target='_blank'>Lista dei soci che hanno confermato</a><br /><hr />";

	$txt .= "<div>".$res->introduzione."</div>".
		"<h2>Ordine del giorno</h2>".
		"<ol>";

	foreach ($res_og as $og)
		{
		$txt .= "<li><b>".$og->titolo."</b><br />".$og->testo."</li><br />";
		// I commenti
		$res_commenti = $wpdb->get_results($wpdb->prepare("SELECT id_user, testo, timestamp 
				FROM wp_assemblee_commenti 
				WHERE id_assemblea=%d AND id_punto=%d ORDER BY ordine", $id, $og->id));

		if (count($res_commenti) > 0)
			{
			$txt .= "<div>";
			foreach($res_commenti as $c)
				{
				$user_info = get_userdata($c->id_user);
				
				$txt .= "<div style='margin-bottom:0.3em; background: #F7F7FF; border:1px solid gray; padding:0.5em; border-radius:2px; 
					box-shadow:0 2px 2px 0 rgba(0, 0, 0, .05), 0 1px 4px 0 rgba(0, 0, 0, .08), 0 3px 1px -2px rgba(0, 0, 0, .2)'>
					<strong>".$user_info->first_name." ".$user_info->last_name."</strong> - ".$c->timestamp."<br />".stripslashes($c->testo)."</div>"; 
				}

			$txt .= "</div><br />";
			}
		
		// Box dei commenti
		if ($res->discussione_in_corso && $partecipante)
			{
			$txt .= "<form method='POST' action='../commentoog?aid=".$res->id."'><textarea placeholder='Il tuo commento' required='required' name='og_".
				$og->id."_comment' cols='80' rows='5'></textarea>".
				"<br /><input type='submit' value='Aggiungi commento' /></form>";
			}
		}
	$txt .= "</ol>";
	
	// Controlliamo se l'utente ha già votato

	$res_dom = $wpdb->get_results($wpdb->prepare("SELECT id, testo, max_risposte, display_from <= CURRENT_DATE() to_show, vote_to, vote_to >= CURRENT_DATE() can_vote   
		FROM wp_assemblee_domande 
		WHERE id_assemblea=%d ORDER BY ordine", $id));

	$id_dom = array();
	foreach($res_dom as $dom)
		{
	 	$id_dom[] = (int)$dom->id;
		}
	$id_dom = implode(",", $id_dom);

//if (get_current_user_id() == 60)
//	echo $wpdb->last_query;


	if ($res->voti_in_corso || $res->passata)
		$txt .= "<h2>Votazioni</h2>";
	else
		$txt .= "<h2>Votazioni (apertura ".strftime("%d %B %Y", strtotime($res->date_to)+24*3600).")</h2>";

	if ($res->voti_in_corso)
		{
		$txt .= "<form action='../vota?aid=$res->id' method='POST'>";
		}

	$txt .= "<ul>";
	foreach ($res_dom as $dom)
		{	
		if ($dom->to_show == '0')
			continue;
					
		$available_to = $dom->vote_to ? "(votazione disponibile fino al $dom->vote_to)" : "";
		
		$txt .= "<li><strong>".$dom->testo."</strong> $available_to </li>";
		
		$res_risp = $wpdb->get_results($wpdb->prepare("SELECT id, testo 
			FROM wp_assemblee_risposte 
			WHERE id_domanda=%d ORDER BY ordine", $dom->id));

		$res_voti = $wpdb->get_results($wpdb->prepare("SELECT id_domanda, voto FROM wp_assemblee_voti WHERE id_user=%d AND id_domanda = %d", 
					get_current_user_id(), $dom->id));
		$votato = !empty($res_voti);

		if ($res->passata)
			{
			$res_risultati =  $wpdb->get_results($wpdb->prepare("SELECT voto, COUNT(voto) voti 
				FROM wp_assemblee_voti 
				WHERE id_domanda=%d GROUP BY voto 
				ORDER BY voto ASC", $dom->id));
			
			$totale_voti = 0;

			foreach ($res_risultati as $ris)
				$totale_voti += $ris->voti;
			$extra_astenuti = $res->numero_partecipanti - $totale_voti;

			$txt .= "<ul>";

			foreach ($res_risp as $risp)
				{
				$num_voti = 0;

				foreach ($res_risultati as $ris)
					{
					if ($ris->voto == $risp->id)
						{
						$num_voti = $ris->voti;
						break;
						}
					}

				if (strcmp($risp->testo, "Astenuto") === 0)
					{
					$num_voti += $extra_astenuti;
					}

				$perc_voti = sprintf("%0.2f", $num_voti / $res->numero_partecipanti * 100);
				$txt .= "<li style='list-style-type: none'>".$risp->testo." (".$num_voti." voti - <strong>".$perc_voti."%</strong>)</li>";
				}

			$txt .= "</ul>";
			}
		else if (!$votato)
			{
			$txt .= "<ul>";

			$dis = ($res->voti_in_corso && $partecipante && ($dom->can_vote == 1 || $dom->can_vote == NULL)) ? "" : "disabled='disabled'"; 
			
			if ($dom->max_risposte > 1)
				$txt .= "<div class='multiple_check' value='$dom->max_risposte' >";

			foreach ($res_risp as $risp)
				{
				if ($dom->max_risposte > 1)
					{
					$txt .= "<li style='list-style-type: none'><input type='checkbox' $dis name='domanda_".$dom->id.
						"[]' id='d_".$dom->id."_r_".$risp->id."' value='".$risp->id."'>".
						"<label for='d_".$dom->id."_r_".$risp->id."'>".$risp->testo."</label></li>";
					}
				else
					{
					$txt .= "<li style='list-style-type: none'><input type='radio' $dis required='required' name='domanda_".$dom->id.
						"' id='d_".$dom->id."_r_".$risp->id."' value='".$risp->id."'>".
						"<label for='d_".$dom->id."_r_".$risp->id."'>".$risp->testo."</label></li>";
					}
				}

			$txt .= "</ul>";

			if ($dom->max_risposte > 1)
				$txt .= "</div>";
			}
		else
			{
			foreach ($res_voti as $voto)
				{
				if ($voto->id_domanda == $dom->id)
					{
					foreach($res_risp as $r)
						{
						if ($r->id == $voto->voto)
							$txt .= "<li style='list-style-type: none'>Hai votato: <strong>".$r->testo."</strong></li>";
						}
					}
				}
			}
		}
	
	$txt .= "</ul>";

	if ($res->voti_in_corso && (!$votato) && $partecipante)
		{
		$txt .= "<input type='submit' value='Vota'/></form>";
		}

	return $txt;
	}

function lista_partecipanti()
	{
	global $wpdb;

	$txt = "";

	$res_part = $wpdb->get_results($wpdb->prepare("SELECT id_user, delega FROM wp_assemblee_partecipanti 
			WHERE id_assemblea=%d", (int)($_GET["id"])));

	$res_ass = $wpdb->get_row($wpdb->prepare("SELECT date_from FROM wp_assemblee_assemblee WHERE id=%d", (int)($_GET["id"])));

	$txt .= "I seguenti <strong>".count($res_part)."</strong> soci hanno partecipato all'assemblea del ".$res_ass->date_from.": <br /><ul>";

	foreach ($res_part as $part)
		{
		$user_info = get_userdata($part->id_user);

		$nome = $user_info->first_name." ".$user_info->last_name;

		if ($part->delega != NULL)
			{
			$delega_info = get_userdata($part->delega);
			$nome_delega = $delega_info->first_name." ".$delega_info->last_name;
			$txt .= "<li>$nome - (con delega di $nome_delega)</li>";
			}
		else
			{
			$txt .= "<li>".$nome."</li>";
			}
		}
	$txt .= "</ul>";
	
	return $txt;
	}

function conferma_partecipazione()
	{
	global $wpdb;

	if (!isset($_GET['aid']) || !isset($_GET['action']))
		return ("<div style='text-align:center'><img src='http://www.airicerca.org/wp-content/uploads/2014/03/airi-logo-256x62.png' /></div><br />Errore interno");

	$id = (int)($_GET['aid']);
	$act = sanitize_text_field($_GET['action']);
	$uid = get_current_user_id();

	$res = $wpdb->get_row($wpdb->prepare("SELECT date_from, date_to, vote_date_to, tipo, quorum, seconda_convocazione, introduzione, 
	(CURDATE() > date_to) passata, (CURDATE() BETWEEN date_from AND date_to) in_corso  
	FROM wp_assemblee_assemblee 
	WHERE id=%d", $id));
	
	$txt = "";

	if ($res->passata)
		{
		$txt .= "<h1>Assemblea ".$res->tipo." di AIRIcerca.</h1><br />".
			"<div style='color:white; font-weight:bold; background:red;'>Questa assemblea è già terminata, non è più possibile parteciparvi</strong></div><br />
			<a href='../mostra-assemblea/?id=".$id."'>Visualizza i contenuti dell'assemblea.";
		}
	else if ($res->in_corso)
		{
		$txt .= "<h1>Assemblea ".$res->tipo." di AIRIcerca.</h1><br />".
			"<div style='color:white; font-weight:bold; background:red;'>Questa assemblea è in corso, non è più possibile confermare la propria partecipazione.</div><br />
			<a href='../mostra-assemblea/?id=".$id."'>Visualizza i contenuti dell'assemblea.";
		}
	else
		{
		if ($act == "choice")
			{
			$txt .= "<h1>Assemblea ".$res->tipo." di AIRIcerca.</h1><br />".
				"Grazie per aver scelto di partecipare a questa Assemblea!<br /><br />".
				"L'Assemblea si terrà in due fasi:<br />".
				"1. Una prima fase di discussione dell'ordine del giorno, dal <strong>".strftime("%d %B %Y", strtotime($res->date_from))."</strong> al <strong>".
					strftime("%d %B %Y", strtotime($res->date_to))."</strong><br />".
				"2. Una seconda fase di votazione, fino al <strong>".strftime("%d %B %Y", strtotime($res->vote_date_to))."</strong><br /><br />";
			if (!$res->seconda_convocazione)
				$txt .= "<div>Lo svolgimento dell'Assemblea richiede il raggiungimento del quorum del 50% + 1 dei soci che abbiano confermato la loro partecipazione. 
				Nel caso il quorum non venisse raggiunto, procederemo ad una seconda convocazione dell'Assemblea, che non richiederà un quorum</div>";
			else
				$txt .= "<div>Questa è la seconda convocazione dell'Assemblea, percui non sarà richiesto un quorum per lo svolgimento.</div>";

			$txt .=	"<div align='center'><a class='button' href='../conferma-partecipazione?action=mail&aid=".$id.
					"' style='background:green' target='_blank'>Conferma la tua partecipazione</a></div>".
				"<div align='center'><a style='font-size:0.7em' class='button' href='../delega?action=init&aid=".$id."' style='background:green' target='_blank'>Delega un altro socio</a></div>".
				"<div align='center'><a style='font-size:0.7em' class='button' href='../mostra-assemblea?id=".$id."' target='_blank'>Visualizza l'assemblea</a></div>";
			}
		else if ($act == "mail")
			{
			// Controlliamo che l'utente non abbia già confermato
			$res_user = $wpdb->get_row($wpdb->prepare("SELECT token_partecipazione FROM wp_assemblee_partecipanti WHERE id_assemblea=%d AND id_user=%d", $id, $uid));
			$resend = isset($_GET['resend']) ? (int)$_GET['resend'] : 0;

			if (empty($res_user) || $resend == 1)
				{
				$ud = get_userdata($uid);

				$txt .= "<div style='text-align:center'><img src='http://www.airicerca.org/wp-content/uploads/2014/03/airi-logo-256x62.png' /></div><br />
					Grazie ".$ud->first_name.",<br /> ti abbiamo inviato un'email con un link di conferma per finalizzare la tua partecipazione.<br />".
					"Se non dovesse arrivarti a breve, controlla che non sia finita nello spam, oppure <a href='mailto:webmaster@airicerca.org'>contattaci</a>.";

				$email = $ud->user_email;
				$nome = $ud->first_name;
				// Mail di conferma all'utente
				$to = $email;
				$token = ($resend == 1) ? $res_user->token_partecipazione : bin2hex(openssl_random_pseudo_bytes(7));
				$url = "http://www.airicerca.org".strtok($_SERVER["REQUEST_URI"],'?');
				$link = $url."?aid=".$id."&token=".$token."&action=conf";

				if (!$resend)
					$wpdb->insert("wp_assemblee_partecipanti",
						      array('id_user' => $uid, 'id_assemblea' => $id, 'token_partecipazione' => $token, 'delega' => NULL));

				$subject = "Conferma partecipazione all'assemblea di AIRIcerca!";

				$body = "<div style='text-align:center'><img src='http://www.airicerca.org/wp-content/uploads/2014/03/airi-logo-256x62.png' /></div><br />
					Gentile $nome,<br /> 
					grazie di aver scelto di partecipare all'assemblea ".$res->tipo." di AIRIcerca.<br />

					<strong>Clicca su questo link per confermare la tua partecipazione</strong><br />".$link."<br />

					L'Assemblea si terrà in due fasi:<br />
					1. Una prima fase di discussione dell'ordine del giorno, dal <strong>".strftime("%d %B %Y", strtotime($res->date_from))."</strong> al <strong>".
						strftime("%d %B %Y", strtotime($res->date_to))."</strong><br />
					2. Una seconda fase di votazione, fino al <strong>".strftime("%d %B %Y", strtotime($res->vote_date_to))."</strong><br /><br />
					<div>Lo svolgimento dell'Assemblea richiede il raggiungimento del quorum del 50% + 1 dei soci partecipanti. Nel caso il quorum non venisse raggiunto,
					procederemo ad una seconda convocazione dell'Assemblea, che non richiederà un quorum</div>

					Il team di AIRIcerca.
					<hr />
					Vuoi aiutare AIRIcerca? Puoi <a href='http://www.airicerca.org/collabora-con-noi/' target='_blank'>collaborare</a> con noi 
					o <a href='http://www.airicerca.org/dona-ora/' target='_blank'>contribuire</a> con una donazione!";

				$headers[] = "From: Assemblee AIRIcerca <webmaster@airicerca.org>";
				$headers[] = "Reply-To: webmaster@airicerca.org";
				$headers[] = "Content-Type: text/html";
				$headers[] = "charset=UTF-8";
				$headers[] = "MIME-Version: 1.0";
				$headers[] = "X-Mailer: PHP/".phpversion();

				wp_mail($to, $subject, $body, $headers);
				}
			else if ($res_user->token_partecipazione == NULL)
				{
				$ud = get_userdata($uid);
				$link_assemblea = "../mostra-assemblea/?id=".$id;
				$txt .= "<div style='text-align:center'><img src='http://www.airicerca.org/wp-content/uploads/2014/03/airi-logo-256x62.png' /></div><br />
					Gentile ".$ud->first_name.",<br />Hai già provveduto a confermare la tua partecipazione all'assemblea.<br />
					Potrai partecipare all'assemblea, una volta iniziata, <a href='".$link_assemblea."'> a questo indirizzo</a>:<br />".
					"Per qualsiasi altro problema non esitare a <a href='mailto:webmaster@airicerca.org'>contattarci</a>.";
				}
			else
				{
				$ud = get_userdata($uid);
				$url = strtok($_SERVER["REQUEST_URI"],'?');

				$txt .= "<div style='text-align:center'><img src='http://www.airicerca.org/wp-content/uploads/2014/03/airi-logo-256x62.png' /></div><br />
					Gentile ".$ud->first_name.",<br />Ti abbiamo già inviato una mail per confermare la tua partecipazione all'assemblea.<br />
					Nel caso non ti fosse arrivata, controlla che non sia finita nello spam, oppure<br />
					<div align='center'><a class='button' href='".
					$url."?aid=$id&action=mail&resend=1'>Invia nuovamente la mail di conferma</a></div><br /><br />
					Per qualsiasi altro problema non esitare a <a href='mailto:webmaster@airicerca.org'>contattarci</a>.";
				}
			}
		else if ($act == "conf")
			{
			if (!isset($_GET['token']))
				return ("<div style='text-align:center'><img src='http://www.airicerca.org/wp-content/uploads/2014/03/airi-logo-256x62.png' /></div><br />Errore interno");

			$token = $_GET['token'];

			// Controlliamo che il token corrisponda all'utente e che effettivamente l'utente abbia scelto di partecipare
			$res_user = $wpdb->get_row($wpdb->prepare("SELECT token_partecipazione FROM wp_assemblee_partecipanti WHERE id_assemblea=%d AND id_user=%d", $id, $uid));

			if (empty($res_user))
				{
				$txt .= "Errore interno, non hai scelto di partecipare a questa assemblea.";
				}
			else if ($res_user->token_partecipazione != $token)
				{
				if ($res_user->token_partecipazione == NULL)
					$txt .= "<div style='text-align:center'><img src='http://www.airicerca.org/wp-content/uploads/2014/03/airi-logo-256x62.png' /></div><br />
						Hai già confermato la tua partecipazione.";
				else
					$txt .= "Link non valido.";
				}
			else
				{
				// Confermiamo la partecipazione dell'utente
				$wpdb->update("wp_assemblee_partecipanti", array("token_partecipazione" => NULL), array("id_assemblea" => $id, "id_user" => $uid),
					      array("%s"), array("%d", "%d"));

				$link_assemblea = "../mostra-assemblea/?id=".$id;

				$txt .= "<div style='text-align:center'><img src='http://www.airicerca.org/wp-content/uploads/2014/03/airi-logo-256x62.png' /></div><br />
					Grazie, la tua partecipazione è stata confermata.<br />
					Potrai partecipare all'assemblea, una volta iniziata, <a href='".$link_assemblea."'> a questo indirizzo</a>:<br />
					Per qualsiasi altro problema non esitare a <a href='mailto:webmaster@airicerca.org'>contattarci</a>.";
				}
			}
		}

	return $txt;
	}

function delega_socio()
	{
	global $wpdb;

	if (!isset($_GET['aid']) || !isset($_GET['action']))
		return ("<div style='text-align:center'><img src='http://www.airicerca.org/wp-content/uploads/2014/03/airi-logo-256x62.png' /></div><br />Errore interno");

	$action = sanitize_text_field($_GET['action']);
	$uid = get_current_user_id();
	$id = (int)($_GET['aid']);

	$txt = "";

	if ($action == "init")
		{
		$txt .= "<div style='text-align:center'><img src='http://www.airicerca.org/wp-content/uploads/2014/03/airi-logo-256x62.png' /></div><br />
			Questa pagina ti permetterà di delegare un altro socio per la partecipazione all'assemblea dell'Associazione.<br />
			<div>Per poter delegare qualcuno è necessario conoscere il suo indirizzo email (quello utilizzato per iscriversi ad AIRIcerca).<br />
			Il delegato deve confermare la propria partecipazione all'assemblea, prima di poter accettare la delega.<br />
			Il voto del delegato varrà doppio in sede di assemblea.</div><br />

			<strong>NOTA importante</strong>: come dallo Statuto dell'Associazione, ogni Socio può rappresentare in Assemblea (a mezzo di delega) non più di un altro associato oltre a se stesso.
			Non sarà quindi possibile delegare un Socio che abbia già accettato la delega di qualcun altro";

		$txt .= "<form action='".strtok($_SERVER[REQUEST_URI], "?")."' >Indirizzo email del delegato: <input size='30' name='emaildelega' type='email' 
			placeholder='Indirizzo email' style='padding:0.3em' required='required' />
			<input type='hidden' name='aid' value=$id/>
			<input type='hidden' name='action' value='email' />
			<input type='submit' class='button' style='font-size=0.7em' value='Delega' /></form>";
		}
	else if ($action == "email")
		{
		$ud = get_userdata($uid);
		if (!isset($_GET['emaildelega']))
			return ("<div style='text-align:center'><img src='http://www.airicerca.org/wp-content/uploads/2014/03/airi-logo-256x62.png' /></div><br />Errore interno");

		$email = sanitize_text_field($_GET['emaildelega']);

		$delegato = $wpdb->get_row($wpdb->prepare("SELECT id, user_email FROM wp_users WHERE user_email LIKE '%s'", $email));
		$delegante = $wpdb->get_row($wpdb->prepare("SELECT id_user, delega FROM wp_assemblee_partecipanti WHERE (id_user=%d OR delega=%d) AND id_assemblea = %d", $uid, $uid, $id));

		if (empty($delegato))
			{
			$txt .= "<div style='text-align:center'><img src='http://www.airicerca.org/wp-content/uploads/2014/03/airi-logo-256x62.png' /></div><br />
				Gentile ".$ud->first_name.",<br />questo indirizzo email non è nel database dei nostri soci.<br /><br />
				<a class='button' href='../delega?action=init&aid=".$id."'>Delega un altro socio</a>";
			}
		else if ($delegato->id == $uid)
			{
			$txt .= "<div style='text-align:center'><img src='http://www.airicerca.org/wp-content/uploads/2014/03/airi-logo-256x62.png' /></div><br />
				Gentile ".$ud->first_name.",<br />non puoi delegare te stesso!<br /><br />
				<a class='button' href='../delega?action=init&aid=".$id."'>Delega un altro socio</a>";
			}
		else if (!empty($delegante))
			{
			if ($delegante->id_user == $uid)
				{
				$txt .= "<div style='text-align:center'><img src='http://www.airicerca.org/wp-content/uploads/2014/03/airi-logo-256x62.png' /></div><br />
					Gentile ".$ud->first_name.",<br />hai già confermato la tua partecipazione a questa assemblea.<br />".
					"Per qualsiasi problema non esitare a <a href='mailto:webmaster@airicerca.org'>contattarci</a>.";
				}
			else if ($delegante->delega == $uid)
				{
				$del_data = get_userdata($delegato->id);

				$txt .= "<div style='text-align:center'><img src='http://www.airicerca.org/wp-content/uploads/2014/03/airi-logo-256x62.png' /></div><br />
					Gentile ".$ud->first_name.",<br />Abbiamo già contattato ".$del_data->first_name." ".$del_data->last_name.
					" a riguardo della tua delega.<br />Ti contatteremo non appena la avrà accettata.";
				}
			}
		else
			{
			$del_data = get_userdata($delegato->id);

			// Controlliamo se il delegato ha già confermato la sua presenza e se sta già facendo da delegato per qualcun altro
			$res_deleg = $wpdb->get_row($wpdb->prepare("SELECT id_user, token_partecipazione, delega FROM wp_assemblee_partecipanti WHERE id_user=%d AND id_assemblea = %d", $delegato->id, $id));

			if (empty($res_deleg)) // Non ha confermato, settiamo entrambi i token
				{
				$res_ass = $wpdb->get_row($wpdb->prepare("SELECT id, date_from, date_to, vote_date_to, tipo 
					FROM wp_assemblee_assemblee WHERE id=%d", $id));

				// Mail di conferma al delegato
				$email = $del_data->user_email;
				$nome = $del_data->first_name;
				$nome_delegato = $ud->first_name." ".$ud->last_name;
				$to = $email;
				$token = bin2hex(openssl_random_pseudo_bytes(7));
				$tokendel = bin2hex(openssl_random_pseudo_bytes(7));
				$url = "http://www.airicerca.org".strtok($_SERVER["REQUEST_URI"],'?');
				$link = $url."?aid=".$id."&token=".$token."&tokendel=".$tokendel."&action=conf";
				$linkrefuse = $url."?aid=".$id."&token=".$token."&tokendel=".$tokendel."&action=ref";
				$linkrefuseall = $url."?aid=".$id."&token=".$token."&tokendel=".$tokendel."&action=refall";

				$wpdb->insert("wp_assemblee_partecipanti",
					      array('id_user' => $delegato->id, 'id_assemblea' => $id, 'token_partecipazione' => $token, 'delega' => $uid, 'token_delega' => $tokendel));

				$txt .= "<div style='text-align:center'><img src='http://www.airicerca.org/wp-content/uploads/2014/03/airi-logo-256x62.png' /></div><br />
					Grazie ".$ud->first_name.", la tua richiesta di delega è stata registrata.<br />
					A breve invieremo un'e-mail di conferma a ".$del_data->first_name." ".$del_data->last_name." che potrà così accettare la tua delega.<br />
					Ti informeremo una volta che la delega sarà stata accettata.";

				$subject = "Richiesta di delega per l'assemblea di AIRIcerca!";

				$body = "<div style='text-align:center'><img src='http://www.airicerca.org/wp-content/uploads/2014/03/airi-logo-256x62.png' /></div><br />
					Gentile $nome,<br /> 
					$nome_delegato ha fatto una richiesta di delega per partecipare all'assemblea ".$res_ass->tipo." di AIRIcerca.<br />

					<strong>Clicca su questo link per confermare la tua partecipazione e accettare la delega di $nome_delegato</strong><br />".$link."<br />

					<strong>Clicca su questo link per confermare la tua partecipazione ma NON accettare la delega di $nome_delegato</strong><br />".$linkrefuse."<br />

					Clicca su questo link se non volessi partecipare all'assemblea.<br />".$linkrefuseall."<br />

					L'Assemblea si terrà in due fasi:<br />
					1. Una prima fase di discussione dell'ordine del giorno, dal <strong>".strftime("%d %B %Y", strtotime($res_ass->date_from))."</strong> al <strong>".
						strftime("%d %B %Y", strtotime($res_ass->date_to))."</strong><br />
					2. Una seconda fase di votazione, fino al <strong>".strftime("%d %B %Y", strtotime($res_ass->vote_date_to))."</strong><br /><br />
					<div>Lo svolgimento dell'Assemblea richiede il raggiungimento del quorum del 50% + 1 dei soci partecipanti. Nel caso il quorum non venisse raggiunto,
					procederemo ad una seconda convocazione dell'Assemblea, che non richiederà un quorum</div>

					Il team di AIRIcerca.
					<hr />
					Vuoi aiutare AIRIcerca? Puoi <a href='http://www.airicerca.org/collabora-con-noi/' target='_blank'>collaborare</a> con noi 
					o <a href='http://www.airicerca.org/dona-ora/' target='_blank'>contribuire</a> con una donazione!";

				$headers[] = "From: Assemblee AIRIcerca <webmaster@airicerca.org>";
				$headers[] = "Reply-To: webmaster@airicerca.org";
				$headers[] = "Content-Type: text/html";
				$headers[] = "charset=UTF-8";
				$headers[] = "MIME-Version: 1.0";
				$headers[] = "X-Mailer: PHP/".phpversion();

				wp_mail($to, $subject, $body, $headers);
				}
			else if ($res_deleg->delega != NULL)
				{
				$txt .= "<div style='text-align:center'><img src='http://www.airicerca.org/wp-content/uploads/2014/03/airi-logo-256x62.png' /></div><br />
					Gentile ".$ud->first_name.",<br />Siamo spiacenti ma ".$del_data->first_name." ".$del_data->last_name.
					" ha già accettato la delega di un altro socio.<br />
					<a class='button' href='../delega?action=init&aid=".$id."'>Delega un altro socio</a>";
				}
			else // Il delegato ha confermato la partecipazione ma non ha accettato nessuna delega
				{
				$res_ass = $wpdb->get_row($wpdb->prepare("SELECT id, date_from, date_to, vote_date_to, tipo 
					FROM wp_assemblee_assemblee WHERE id=%d", $id));

				// Mail di conferma al delegato
				$email = $del_data->user_email;
				$nome = $del_data->first_name;
				$nome_delegato = $ud->first_name." ".$ud->last_name;
				$to = $email;
				$tokendel = bin2hex(openssl_random_pseudo_bytes(7));
				$url = "http://www.airicerca.org".strtok($_SERVER["REQUEST_URI"],'?');
				$link = $url."?aid=".$id."&tokendel=".$tokendel."&action=conf";
				$linkrefuse = $url."?aid=".$id."&tokendel=".$tokendel."&action=ref";

				$wpdb->update("wp_assemblee_partecipanti",
					      array('delega' => $uid, 'token_delega' => $tokendel),
					      array('id_user' => $delegato->id, 'id_assemblea' => $id));

				$txt .= "<div style='text-align:center'><img src='http://www.airicerca.org/wp-content/uploads/2014/03/airi-logo-256x62.png' /></div><br />
					Grazie ".$ud->first_name.", la tua richiesta di delega è stata registrata.<br />
					A breve invieremo un'e-mail di conferma a ".$del_data->first_name." ".$del_data->last_name." che potrà così accettare la tua delega.<br />
					Ti informeremo una volta che la delega sarà stata accettata.";

				$subject = "Richiesta di delega per l'assemblea di AIRIcerca!";

				$body = "<div style='text-align:center'><img src='http://www.airicerca.org/wp-content/uploads/2014/03/airi-logo-256x62.png' /></div><br />
					Gentile $nome,<br /> 
					$nome_delegato ha fatto una richiesta di delega per partecipare all'assemblea ".$res_ass->tipo." di AIRIcerca.<br />

					<strong>Clicca su questo link per accettare la delega di $nome_delegato</strong><br />".$link."<br />

					<strong>Clicca su questo link per NON accettare la delega di $nome_delegato</strong><br />".$linkrefuse."<br />

					Il team di AIRIcerca.
					<hr />
					Vuoi aiutare AIRIcerca? Puoi <a href='http://www.airicerca.org/collabora-con-noi/' target='_blank'>collaborare</a> con noi 
					o <a href='http://www.airicerca.org/dona-ora/' target='_blank'>contribuire</a> con una donazione!";

				$headers[] = "From: Assemblee AIRIcerca <webmaster@airicerca.org>";
				$headers[] = "Reply-To: webmaster@airicerca.org";
				$headers[] = "Content-Type: text/html";
				$headers[] = "charset=UTF-8";
				$headers[] = "MIME-Version: 1.0";
				$headers[] = "X-Mailer: PHP/".phpversion();

				wp_mail($to, $subject, $body, $headers);
				}
			}
		}
	else if ($action == "conf" || $action == "ref" || $action == "refall")
		{
		if (!isset($_GET['token'])) // L'utente deve aver già confermato la sua partecipazione
			{
			$res_del = $wpdb->get_row($wpdb->prepare("SELECT id FROM wp_assemblee_partecipanti WHERE id_assemblea = %d AND id_user = %d", $id, $uid));
			if (empty($res_del))
				return ("<div style='text-align:center'><img src='http://www.airicerca.org/wp-content/uploads/2014/03/airi-logo-256x62.png' /></div><br />Errore 	interno 01");
			
			}
			
		if (!isset($_GET['tokendel']))
			{
			return ("<div style='text-align:center'><img src='http://www.airicerca.org/wp-content/uploads/2014/03/airi-logo-256x62.png' /></div><br />Errore interno 02");
			}

		$tokendel = sanitize_text_field($_GET['tokendel']);

		if (!isset($_GET['token']))
			{
			$res_del = $wpdb->get_row($wpdb->prepare("SELECT id, delega FROM wp_assemblee_partecipanti WHERE id_assemblea = %d AND id_user = %d  
				AND token_delega = %s", $id, $uid, $tokendel));
			}
		else
			{
			$token = sanitize_text_field($_GET['token']);
			$res_del = $wpdb->get_row($wpdb->prepare("SELECT id, delega FROM wp_assemblee_partecipanti WHERE id_assemblea = %d AND token_partecipazione = %s  
				AND token_delega = %s", $id, $token, $tokendel));
			}
			
		if (empty($res_del))
			{
			return ("<div style='text-align:center'><img src='http://www.airicerca.org/wp-content/uploads/2014/03/airi-logo-256x62.png' /></div><br />Link non valido.");
			}

		$ud = get_userdata($uid);
		$del_data = get_userdata($res_del->delega);

		if ($action == "conf")
			{
			$wpdb->update("wp_assemblee_partecipanti", array("token_partecipazione" => NULL, "token_delega" => NULL), 
					array("id" => $res_del->id), array("%s", "%s"), array("%d"));

			$txt .= "<div style='text-align:center'><img src='http://www.airicerca.org/wp-content/uploads/2014/03/airi-logo-256x62.png' /></div><br />
				Grazie ".$ud->first_name.",<br />hai confermato la tua presenza all'assemblea e la delega di ".$del_data->first_name." ".$del_data->last_name;
			}
		else if ($action == "ref")
			{
			$wpdb->update("wp_assemblee_partecipanti", array("token_partecipazione" => NULL, "delega" => NULL, "token_delega" => NULL), 
					array("id" => $res_del->id), array("%s", "%s", "%s"), array("%d"));

			$txt .= "<div style='text-align:center'><img src='http://www.airicerca.org/wp-content/uploads/2014/03/airi-logo-256x62.png' /></div><br />
				Grazie ".$ud->first_name.",<br />hai <strong>confermato</strong> la tua presenza all'assemblea ma hai <strong>rifiutato</strong> la delega di ".
				$del_data->first_name." ".$del_data->last_name.
				"<br />Per qualsiasi altro problema non esitare a <a href='mailto:webmaster@airicerca.org'>contattarci</a>.<br />";

			}
		else if ($action == "refall")
			{
			$wpdb->delete("wp_assemblee_partecipanti", array("id" => $res_del->id), array("%d"));

			$txt .= "<div style='text-align:center'><img src='http://www.airicerca.org/wp-content/uploads/2014/03/airi-logo-256x62.png' /></div><br />
				Grazie ".$ud->first_name.",<br />hai deciso di <strong>non partecipare</strong> all'assemblea e hai 
				<strong>rifiutato</strong> la delega di ".$del_data->first_name." ".$del_data->last_name.
				"<br />Nel caso cambiassi idea, potrai sempre visitare la <a href='../'>pagina delle assemblee</a> nell'area soci e riconfermare la tua presenza<br />
				Per qualsiasi altro problema non esitare a <a href='mailto:webmaster@airicerca.org'>contattarci</a>. <br />";
			}
			
		if ($action == "conf" || $action == "ref" || $action == "refall") // mandiamo una mail a chi ha richiesto la delega
			{
			$subject = "Delega per l'assemblea di AIRIcerca!";
			
			if ($action == "conf")
				$decision = $ud->first_name." ".$ud->last_name." ha accettato la tua richiesta di delega.";
			else
				$decision = "Siamo spiacenti ma ".$ud->first_name." ".$ud->last_name." non ha accettato la tua richiesta di delega. Puoi scegliere di delegare un altro socio o di partecipare direttamente all'assemblea usando <a href='http://www.airicerca.org/area-soci/assemblee/mostra-assemblea/?id=".(int)$_GET['aid']."'>questo link</a>.";

			$body = "<div style='text-align:center'><img src='http://www.airicerca.org/wp-content/uploads/2014/03/airi-logo-256x62.png' /></div><br />
				Gentile ".$del_data->first_name.",<br /> 
				$decision <br />

				Il team di AIRIcerca.
				<hr />					
				Vuoi aiutare AIRIcerca? Puoi <a href='http://www.airicerca.org/collabora-con-noi/' target='_blank'>collaborare</a> con noi 
					o <a href='http://www.airicerca.org/dona-ora/' target='_blank'>contribuire</a> con una donazione!";

			$headers[] = "From: Assemblee AIRIcerca <webmaster@airicerca.org>";
			$headers[] = "Reply-To: webmaster@airicerca.org";
			$headers[] = "Content-Type: text/html";
			$headers[] = "charset=UTF-8";
			$headers[] = "MIME-Version: 1.0";
			$headers[] = "X-Mailer: PHP/".phpversion();

			wp_mail($del_data->user_email, $subject, $body, $headers);
			
			$txt .= "<br /><hr />Abbiamo mandato una mail di conferma a ".$del_data->first_name." ".$del_data->last_name;
			} 
		}

	return $txt;
	}

function comment_og()
	{
	global $wpdb;
	$txt = "";
	$ud = get_userdata(get_current_user_id());

	if (!isset($_GET['aid']))
		return("<div style='text-align:center'><img src='http://www.airicerca.org/wp-content/uploads/2014/03/airi-logo-256x62.png' /></div><br />Errore interno.");

	$aid = (int)$_GET['aid'];

	foreach ($_POST as $name => $val)
		{
		if (preg_match("/^og_([0-9])?/", $name))
			{
			$token = explode("_", $name);

			$res_ord = $wpdb->get_row($wpdb->prepare("SELECT MAX(ordine)+1 ordine FROM wp_assemblee_commenti WHERE id_assemblea = %d AND id_punto = %d", $aid, $token[1]));

	 		if (empty($res_ord) || $res_ord->ordine == NULL)
				$ordine = 1;
			else
				$ordine = $res_ord->ordine;

			$wpdb->insert("wp_assemblee_commenti", array("id_assemblea"=>$aid, "ordine"=>$ordine, "id_punto"=>$token[1], 
				"testo"=>$val, "id_user"=>get_current_user_id(), "timestamp"=>current_time('mysql', 1)),
				array("%d", "%d", "%d", "%s", "%d", "%s"));
			}
		}

	$txt .= "<div style='text-align:center'><img src='http://www.airicerca.org/wp-content/uploads/2014/03/airi-logo-256x62.png' /></div><br />
		Grazie ".$ud->first_name.",<br />il tuo commento è stato registrato.<br />
		<a href='../mostra-assemblea?id=".$aid."' class='button'>Torna all'assemblea</a>";

	return $txt;
	}

function vota_assemblea()
	{
	global $wpdb;

	$txt = "";
	$ud = get_userdata(get_current_user_id());

	if (!isset($_GET['aid']))
		return("<div style='text-align:center'><img src='http://www.airicerca.org/wp-content/uploads/2014/03/airi-logo-256x62.png' /></div><br />Errore interno.");

	$aid = (int)$_GET['aid'];

	// Controlliamo che l'utente non abbia già votato
	$res_user = $wpdb->get_results($wpdb->prepare("SELECT ID FROM wp_assemblee_voti WHERE id_user=%d AND id_assemblea=%d", get_current_user_id(), $aid));
	if (!empty($res_user))
		{
		return "<div style='text-align:center'><img src='http://www.airicerca.org/wp-content/uploads/2014/03/airi-logo-256x62.png' /></div><br />
			Gentile ".$ud->first_name.",<br />hai già votato per questa assemblea, non è possibile votare più di una volta.<br />
			<a href='../mostra-assemblea?id=".$aid."' class='button'>Torna all'assemblea</a>";
		}

	// Controlliamo se l'utente è delegatario di qualcuno
	$res_del = $wpdb->get_row($wpdb->prepare("SELECT delega FROM wp_assemblee_partecipanti WHERE id_user=%d AND id_assemblea=%d", get_current_user_id(), $aid));
	
	// Sanity check per domande a risposta multipla
	$res_multi = $wpdb->get_results($wpdb->prepare("SELECT ID, max_risposte FROM `wp_assemblee_domande` WHERE id_assemblea = %d AND max_risposte > 1", $aid));
	
	foreach($res_multi as $m)
		{
		if (count($_POST["domanda_".$m->ID]) > $m->max_risposte)
			{
			// Mandiamo una mail all'admin, giusto per sicurezza
			$email = "webmaster@airicerca.org";
			$to = $email;
				
			$subject = "Errore durante votazione";

			$body = "L'utente ".$ud->first_name." ".$ud->last_name." (ID:".get_current_user_id().") ha dato ".
				count($_POST["domanda_".$m->ID])." risposte alla domanda ".$m->ID;
			$headers[] = "From: Assemblee AIRIcerca <webmaster@airicerca.org>";
			$headers[] = "Reply-To: webmaster@airicerca.org";
			$headers[] = "Content-Type: text/html";
			$headers[] = "charset=UTF-8";
			$headers[] = "MIME-Version: 1.0";
			$headers[] = "X-Mailer: PHP/".phpversion();

			wp_mail($to, $subject, $body, $headers);			
			
			return("Errore interno.");
			}
		}

	foreach ($_POST as $name => $val)
		{
		if (preg_match("/^domanda_([0-9]+)?/", $name, $matches))
			{
			if (is_array($val)) // Domanda con risposte multiple
				{
				foreach($val as $v)
					{
					$wpdb->insert("wp_assemblee_voti", 
						      array("id_domanda"=>$matches[1], "voto"=>(int)$v, "id_user"=>get_current_user_id(), 
						      	"timestamp"=>current_time('mysql', 1)), array("%d", "%d", "%d", "%s"));
		
					if (!empty($res_del) && $res_del->delega != NULL)
						{
						$wpdb->insert("wp_assemblee_voti", array("id_domanda"=>$matches[1], "voto"=>(int)$v, "id_user"=>$res_del->delega, 
							      "timestamp"=>current_time('mysql', 1)), array("%d", "%d", "%d", "%s"));
						}
					}
				}
			else
				{
				$wpdb->insert("wp_assemblee_voti", 
					      array("id_domanda"=>$matches[1], "voto"=>(int)$val, "id_user"=>get_current_user_id(), "timestamp"=>current_time('mysql', 1)),
				 	      array("%d", "%d", "%d", "%s"));
	
				if (!empty($res_del) && $res_del->delega != NULL)
					{
					$wpdb->insert("wp_assemblee_voti", 
						      array("id_domanda"=>$matches[1], "voto"=>(int)$val, "id_user"=>$res_del->delega, "timestamp"=>current_time('mysql', 1)),
					 	      array("%d", "%d", "%d", "%s"));
					}
				}
			}
		}

	if (!isset($_GET['aid']))
		return("<div style='text-align:center'><img src='http://www.airicerca.org/wp-content/uploads/2014/03/airi-logo-256x62.png' /></div><br />Errore interno.");	

	$txt .= "<div style='text-align:center'><img src='http://www.airicerca.org/wp-content/uploads/2014/03/airi-logo-256x62.png' /></div><br />
		Grazie ".$ud->first_name.",<br />i tuoi voti sono stati registrati.<br />
		<a href='../mostra-assemblea?id=".$aid."' class='button'>Torna all'assemblea</a>";

	return $txt;
	}

function lista_votanti()
	{
	global $wpdb;
	$aid = isset($_GET['aid']) ? (int)$_GET['aid'] : NULL;

	if ($aid == NULL)
		return;

	$res = $wpdb->get_results($wpdb->prepare("SELECT u.ID user_id, COUNT(v.ID) num_voti   
		FROM wp_assemblee_partecipanti p 
		LEFT JOIN wp_users u ON p.id_user = u.ID
		LEFT JOIN wp_assemblee_voti v ON v.id_user = u.ID  
		WHERE p.id_assemblea = %d 
		GROUP BY u.ID ORDER BY u.display_name", $aid));

	$txt = "";

	foreach ($res as $p)
		{
		$user_info = get_userdata($p->user_id);

		$nome = $user_info->first_name." ".$user_info->last_name;

		if ($p->num_voti == 0)
			$col = "red";
		else
			$col = "green";

		$txt .= "<div style='color:$col'>".$nome."</div>";
		}

	return $txt;
	}

add_shortcode('lista-assemblee', 'lista_assemblee');
add_shortcode('lista-partecipanti', 'lista_partecipanti');
add_shortcode('lista-votanti', 'lista_votanti');
add_shortcode('mostra-assemblea', 'mostra_assemblea');
add_shortcode('conferma-partecipazione', 'conferma_partecipazione');
add_shortcode('delega-socio', 'delega_socio');
add_shortcode('comment-og', 'comment_og');
add_shortcode('vota-assemblea', 'vota_assemblea');



/** Opzioni nel menu admin **/

add_action('admin_menu', 'assemblee_menu');

function assemblee_menu()
	{
	# Mettiamo questa come variabile globale in modo da poter caricare il JS solo quando necessario
	global $ass_options_page;
	
	$ass_options_page = add_options_page('Opzioni Assemblee', 'Assemblee', 'manage_options', 'assemblee_options', 'assemblee_options');
	}

function assemblee_options()
	{
	$id = (isset($_GET['id'])) ? (int)$_GET['id'] : -1;
	$act = (isset($_GET['action'])) ? sanitize_text_field($_GET['action']) : "display";
	
	if ($act == "new")
		{
		nuova_assemblea();
		}
	else if ($act == "add")
		{
		aggiungi_assemblea();
		}
	else if ($act == "finalize")
		{
		global $wpdb;

		if ($id == -1)
			wp_die("Errore interno.");

		$res = $wpdb->get_row($wpdb->prepare("SELECT finalizzata FROM wp_assemblee_assemblee WHERE id=%d", $id));

		if (empty($res))
			{
			wp_die("Assemblea inesistente.");
			}
		else
			{
			// Troviamo il quorum necessario per questa assemblea
			$res = $wpdb->get_row("SELECT CEIL(COUNT(u.ID)/2+1) quorum FROM wp_usermeta meta LEFT JOIN wp_users u ON meta.user_id=u.ID LEFT JOIN wp_pmpro_memberships_users pmu ON pmu.user_id = u.ID WHERE meta.meta_key LIKE 'card_number' AND pmu.status LIKE 'active'");

			$wpdb->query($wpdb->prepare("UPDATE wp_assemblee_assemblee SET finalizzata = 1, quorum=%d WHERE id=%d", $res->quorum, $id));
			}

		echo "L'assemblea &egrave; stata finalizzata.<br />";

		echo "<div><a href='".$_SERVER['PHP_SELF']."?page=assemblee_options'>Torna alla lista delle assemblee</a></div>";
		}
	else if ($act == "display")
		{
		if ($id == -1) // Pagina principale
			mostra_lista_assemblee();
		else
			opzioni_assemblea($id);
		}
	else
		{
		echo "Errore interno";
		}
	}

function nuova_assemblea()
	{
	echo "<div class = 'wrap'>
	      <h1>Aggiungi una nuova assemblea</h1>

	     <form action='".$_SERVER["PHP_SELF"]."?page=assemblee_options&action=add' method='post'>
	     <div>Tipo : <select name='tipo'><option value='ordinaria'>Ordinaria</option><option value='straordinaria'>Straordinaria</option></select></div>";
	
	$today = date("Y-m-d");
	$in5days = date("Y-m-d", time() + 5 * 24 * 3600);
	$in8days = date("Y-m-d", time() + 8 * 24 * 3600);
	
	echo "<div>Data di inizio: <input required='required' name='data-inizio' type='date' value='".$today."' /><br />
		Data di fine discussione: <input required='required' name='data-fine-discussione' type='date' value='".$in5days."' />
		Data di fine votazione: <input required='required' name='data-fine-votazione' type='date' value='".$in8days."' /></div><br />";

	echo "<h2>Introduzione</h2>";
	wp_editor(" ", "introduzione", array("textarea_rows" => 5));
	
	echo "<h2>Ordine del giorno</h2>";
	echo "<button id = 'og_add_new' disabled='disabled'>Aggiungi nuovo punto</button>";
	echo "<div id='og'><ol id='og_punti'></ol></div>";

	echo "<h2>Votazioni</h2>";
	echo "<button id = 'vot_add_new' disabled='disabled'>Aggiungi nuova votazione</button>";
	echo "<div id='vot'><ol id='vot_votazioni'></ol></div>";
	
	echo "<input id='nuova_assemblea' type='submit' value='Aggiungi assemblea' /></form>";
	echo "</div>";
	}
	
function aggiungi_assemblea()
	{
	global $wpdb;

	$wpdb->insert('wp_assemblee_assemblee', 
			array('tipo' => $_POST['tipo'], 
			      'date_from' => $_POST['data-inizio'],
			      'date_to' => $_POST['data-fine-discussione'],
			      'vote_date_to' => $_POST['data-fine-votazione'],
			      'introduzione' => $_POST['introduzione']),
			array('%s', '%s', '%s', '%s', '%s'));

	echo "L'assemblea è stata aggiunta nel database. Ora non resta che finalizzarla";
	echo "Torna alla <a href='".$_SERVER["PHP_SELF"]."?page=assemblee_options&action=display'>lista delle assemblee</a>";
	}

// Mostra la lista delle assemblee
function mostra_lista_assemblee()
	{
	global $wpdb;

	echo "<h1>Opzioni assemblee associazione</h1>";
	
	if (!current_user_can('manage_options'))
		{
		wp_die( __( 'Non hai i permessi di accedere a questa pagina.'));
		}

	echo '<div class="wrap">';
	
	$res = $wpdb->get_results("SELECT id, date_from, date_to, tipo, finalizzata, (CURDATE() > date_to) passata, (CURDATE() BETWEEN date_from AND date_to) in_corso
		FROM wp_assemblee_assemblee ORDER BY date_from DESC");

	if (count($res) == 0)
		{
		echo "Non è stata trovata alcuna assemblea.";
		}
	else
		{
		echo "<table id='tabella-assemblee' class='wp-list-table widefat fixed striped pages'>";
		echo "<tr>
			<th>Data</th>
			<th>Tipo</th>
			<th></th>
			</tr>";
		foreach ($res as $a)
			{
			// Possiamo modificare solamente le assemblee future e non finalizzate
			if ($a->passata || $a->in_corso || $a->finalizzata)
				$linkstxt = "<a href='".$_SERVER["PHP_SELF"]."?page=assemblee_options&id=".$a->id."'>Visualizza</a>";
			else
				$linkstxt = "<a style = 'vertical-align: middle;' href='".$_SERVER["PHP_SELF"]."?page=assemblee_options&id=".$a->id."'>Modifica</a>&nbsp;&nbsp;
					<a class = 'button' style = 'vertical-align: middle' href='".$_SERVER['PHP_SELF']."?page=assemblee_options&action=finalize&id=".$a->id.
					"'><strong>Finalizza</strong></a>";
			
			echo "<tr><td>".strftime("%d %B %Y", strtotime($a->date_from))." - ".strftime("%d %B %Y", strtotime($a->date_to))."</td>".
				"<td>".$a->tipo."</td>".
				"<td>$linkstxt</td></tr>";
	 		}
		echo "</table>";
		}
	
	echo "<br /><a class = 'button' href = '".$_SERVER["PHP_SELF"]."?page=assemblee_options&action=new'>Aggiungi assemblea</a>";
		
	echo '</div>';
	}

function opzioni_assemblea($id)
	{
	global $wpdb;
	
	$res = $wpdb->get_row($wpdb->prepare("SELECT id, date_from, date_to, vote_date_to, tipo, introduzione, 
		(CURDATE() > vote_date_to) passata, (CURDATE() BETWEEN date_from AND vote_date_to) in_corso 
		FROM wp_assemblee_assemblee 
		WHERE id=%d", $id));

	$res_og = $wpdb->get_results($wpdb->prepare("SELECT id, titolo, testo 
		FROM wp_assemblee_ordini 
		WHERE id_assemblea=%d ORDER BY ordine", $id));

	if ($res == NULL)
		{
		echo "<br /><div style='display:block; background-color:darkred; padding:.5em; color:white;'><strong>Assemblea non trovata.</strong></div><br />";
		echo "<div><a href='".$_SERVER['PHP_SELF']."?page=assemblee_options'>Torna alla lista delle assemblee</a></div>";
		return;
		}
	
	$dis = ($res->passata || $res->in_corso) ? "disabled='disabled'" : "";
				
	echo "<h1>Assemblea ".$res->tipo." : ".$res->date_from." - ".$res->date_to."</h1>";
		
	if ($res->passata == 1)
		echo "<div style='display:block; background-color:darkorange; padding:.5em; color:white;'>
			<strong>Questa assemblea è terminata, non è più possibile modificarla</strong></div><br />";

	if ($res->in_corso == 1)
		echo "<div style='display:block; background-color:darkorange; padding:.5em; color:white;'>
			<strong>Questa assemblea è attualmente in corso, non è più possibile modificarla.</strong></div><br />";
		
	echo "<div><a href='".$_SERVER['PHP_SELF']."?page=assemblee_options'>Torna alla lista delle assemblee</a></div>";
	
	if ($res->tipo == "ordinaria")
		echo "<div><select name='tipo'><option selected='selected'>Ordinaria</option><option>Straordinaria</option></select></div>";
	else
		echo "<div><select name='tipo'><option>Ordinaria</option><option selected='selected'>Straordinaria</option></select></div>";
	
	echo "<div>Data di inizio: <input name='data-inizio' type='date' value='".$res->date_from."' $dis /><br />
		Data di fine discussione: <input name='data-fine-discussione' type='date' value='".$res->date_to."' $dis /></div>
		Data di fine votazione: <input name='data-fine-votazione' type='date' value='".$res->vote_date_to."' $dis /></div><br />";

	echo "<h2>Introduzione</h2>";
	if (!($res->passata || $res->in_corso))
		{
		wp_editor($res->introduzione, "introduzione");
		}
	else
		{
		echo "<div style='color:darkgray;'>".$res->introduzione."</div>";
		}
		
	echo "<h2>Ordine del giorno</h2>";
	
	echo "<ol>";
	foreach ($res_og as $og)
		{
		echo "<li><b>".$og->titolo."</b><br />".$og->testo."</li>";
		}
	echo "</ol>";

	echo "</div>";
	}

function assemblee_load_scripts($hook)
	{
	global $ass_options_page;
	
	if ($hook != $ass_options_page)
		{
		return;
		}
	
	wp_enqueue_script('assemblee-js', plugin_dir_url(__FILE__).'js/assemblee-admin.js', array("jquery"));
	}
	
add_action('admin_enqueue_scripts', 'assemblee_load_scripts');
?>
