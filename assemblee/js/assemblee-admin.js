/*	Copyright 2015 Nicola Romanò (romano.nicola@gmail.com)

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

jQuery(document).ready(function($)
	{
	$("#og_add_new").prop("disabled", false);
	$("#vot_add_new").prop("disabled", false);

	var og_i = 1, vot_i = 1;
	
	$("#og_add_new").click(
		function(){
		$("#og_punti").append("<li><label for='og_titolo_"+og_i+"'>Titolo:</label> <input id='og_titolo_"+og_i+"' placeholder='Titolo del punto'/><br />" +
			"<textarea id='og_descrizione_"+og_i+"' cols = '50' rows = '5' placeholder='Descrizione'></textarea><br />" +
			"<button id = 'og_rimuovi_punto"+og_i+"'>Elimina</button></li>");
			
		$("#og_rimuovi_punto"+og_i).click(
			function(){
				$(this).parent().remove();
			})
		og_i++;
		});
		
	$("#vot_add_new").click(
		function(){
		
		var litag = "<li>Tipo: <select id='votetype_"+vot_i+"'>"+
			"<option value='unica'>Scelta unica</option><option value='multipla'>Scelta multipla</option></select><br />";
		
		litag += "<textarea cols='40' rows='5' placeholder='Testo della domanda'></textarea><br />";

		for (v=1; v<6; v++)
			{
			litag += "<input type='radio' name='voto_"+vot_i+"_opzioni' value = 'v"+vot_i+"_o"+v+"' /><input id='voto"+vot_i+
				"_opzione"+v+"' placeholder = 'Risposta " + v + "'/><br />";
			}

		litag += "<button id = 'vot_rimuovi_voto"+vot_i+"'>Elimina</button></li>"
		$("#vot_votazioni").append(litag);

		// Bottone "Rimuovi" votazione
		$("#vot_rimuovi_voto"+vot_i).click(
			function(){
				$(this).parent().remove();
			})

		// Cambiamo il tipo di votazione risposta singola/multipla (checkbox a radiobutton)
		$('[id^=votetype_]').change(function() {
			var tokens = $(this).attr("id").split("_");
			id = tokens[1];
			console.log(id);
			var checkbox = $('#voto_'+id+'_opzioni');
		
			checkbox.replaceWith($("<input>",
				{
				type:'radio',
				name: checkbox.attr('name'),
				value: checkbox.attr('value')
				}));
			});

		vot_i++;
		});
	})
