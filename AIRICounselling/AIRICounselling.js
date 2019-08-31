/*   Copyright (C) 2017 Nicola Romanò (romano.nicola@gmail.com)

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

jQuery(document).ready(mapReady);
	
function mapReady()
	{
	jQuery(function($)
		{
		// Recupera la lista dei centri
		$.ajax({
			type: "POST",
			dataType: "json",
			url: "http://www.airicerca.org/wp-admin/admin-ajax.php",
			data: {
				action: "AIRICounselling_get_centres"
				},
			success : function(res)
				{
				var mapOptions = {
						center:{lat:10,lng:30},
						zoom:2,
						streetViewControl:0,
						mapTypeControl:0,
						mapTypeId:google.maps.MapTypeId.ROADMAP};
						
				var map = new google.maps.Map(document.getElementById('map'), mapOptions);
				for (var m=0; m<res.descrizioni.length; m++)
					{
					var sito = (res.siti[m] == null) ? "" : 
						"<strong>Website: </strong><a href='" + res.siti[m] + "' target='_blank'>" + res.siti[m] + "</a>";
					var tel = (res.tel[m] == null) ? "" : "<strong>Tel: </strong>" + res.tel[m];
					var descrizione = (res.descrizioni[m] == null) ? "" : res.descrizioni[m] + "<br /><br />";
					marker = new google.maps.Marker
						({
						position: new google.maps.LatLng(parseInt(res.coord[m][0]), parseInt(res.coord[m][1])),
	                    			map: map,
	                    			icon: "http://www.airicerca.org/wp-content/media/pinCounselling.png",
	                    			title: res.nomi[m],
	                    			content: "<h1 style='font-size:medium'>" + res.nomi[m] + "</h1>" + descrizione + tel + "<br />" + sito
	                    			});
	           	       		
	           	       		var infowindow = new google.maps.InfoWindow();
	           	       		google.maps.event.addListener(marker, 'click', function()
						{
						infowindow.setContent(this.content);
						infowindow.open(map, this);
 						});  
					}	
				},
			error: function(xhr, ajaxOptions, thrownError)
				{
				alert("ERRORE: " + thrownError + " - Status: " + xhr.status);
				}
			});
		});
	}

