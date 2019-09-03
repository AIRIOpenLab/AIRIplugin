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
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

jQuery(document).ready(bandiInsertReady);

function bandiInsertReady()
	{
	jQuery(function($)
		{
		$.ajax({
			type: "POST",
			dataType: "json",
			url: scriptVars.ajaxURL,
			data: {
				action: "act_getPaesi",
				all: 1
				},
			success : function(res)
				{
				sel = $('#bando_paesi');
				sel.attr("placeholder", "Scrivi il nome del paese");
				sel.attr("size", "60");
				
				sel.find('option').remove();
				sel.append($("<option>").text(""));
				
				for(i = 0; i < res.paesi.length; i++)
					{
					opt = $("<option>").attr('value', res.paesi[i]);
					opt.attr('data-alternative-spellings', res.alternative[i]).attr('data-relevancy-booster', 
						res.pesi[i]).attr('codice-paese', res.codici[i]).attr('id-paese', res.ids[i]);
					opt.html(res.paesi[i]);
					
					sel.append(opt);
					};

				sel.selectToAutocomplete();
				
				$(".PaeseCloseButton").click(function()
					{
					$(this).parent().next("input[type=hidden]").remove();
			
					$(this).parent().remove();
					});


				
				sel.change(function()
					{
					$('#noCountrySpan').hide();

					var paese = $("#bando_paesi option:selected").text();
					var codice = $("#bando_paesi option:selected").attr("codice-paese");
					var id = $("#bando_paesi option:selected").attr("id-paese");
					
					if ($('#paese_'+codice).length == 0 && codice != undefined)
						{
						var newCountry = $("<span id='paese_"+codice+"'>");
						var newCountryHidden = $("<input type='hidden' name='bando_id_paesi[]' value='"+id+"' />");
						
						newCountry.html("<img src='"+scriptVars.flagsURL+codice+".png' style='vertical-align:middle' /> " + paese +
							"<span class='PaeseCloseButton'>X</span>").addClass("nomePaese");
					
						$("#nomipaesi").append(newCountry).append(newCountryHidden);

						newCountry.children(".PaeseCloseButton").click(function()
								{
								$(this).parent().next("input[type=hidden]").remove();
								
								$(this).parent().remove();

								if ($("#nomipaesi span").length == 1)
									$("#noCountrySpan").show();
								});
						}

					$("#bando_paesi").val("");
					$("#bando_paesi").next().val("");
					});
				},
			error: function(xhr, ajaxOptions, thrownError)
				{
				alert("ERRORE: " + thrownError + " - Status: " + xhr.status);
				}
			});
		})
	}
