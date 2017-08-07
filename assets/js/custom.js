"use strict";

// global variables
var map;
var lat = 50.83641;
var lng = 12.9276;
var initzoom = 12;
var gasStationIconOpen;
var gasStationIconClosed;
var newMarker;
var markers;
var lat_clicked;
var lng_clicked;
var latlng_clicked;

// function to initialize and create leaflet map
function initMap() {

  // setup a marker group
  markers = L.markerClusterGroup({
    iconCreateFunction: function (cluster) {
			var markers = cluster.getAllChildMarkers();
			var n = 0;
			for (var i = 0; i < markers.length; i++) {
				n += markers[i].number;
			}
			return L.divIcon({ html: cluster.getChildCount(), className: 'cluster', iconSize: L.point(25, 25) });
		}
  });

  // set up the map
  map = new L.Map('map', {
    center: [lat, lng],
    zoom: initzoom
  });

  // define custom marker:  gas station open
  gasStationIconOpen = L.icon({
    iconUrl: 'assets/img/marker-icon-open.png',
    iconSize: [42, 42], // size of the icon
    iconAnchor: [21, 42],
    popupAnchor: [0, -15]
  });

  // define custom marker:  gas station closed
  gasStationIconClosed = L.icon({
    iconUrl: 'assets/img/marker-icon-closed.png',
    iconSize: [42, 42], // size of the icon
    iconAnchor: [21, 42],
    popupAnchor: [0, -15]
  });

  // define layer for map
  var layer = L.tileLayer('https://api.tiles.mapbox.com/v4/{id}/{z}/{x}/{y}.png?access_token=pk.eyJ1IjoibWFwYm94IiwiYSI6ImNpejY4NXVycTA2emYycXBndHRqcmZ3N3gifQ.rJcFIG214AriISLbB6B5aw', {
    attribution: 'Map data &copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors, ' +
      '<a href="http://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, ' +
      'Imagery © <a href="http://mapbox.com">Mapbox</a>',
      id: 'mapbox.streets'});

      // add layer to map
  map.addLayer(layer);
  // add click function for map
  map.on('click', onMapClick);
}

function onMapClick(e) {
  // check if marker for surrounding area search exists on map
  if (newMarker) {
    // remove marker
    map.removeLayer(newMarker);
  }

  // remove all custom layers
  markers.clearLayers();

  // set new surrounding area search marker
  newMarker = new L.marker(e.latlng).addTo(map);

  // retrieve lat and lng values from click event
  lat_clicked = e.latlng.lat;
  lng_clicked = e.latlng.lng;

  // insert values into readonly input fields for coordinates
  document.getElementById('latitude').value = lat_clicked;
  document.getElementById('longitude').value = lng_clicked;

  // enable button for surrounding area search
  document.getElementById('btn-umkreissuche').disabled = false;
  // disable all other buttons (snapshot and price trend)
  document.getElementById('btn-snapshot').disabled = true;
  document.getElementById('btn-preisabfrage').disabled = true;

  // clear all other (readonly) input fields (number of gas stations for snapshot and tankerkoenig ID for price trend)
  document.getElementById('anzahlTankstellen').value = "";
  document.getElementById('idTankerkoenig').value = "";

  // hide (and remove) table title/container/contents
  document.getElementById("table-title").style.display = "none";
  if(document.getElementById("table-stations")) {
    document.getElementById("table-stations").remove();
    document.getElementById("table-stations_wrapper").remove();
  }

  // hide chart title/container/content
  document.getElementById('chart-title').style.display = 'none';
  document.getElementById('chart-content').style.display = 'none';
}

// function to reset map (and all other areas)
function resetMap() {
  // empty all input fields
  document.getElementById('latitude').value = "";
  document.getElementById('longitude').value = "";
  document.getElementById('anzahlTankstellen').value = "";
  document.getElementById('idTankerkoenig').value = "";

  // disable all buttons
  document.getElementById('btn-umkreissuche').disabled = true;
  document.getElementById('btn-snapshot').disabled = true;
  document.getElementById('btn-preisabfrage').disabled = true;

  // check if marker for surrounding area search exists on map
  if (newMarker) {
    // remove marker
    map.removeLayer(newMarker);
  }

  // remove all custom layers
  markers.clearLayers();

  // center to initial view
  map.setView(new L.LatLng(lat, lng), initzoom);

  // hide (and remove) table title/container/contents
  document.getElementById("table-title").style.display = "none";
  if(document.getElementById("table-stations")) {
    document.getElementById("table-stations").remove();
    document.getElementById("table-stations_wrapper").remove();
  }

  // hide chart title/container/content
  document.getElementById('chart-title').style.display = 'none';
  document.getElementById('chart-content').style.display = 'none';
}

