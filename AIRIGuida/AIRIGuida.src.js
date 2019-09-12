jQuery(document).ready(initMap);

function initMap() {
	map = L.map('map').setView([10, 30], 2);
	L.tileLayer('https://tile.thunderforest.com/neighbourhood/{z}/{x}/{y}.png?apikey={apiKey}',
			{ 	attribution: 'Map data &copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a> contributors, <a href="https://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, Imagery © <a href="https://www.thunderforest.com/">Thunderforest</a>',
				maxZoom: 18,
				id: 'mapbox.streets',
				apiKey: Leaflet.key}).addTo(map);
	mP=Array([53.4, -3],[41.9, 12.5],[52.09, 5.12],[-37.82, 144.97],[41.3, 2.18],[45.46, 9.18],[44.66, 10.92],[44.4, 11.4],[51.5, 0.12],[60.17, 24.9],[45.2, 5.7],[57.71, 11.97],[47.7, 9.2],[55.95, -3.18],[59.85, 17.63],[43.71, 7.26],[53.48, -2.24],[48.8, 2.3],[48.2, 16.37]);
	mT=Array("Liverpool","Roma","Utrecht","Melbourne", "Barcellona", "Milano", "Modena", "Bologna", "Londra","Helsinki","Grenoble", "Gothenburg", "Konstanz","Edinburgh", "Uppsala","Nice", "Manchester", "Paris", "Vienna");
	enable=Array(1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1);
	
	for(i=0;i<mP.length;i++) {
		var a, e;
		enable[i] ? (a="http://airicerca.org/wp-content/uploads/2015/08/AIRIIconSmall.png",e='<a href="'+mT[i]+'">'+mT[i]+'</a>') 
				  : (a="http://airicerca.org/wp-content/uploads/2015/08/AIRIIconSmallBW.png",e=mT[i]+" - COMING SOON");
		
		icon = L.icon({iconUrl: a,iconAnchor: [10, 0]});
		marker = L.marker(mP[i], {icon: icon}).addTo(map);
		popup = L.popup();
		popup.setLatLng(mP[i]);
		popup.setContent(e);
		marker.bindPopup(popup);
	}
}