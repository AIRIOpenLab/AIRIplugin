/*   Copyright (C) 2016 Nicola Romanò (romano.nicola@gmail.com)

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

jQuery(document).ready(portalReady);
var informaCurrent = 0, informaNum = 5;
var socialCurrent = 0, socialNum = 10;

function portalReady()
	{
	jQuery(function($)
		{
		function showAIRInforma()
			{
			$("#AIRInformaLatest").children().hide();
			$($("#AIRInformaLatest").children()[informaCurrent]).fadeIn(300);
			informaCurrent++;
			informaCurrent = informaCurrent % informaNum;
			}

		function showAIRISocial()
			{
			$("#AIRISocialLatest").children().hide();
			$($("#AIRISocialLatest").children()[socialCurrent]).fadeIn(300);
			$($("#AIRISocialLatest").children()[socialCurrent+1]).fadeIn(300);
			socialCurrent+= 2;
			socialCurrent = socialCurrent % socialNum;
			}

		// Trova gli ultimi articoli AIRInforma
		$.ajax({
			type: "POST",
			dataType: "json",
			url: DataTablesLoadData.ajaxURL,
			data: {
				action: "AIRInforma_get_latest"
				},
			success : function(res)
				{
				// Remove spinner
				$("#AIRInformaLatest").children(".ap_spinner").remove();
				for (var i = 0; i < res.length; i++)
					{
					var r = res[i];

					var artdiv = $("<div class='informa_img_wrapper'>");
					var arttitle = $("<div>").html("<a href='"+r.post_URL+"' target='_blank'><h1>"+r.post_title+"</h1></a>");
					var artimg = $("<img class='informa_img' src='"+r.post_image+"' />");
					var arttxt = $("<div>").html(r.post_incipit);

					artdiv.append(arttitle).append(artimg).append(arttxt);
					artdiv.hide();

					$("#AIRInformaLatest").append(artdiv);
					};

				showAIRInforma();
				setInterval(showAIRInforma, 5000);
				},
			error: function(xhr, ajaxOptions, thrownError)
				{
				alert("ERRORE: " + thrownError + " - Status: " + xhr.status);
				}
			});

		// Trova gli ultimi articoli AIRISocial
		$.ajax({
			type: "POST",
			dataType: "json",
			url: DataTablesLoadData.ajaxURL,
			data: {
				action: "AIRISocial_get_latest"
				},
			success : function(res)
				{
				// Remove spinner
				$("#AIRISocialLatest").children(".ap_spinner").remove();
				for (var i = 0; i < res.length; i++)
					{
					var r = res[i];

					var artdiv = $("<div class='social_img_wrapper'>");
					var arttitle = $("<div>").html("<a href='"+r.post_URL+"' target='_blank'><h2>"+r.post_title+"</h2></a>");
					var artimg = $("<img class='social_img' src='"+r.post_image+"' />");
					var arttxt = $("<div>").html(r.post_incipit);

					artdiv.append(arttitle).append(artimg).append(arttxt);
					artdiv.hide();

					$("#AIRISocialLatest").append(artdiv);
					};

				showAIRISocial();
				setInterval(showAIRISocial, 5000);
				},
			error: function(xhr, ajaxOptions, thrownError)
				{
				alert("ERRORE: " + thrownError + " - Status: " + xhr.status);
				}
			});

		});
	}

