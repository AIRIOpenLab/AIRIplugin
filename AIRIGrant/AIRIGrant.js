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

jQuery(document).ready(grantReady);

function grantReady()
	{
	jQuery(function($)
		{
		$("#username").autocomplete(
			{
			serviceUrl: 'http://www.airicerca.org/wp-admin/admin-ajax.php',
			type: 'POST',
			params: {action:'AIRIGrant_get_user'}
			});
		
		$("[id^=rem_revisore_]").click(function()
			{
			tokens = this.id.split("_");
			res = confirm("Sei sicuro di voler eliminare questo revisore? (id="+tokens[2]+")");
			if (res === true)
				{
				$.ajax({
					type:"POST",
					url: "http://www.airicerca.org/wp-admin/admin-ajax.php",
					data: {
						action: "AIRIGrant_rem_revisore",
						id: tokens[2]
						},
					success : function(res)
						{
						$("#revisore_"+tokens[2]).fadeOut();
						},
					error: function(xhr, ajaxOptions, thrownError)
						{
						alert("ERRORE: " + thrownError + " - Status: " + xhr.status);
						}
					});
				}
			});
			
		$("#add_revis").click(function()
			{
			name = $("#username").val();
			tokens = name.split("=");
			
			$.ajax({
				type:"POST",
				url: "http://www.airicerca.org/wp-admin/admin-ajax.php",
				data: {
					action: "AIRIGrant_add_revisore",
					id: parseInt(tokens[1]),
					exp: $("#description").val()
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
			});
		})
	}