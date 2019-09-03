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

jQuery(document).ready(checkData);

var urlParams;
// From: http://stackoverflow.com/questions/901115/how-can-i-get-query-string-values-in-javascript
(window.onpopstate = function () {
	var match,
	pl = /\+/g,  // Regex for replacing addition symbol with a space
	search = /([^&=]+)=?([^&]*)/g,
	decode = function (s) { return decodeURIComponent(s.replace(pl, " ")); },
	query = window.location.search.substring(1);

	urlParams = {};
	while (match = search.exec(query))
	urlParams[decode(match[1])] = decode(match[2]);
})();

if (urlParams.ps==1)
	{
	var input, autocomplete;
	}
	
function checkData()
	{
	jQuery(function($)
		{
		if (urlParams.ps == 1)
			{
			function checkemail()
				{
				if ($('#email').val().length && $('#confirmemail').val().length)
					{
					if ($('#email').val() != $('#confirmemail').val())
						{
						$('#invalidEmail').fadeIn(400);
						}
					else
						{
						$('#invalidEmail').fadeOut(400);
						}
					}
				else
					{
					$('#invalidEmail').fadeOut(400);
					}
				}

			options =
				{
				language: 'it',
				types: ['(cities)']
				}

			input = /** @type {!HTMLInputElement} */(document.getElementById('citta'));
			autocomplete = new google.maps.places.Autocomplete(input, options);

			$('select#professione').change(function()
				{
				if ($(this).val() == "Studente") // Studente ultimo anno uni
					{
					$("#affiliazione_txt").text("Università/corso");
					$("#ambito_ricerca").fadeOut(400);
					$("#cvdiv").fadeOut(400);
					}
				else
					{
					$("#affiliazione_txt").text("Affiliazione");
					$("#ambito_ricerca").fadeIn(400);
					$("#cvdiv").fadeIn(400);
					}
				})
			
			// Forziamo un evento di change, in modo da avere i testi giusti nel caso che l'opzione
			// preselezionata sia "studente"
			$("select#professione").trigger("change");

			$("#email").blur(checkemail);
			$("#confirmemail").blur(checkemail);

			$("#codice_fiscale").blur(function(){
				l = $('#codice_fiscale').val().length;
				
				if (l && l!=16)
					$('#invalidCF').fadeIn(400);
				else
					$('#invalidCF').fadeOut(400);
				})

			$('#form-registrazione').submit(function() 
				{
				res = true;
				
				if ($("#nome").val().trim() == "" ||
					$("#cognome").val().trim() == "" ||
					$("#email").val().trim() == "" ||
					$("#affiliazione").val().trim() == "")
						{
						$('#fillAll').fadeIn(400);
						res = false; 
						}
						
				var place = autocomplete.getPlace();
				$('#lat').val(place.geometry.location.lat());
				$('#lng').val(place.geometry.location.lng());

				return res; // se restituiamo false il form non sarà inviato
				});

			// Controlliamo che la File API sia supportata
			if (window.FileReader && window.File && window.FileList && window.Blob)
				{
				$('#prova').bind('change', function() 
					{
					var maxfilesize = 1000000 
					if (this.files[0].size > maxfilesize)
						{
						$('#invalidFile').fadeIn(400);
						}
					else
						{
						$('#invalidFile').fadeOut(400);
						}
					});
				}
			}
		});
	}
