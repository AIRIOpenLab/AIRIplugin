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

jQuery(document).ready(bandiReady);

function bandiReady()
	{
	var mostra = 1;
	
	var tbCol = { 
		nome: 0,
		ente: 1,
		destinatari: 2,
		apertura: 3,
		aperturasort : 4,
		chiusurasort : 5,
		paese: 6,
		tools: 7
		};
		
	var palette = {
		scadutoBg: "#EEE"
		}
		
	jQuery(function($)
		{
		// See https://datatables.net/forums/discussion/21940/how-to-pass-new-post-parameters-on-ajax-reload
		function buildAJAXData()
			{
			var settings = $("#tabella-bandi").dataTable().fnSettings();
			console.log(settings);
			var country = $("#lista-paesi-search").val();
			var dest = $("#lista-destinatari-search").val();

			if (country == null)
				country = -1;
			if (dest == null)
				dest = -1;
			
			var obj = {
				"action" : "getBandi",
				"sa": $('#showall').is(":checked")|0, // |0 to cast to int
				"country": country,
				"dest": dest,

				// Default params
				"search" : settings.oPreviousSearch.sSearch,
				"draw" : settings.iDraw,
				"start" : settings._iDisplayStart,
				"length" : settings._iDisplayLength,
				"columns" : "",
				"order": ""};
		
			obj.sorting = settings.aaSorting[0];

			return obj;
			}

		table = $('#tabella-bandi').DataTable({
			"language" : {"url": "http://cdn.datatables.net/plug-ins/f2c75b7247b/i18n/Italian.json"},
			"bProcessing" : "true",
			"bServerSide" : "true",
			"searchDelay" : 1000,
			"ajax": {
				"url" : DataTablesLoadData.ajaxURL,
				"data" : buildAJAXData,
				"type": "POST",
				},
			"pageLength" : 10,
			"lengthMenu": [10, 30, 100],
			"order": [[tbCol.chiusurasort, "desc"]],
			"columnDefs": [
				{
				"targets": [tbCol.nome],
				"width": "50em"
				},
				{
				"targets": [tbCol.paese],
				"sortable": false,
				"visible": true
				},
				{
				"targets": [tbCol.destinatari],
				"sortable": false
				},
				{
				"targets": [tbCol.aperturasort],
				"visible": false
				},
				{
				"targets": [tbCol.chiusurasort],
				"visible": false
				},
				{
				"targets": [tbCol.tools],
				"sortable": false
				}],
			"fnRowCallback": function(nRow, aData, iDisplayIndex, iDisplayIndexFull) 
					{
					//$(nRow).css('cursor', 'pointer');

					if (aData[tbCol.apertura].indexOf("SCADUTO") != -1)
						{
						$('td', nRow).css('background-color', palette.scadutoBg);
						}
					},
			"responsive": {
				details:
					{
					type: 'column',
					target: 'tr'
					}
				},
			"sDom": '<"#extraTableControls">frtip',
			fnDrawCallback: function (oSettings) 
				{
				if ($("#extraTableControls").children().length == 0)
					{
					$("div#extraTableControls").css("float", "left");
					var paesitxt = $('<span>Mostra solo bandi in: </span>');
					var paesi = $("<select id='lista-paesi-search'>");
					$("<option />", {value: -1, text: "Tutti i Paesi"}).appendTo(paesi);
					var desttxt = $('<br /><span>Destinatari: </span>');
					var dest = $("<select id='lista-destinatari-search'>");
					$("<option />", {value: -1, text: "Tutti"}).appendTo(dest);
					$("<option />", {value: 1, text: "Studenti"}).appendTo(dest);
					$("<option />", {value: 2, text: "Laureati"}).appendTo(dest);
					$("<option />", {value: 3, text: "Dottorandi"}).appendTo(dest);
					$("<option />", {value: 4, text: "Postdoc"}).appendTo(dest);
					$("<option />", {value: 5, text: "Ricercatori"}).appendTo(dest);

					$("#extraTableControls").append(paesitxt);
					$("#extraTableControls").append(paesi);
					$("#extraTableControls").append(desttxt);
					$("#extraTableControls").append(dest);

					$("#lista-destinatari-search").change(function()
						{
						table.ajax.reload();
						})

					if ($('#lista-paesi-search').size() == 1)
						{
						$.ajax({
							type: "POST",
							dataType: "json",
							url: DataTablesLoadData.ajaxURL,
							data: {
								action: "act_getPaesi"
								},
							success : function(res)
								{
								sel = $('#lista-paesi-search');
								for(i = 0; i < res.paesi.length; i++)
									{
									sel.append($("<option>").attr('value', res.ids[i]).text(res.paesi[i]));
									};
							
								$(sel).change(function()
									{
									table.ajax.reload();
									});
								},
							error: function(xhr, ajaxOptions, thrownError)
								{
								alert("ERRORE: " + thrownError + " - Status: " + xhr.status);
								}
							});
						}
					}
				}
			});

		$("#showall").change(function()
			{
			table.ajax.reload();
			})
		})
	}
