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

jQuery(document).ready(statistiche_ready);

function statistiche_ready()
	{
	// NOTA IMPORTANTE: non usare l'esempio come su https://developers.google.com/chart/interactive/docs/quick_start
	// perché cancella il contenuto della pagina
	// Vedi http://stackoverflow.com/questions/9519673/why-does-google-load-cause-my-page-to-go-blank 
	// per spiegazione e soluzione
	google.load('visualization', '1.0', 
		{
		'packages':['corechart'],
		'callback': drawChart
		});

	// Set a callback to run when the Google Visualization API is loaded.
	//google.setOnLoadCallback(drawChart);

	// Callback that creates and populates a data table,
	// instantiates the pie chart, passes in the data and
	// draws it.
	}
	
function drawChart() 
	{
	jQuery(function($)
		{
		$.ajax({
			type:"POST",
			url: DataTablesLoadData.ajaxURL,
			data: {
				action: "act_stats"
				},
			dataType: "json",
			success : function(res)
				{
				$("#loading").fadeOut(500);
				
				$("#numRic").text(res.numRic + " ricercatori");
				$("#numPhD").text(res.numPhD + " dottorandi");
				$("#histoTitle").html("<strong>Istogramma iscrizioni</strong> (dall'apertura pubblica delle iscrizioni)");
				
				var numSociMap = 0;
				for (i=0; i<res.countryCounts.length; i++)
					numSociMap += parseInt(res.countryCounts[i]);
					
				$("#MapTitle").html("<strong>Mappa delle affiliazioni dei soci</strong> <span style='font-size:0.8em;'>(non si aggiorna automaticamente, conta attualmente "+numSociMap+" soci)</span>");
				
				// Create the data table.
				var data = new google.visualization.DataTable();
				data.addColumn('string', 'Area');
				data.addColumn('number', 'Numero');
				
				var ambiti = Array("Scienze mediche/biologiche", "Scienze chimiche/fisiche/geologiche", "Scienze umane", 
					"Scienze giuridiche/economiche", "Ingegneria", "Architettura/design", "Matematica")

				for (i=0; i<ambiti.length; i++)
					{
					data.addRow([ambiti[i], res.countsRic[i]]);
					}

				// Set chart options
				var options = {'title':'Ricercatori',
					'width': 600,
					'height': 350};

				// Instantiate and draw our chart, passing in some options.
				var chart = new google.visualization.PieChart(document.getElementById('perc_ric_div'));
				chart.draw(data, options);

				// DOTTORANDI

				data = new google.visualization.DataTable();
				data.addColumn('string', 'Area');
				data.addColumn('number', 'Numero');

				for (i=0; i<ambiti.length; i++)
					{
					data.addRow([ambiti[i], res.countsPhD[i]]);
					}

				var options = {'title':'Dottorandi',
					'width': 600,
					'height': 350};

				var chart = new google.visualization.PieChart(document.getElementById('perc_dott_div'));
				chart.draw(data, options);

				// ISTOGRAMMA
				data = new google.visualization.DataTable();
				data.addColumn('date', 'DataReg');
				data.addColumn('number', 'Counts');

				for (i=0; i<res.newRegDate.length; i++)
					{
					data.addRow([new Date(res.newRegDate[i]), parseInt(res.newRegNum[i])]);
					}

				var options = {'title':'Numero di iscritti',
					'width': '100%',
					'height': 400,
					'legend': "none",
					'bar' : {'groupWidth': '100%'}
					};

				var chart = new google.visualization.ColumnChart(document.getElementById('histo_iscrizioni_div'));
				chart.draw(data, options);
				
				// Mappa
				data = new google.visualization.DataTable();
				data.addColumn('string', 'Paese');
				data.addColumn('number', 'Numero di soci');

				for (i=0; i<res.countryCounts.length; i++)
					{
					data.addRow([res.countryName[i], parseInt(res.countryCounts[i])]);
					console.log([res.countryName[i], parseInt(res.countryCounts[i])]);
					}
				
				var options = {'title':'I Soci di AIRIcerca',
					'width': '100%',
					'height': 400,
					colorAxis: {colors: ['#00853f', '#231be3']}
					};

				var chart = new google.visualization.GeoChart(document.getElementById('mappa_soci_div'));
				chart.draw(data, options);
				}
			})
		})
	};