// function to plot received gas stations from tankerkoenig API request
function plotData(data) {

  // remove all custom markers
  markers.clearLayers();

  // temp. variable for custom markers
  var tempMarkers = [];

  // loop through all gas stations
  for (var i=0; i < data.stations.length; i++) {

    // create popup for each custom marker
    var customPopup = "<strong>Name:</strong> " + data.stations[i].name + "<br/><strong>Anschrift:</strong><br/>" + data.stations[i].street + " " + data.stations[i].houseNumber + "<br/>0" + data.stations[i].postCode + " " + data.stations[i].place + "<br/><strong>Preise:</strong><br/>Diesel: " + data.stations[i].diesel + "€<br/>E5: " + data.stations[i].e5 + "€<br/>E10: " + data.stations[i].e10 + "€";

    // check if gas station is open or closed
    if(data.stations[i].isOpen == true) {
      // set proper custom icon to gas stations that are open
      // pass tankerkoenig ID for later usage
      // also bind custom popup with all details of that station
      tempMarkers[i] = L.marker([data.stations[i].lat, data.stations[i].lng], {id: data.stations[i].id, icon: gasStationIconOpen}).bindPopup(customPopup).on('click', onClickCustomMarker);
    } else {
      // set proper icon to gas stations that are closed
      // pass tankerkoenig ID for later usage
      // also bind custom popup with all details of that station
      tempMarkers[i] = L.marker([data.stations[i].lat, data.stations[i].lng], {id: data.stations[i].id, icon: gasStationIconClosed}).bindPopup(customPopup).on('click', onClickCustomMarker);
    }

    // add custom marker to group
    markers.addLayer(tempMarkers[i]);
  }

  // add markers group to map
  map.addLayer(markers);
}

// function (click event) for custom markers
function onClickCustomMarker(e) {
  //insert tankerkoenig ID of gas station into readonly input field to be used for the price trend (DB) request
  document.getElementById('idTankerkoenig').value = this.options.id;
  // enable button for price trend
  document.getElementById('btn-preisabfrage').disabled = false;
}

// function to dynamically generate (HTML) dataTable based on received gas stations from surrounding area search
function generateTable(mydata) {

  // hide (and remove) table title/container/contents
  if(document.getElementById("table-stations")) {
    document.getElementById("table-stations").remove();
    document.getElementById("table-stations_wrapper").remove();
  }

  // create DOM table element
  var table = document.createElement("table");
  table.className = "table table-hover table-bordered table-striped";
  table.id = "table-stations";
  table.width = '100%';

  // append DOM table element to parent element
  var divContainer = document.getElementById("table-content");
  divContainer.innerHTML = "";
  divContainer.appendChild(table);

  // get dynamic columns
  var dynamicColumns = [];
  var i = 0;
  $.each(mydata.stations[0], function (key, value) {
      var obj = { sTitle: key };
      dynamicColumns[i] = obj;
      i++;
  });
  // fetch all records from JSON result and make row data set
  var rowDataSet = [];
  var i = 0;
  $.each(mydata.stations, function (key, value) {
      var rowData = [];
      var j = 0;
      $.each(mydata.stations[i], function (key, value) {
          rowData[j] = value;
          j++;
      });
      rowDataSet[i] = rowData;
      i++;
  });

  // initialize dataTable and give column and row data
  $('#table-stations').dataTable({
    data: rowDataSet,
    columns: dynamicColumns,
    responsive: true
  });
}

// function to create and show the price trend chart
function displayPriceTrend(details, json) {

  var xAxis = [];
  var seriesDiesel = [];
  var seriesE5 = [];
  var seriesE10 = [];

  var xAxisAggregated = [];
  var seriesDieselAggregated = [];
  var seriesE5Aggregated = [];
  var seriesE10Aggregated = [];

  // loop through JSON and assign xAxis and series
  for (var i = 0; i < json.length; i++) {
    var tempTimestamp = moment(json[i].timestamp, 'YYYY-MM-DD h:mm:ss');

    xAxis[i] = tempTimestamp.locale('de').format('LLLL');

    seriesDiesel[i] = json[i].priceDiesel;
    seriesE5[i] = json[i].priceE5;
    seriesE10[i] = json[i].priceE10;
  }

  // optional...
  // // loop through xAxis and check if days are equal
  // for (var i = 0; i < xAxis.length; i++) {
  //
  //   var tempDay = moment(xAxis[i]);
  //   var tempDay2 = moment(xAxis[i+1]);
  //
  //   if(!moment(tempDay).isSame(tempDay2, 'day')) {
  //     xAxisAggregated[i] = xAxis[i];
  //     seriesDieselAggregated[i] = seriesDiesel[i];
  //     seriesE5Aggregated[i] = seriesE5[i];
  //     seriesE10Aggregated[i] = seriesE10[i];
  //
  //     xAxisAggregated[i+1] = xAxis[i+1];
  //     seriesDieselAggregated[i+1] = seriesDiesel[i+1];
  //     seriesE5Aggregated[i+1] = seriesE5[i+1];
  //     seriesE10Aggregated[i+1] = seriesE10[i+1];
  //   }
  // }

  Highcharts.chart('chart-content', {
    chart: {
        type: 'line'
    },
    title: {
        text: details.name + ' (Tankerkoenig ID: ' + details.id + ')'
    },
    xAxis: {
        categories: xAxis
    },
    yAxis: {
        title: {
            text: 'Preise in €'
        }
    },
    plotOptions: {
        line: {
            dataLabels: {
                enabled: true
            },
            enableMouseTracking: true
        }
    },
    series: [{
        name: 'Diesel',
        data: seriesDiesel
    }, {
        name: 'E5',
        data: seriesE5
    }, {
        name: 'E10',
        data: seriesE10
    }]
  });

  // show chart title/container/content
  document.getElementById('chart-title').style.display = 'block';
  document.getElementById('chart-content').style.display = 'block';
}
