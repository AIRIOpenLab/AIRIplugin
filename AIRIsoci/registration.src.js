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

function checkData()
	{
	jQuery(function($)
		{
		res = true;
		
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

		$("#email").blur(function()
			{
			if (! /^.+@.+\..+$/.test($('#email').val()) )
				$('#invalidEmail').fadeIn(400);
			else
				$('#invalidEmail').fadeOut(400);
			})

		$("#codice_fiscale").blur(function(){
			if ($('#codice_fiscale').val().length!=16)
				$('#invalidCF').fadeIn(400);
			else
				$('#invalidCF').fadeOut(400);
			})

		$('#form-registrazione').submit(function() 
			{
			if ($("#nome").val().trim() == "" ||
				$("#cognome").val().trim() == "" ||
				$("#email").val().trim() == "" ||
				$("#affiliazione").val().trim() == "")
					{
					$('#fillAll').fadeIn(400);
					res = false; 
					}
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
 		});
	}
