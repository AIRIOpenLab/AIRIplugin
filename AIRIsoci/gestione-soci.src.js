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

jQuery(document).ready(gestioneReady);

function gestioneReady()
	{
	var mostra = 1;
	
	var tbCol = {
		id: 0,
		nome: 1,
		email: 2,
		tipo: 3,
		professione: 4,
		provaPag: 5,
		tessera: 6,
		pagato: 7,
		approvato: 8,
		cv: 9,
		candidatura: 10,
		affiliazione: 11,
		ricercatore: 12,
		registrazione: 13
		};
		
	var colors = {
		socio: "#D0DC23",
		amico: "#558DA5"
		};
		
	jQuery(function($)
		{
		$.fn.dataTableExt.afnFiltering.push(
			function(oSettings, aData, iDataIndex)
			{
			var amico = aData[tbCol.tipo] == "Amico" ? 1 : 0;
			var approvato = aData[tbCol.tessera] == "Non generato" ? 0: 1;
			var ricercatore = aData[tbCol.ricercatore];
			// Mostra tutti
			if (mostra == 0)
				{
				return true;
				}
			else if (amico && mostra == 1) // Mostra solo soci
				{
				return false;
				}
			else if (mostra == 2 && (amico || ricercatore == 0)) // Mostra solo soci ricercatori
				{
				return false;
				}
			else if (mostra == 3 && (amico || ricercatore == 1)) // Mostra solo soci non ricercatori
				{
				return false;
				}
			else if (mostra == 4) // Mostra solo soci non approvati
				{
				if (amico)
					return false;
				else if (!amico && approvato == 1)
					return false;
				}
			else if (!amico && mostra == 5) // Mostra solo amici
				{
				return false;
				}

			return true;
			});
		
		function format(data)
			{
			var txt;
			
			txt = "Indirizzo e-mail: <a href='mailto:"+data[tbCol.email]+"'>"+data[tbCol.email]+"</a><br />";
			if (data[tbCol.affiliazione] != "")
				txt += "Affiliazione: " + data[tbCol.affiliazione] + "<br />";
			
			if (data[tbCol.cv])
				{
				txt += "<br />CV:<br /><textarea disabled='disabled' cols='100' rows='5'>"+data[tbCol.cv]+"</textarea>";
				}
			else
				{
				txt += "<br /><em>CV non inserito</em>";
				}
			
			if (data[tbCol.candidatura])
				{
				txt += "<br />Candidatura:<br /><textarea disabled='disabled' cols='100' rows='5'>"+data[tbCol.candidatura]+"</textarea>";
				}
			else
				{
				txt += "<br /><em>Candidatura non inserita</em><br />";
				}
			
			txt += "<button id='delete_user_"+data[tbCol.id]+"' style='background-color:red; padding:0.6em;'>Cancella utente</button>";
			if (data[tbCol.tessera] == "Non generato")
				txt += "<button id='confirm_user_"+data[tbCol.id]+"' style='background-color:green; padding:0.6em;'>Conferma utente</button>";
			return txt;
			}

		table = $('#subscribers-table').DataTable({
			"language" : {"url": "http://cdn.datatables.net/plug-ins/f2c75b7247b/i18n/Italian.json"},
			"pageLength" : 30,
			"lengthMenu": [10, 30, 100],
			"order": [[1, "asc"]],
			"fnRowCallback": function(nRow, aData, iDisplayIndex, iDisplayIndexFull) 
					{
					$(nRow).css('cursor', 'pointer');
					
					if (aData[tbCol.tipo] == "Amico")
						{
						$('td', nRow).css('background-color', colors.amico);
						}
					else if (aData[tbCol.tessera] != "Non generato")
						{
						$('td', nRow).css('background-color', colors.socio);
						}
					},
			"columnDefs": [
				{
				"targets": [tbCol.pagato],
				"visible": false
				},
				{
				"targets": [tbCol.approvato],
				"visible": false
				},
				{
				"targets": [tbCol.email],
				"visible": false
				},
				{
				"targets": [tbCol.cv],
				"visible": false
				},
				{
				"targets": [tbCol.candidatura],
				"visible": false
				},
				{
				"targets": [tbCol.affiliazione],
				"visible": false
				},
				{
				"targets": [tbCol.ricercatore],
				"visible": false
				},
				{
			"responsive": {
				details:
					{
					type: 'column',
					target: 'tr'
					}
				}
			});
			
		$('#subscribers-table tbody').on('click', 'td', function ()
			{
			var tr = $(this).closest('tr');
			var row = table.row(tr);

			if (row.child.isShown())
				{
				row.child.hide();
				tr.removeClass('shown');
				}
			else 
				{
				// Open this row
				row.child(format(row.data())).show();
				tr.addClass('shown');
				$("[id^=delete_user_]").click(function(){
					tokens = this.id.split("_");
					res = confirm("Sei sicuro di voler eliminare questo utente?\nTutti i dati dell'utente verranno eliminati dal database (id="+tokens[2]+")");
					if (res === true)
						{
						$.ajax({
							type:"POST",
							url: "http://www.airicerca.org/wp-admin/admin-ajax.php",
							data: {
								action: "act_rem_user",
								id: tokens[2]
								},
							success : function(res)
								{
								location.reload();
								},
							error: function(xhr, ajaxOptions, thrownError)
								{
								alert("ERRORE: " + thrownError + " - Status: " + xhr.status);
								}
							});
						}
					});
					
				$("[id^=confirm_user_]").click(function(){
					tokens = this.id.split("_");
					$(this).prop("disabled", true)
					$.ajax({
						type:"POST",
						url: "http://www.airicerca.org/wp-admin/admin-ajax.php",
						data: {
							action: "act_approve_user",
							id: tokens[2]
							},
						success : function(res)
							{
							$(this).prop("disabled", false)
							$('#subscribers-table').DataTable().draw(false);
					
							var oTable = $('#subscribers-table').dataTable();
					
							},
						error: function(xhr, ajaxOptions, thrownError)
							{
							$(this).prop("disabled", false)
							alert("ERRORE: " + thrownError + " - Status: " + xhr.status);
							}
						});
					});
				}
			});

		$('#mostra').change(function()
			{
			mostra = parseInt($('#mostra').val());
			$('#subscribers-table').DataTable().draw();
			})
		});
	}
