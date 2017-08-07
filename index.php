<?php
error_reporting(~E_ALL & ~E_DEPRECATED &  ~E_NOTICE);
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1" />
    <meta name="description" content="Webseite zur Nutzung der Tankerkoenig API">
    <meta name="author" content="Max Scholz">

    <title>Tankerk&ouml;nig API</title>

    <!-- Bootstrap Core CSS -->
    <link href="vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet" />

    <!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
    <link href="vendor/bootstrap/css/ie10-viewport-bug-workaround.css" rel="stylesheet" />

    <!-- Custom styles -->
    <link href="assets/css/custom.css" rel="stylesheet" />

    <!-- Custom Fonts -->
    <link href="vendor/font-awesome/css/font-awesome.min.css" rel="stylesheet" type="text/css" >

    <!-- DataTables CSS -->
    <link href="vendor/datatables-plugins/dataTables.bootstrap.css" rel="stylesheet">

    <!-- DataTables Responsive CSS -->
    <link href="vendor/datatables-responsive/dataTables.responsive.css" rel="stylesheet">

    <!-- external resources (leaflet styles) -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.0.3/dist/leaflet.css" />

    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->

    <!-- javascript features functions -->
    <script>
      // temp. storage of received stations
      var data;

      // function to check input fields (coordinates) and AJAX API request
      function checkInputAndAPIRequest() {
        // retrieve values from input fields
        var lat = document.getElementById("latitude").value;
        var lng = document.getElementById("longitude").value;

        // check if values are not 0 and not empty
        if (lat == 0 || lng == 0 || lat == "" || lng == "") {
          // show error message
          showalert("Bitte Latitude und Longitude durch Klick auf Karte wählen.", "danger");
          return;
        } else {
          // good to go to make the AJAX API request
          // code snippet from tankerkoenig site
          $.ajax({
            url: "https://creativecommons.tankerkoenig.de/json/list.php",
            data: {
                lat: lat,
                lng: lng,
                rad: 5, // default
                type: "all", // get all prices --> no sorting needed because distance will be used when selecting all prices
                apikey: "XXX" // INSERT API-KEY OBTAINED FROM https://creativecommons.tankerkoenig.de/
            },
            // successful request
            success: function(response) {
              // check if response is really ok
              if (!response.ok) {
                // show error message
                showalert("Nachricht: " + response.message, "danger");
                return;
              } else {
                // optional console output to see what has been received from the API request (JSON)
                // console.log(response);

                if (response.stations.length == 0) {
                  // show success message
                  showalert("Keine Tankstellen gefunden.", "warning");
                }
                else {
                  // show success message
                  showalert("API Abfrage erfolgreich.", "success");

                  // give global temp. variable the response for later usage (e.g. snapshot)
                  data = response;

                  // center to initial view
                  map.setView(new L.LatLng(lat, lng), initzoom);

                  // Plot gas stations
                  plotData(response);

                  // generate dataTable based on JSON response
                  generateTable(response);

                  // show table title (collapsible)
                  document.getElementById("table-title").style.display = "block";
                  // set value of readonly input field for number of received gas stations
                  document.getElementById("anzahlTankstellen").value = data.stations.length;
                  // enable snapshot button as we now have gas stations that can be stored in the DB
                  document.getElementById('btn-snapshot').disabled = false;
                }
              }
            },
            // problem encountered with AJAX API request
            error: function(p){
              // show error message
              showalert("AJAX-Problem: Status: " + p.status + " Nachricht: " + p.statusText, "danger");
              return;
            }
        });
        }
      }

      // function the make the snapshot based on the received gas stations plotted on the map
      function makeSnapshot() {

        // retrieve number of gas stations from readonly input field
        var anzahlTankstellen = document.getElementById("anzahlTankstellen").value;

        // check if number of gas stations if 0 or empty
        if (anzahlTankstellen == 0 || anzahlTankstellen == "") {
            showalert("Keine Tankstellen ausgewählt.", "danger");
            return;
        }
        else
        {
          // temp. variable to store only gas stations with id and prices
          var tankstellen = [];

          // loop through complete JSON data and extract gas station ids and prices
          for(var i = 0; i < data.stations.length; i++)
          {
            tankstellen.push({idTankerkoenig: data.stations[i].id, diesel: data.stations[i].diesel, e5: data.stations[i].e5, e10: data.stations[i].e10});
          }

          // make proper JSON format out of array
          var jsonObj  = JSON.stringify(tankstellen);

          // new object used to communicate with a web server
          // possible usages of XMLHttpRequest:
          // 1) Update a web page without reloading the page
          // 2) Request data from a server - after the page has loaded
          // 3) Receive data from a server  - after the page has loaded
          // 4) Send data to a server - in the background
          // in this case we want nr. 4)
          xmlhttp = new XMLHttpRequest();

          // set parameters (here: gas stations with ids and prices)
          var params = "tankstellen=" + jsonObj;

          // onreadystatechange property specifies a function to be executed every time the status of the XMLHttpRequest object changes
          xmlhttp.onreadystatechange = function() {
            // while response not ready show info message
            if(this.readyState < 4) {
              // show info message
              showalert("Speichere...", "info");
            }
            // response is ready / done
            if (this.readyState == 4) {
              if(this.status == 200) {
                // show success message
                showalert("Snapshot in der Datenbank gespeichert.", "success");
              }
              // catch all other HTTP status properties
              else {
                // /show error message
                // examples:
                // - not in TUC network
                // - DB offline / unreachable
                showalert("Etwas stimmt nicht. Im TUC Netzwerk unterwegs? Bitte Admin kontaktieren.", "danger");
              }
            }
          };
          // define where the request has to go
          // with:
          // - what type of request: POST
          // - URL of scripts
          xmlhttp.open("POST", "scripts/makeSnapshot.php", true);
          // set header content-type
          xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
          // send the request to web server
          xmlhttp.send(params);
        }
      }

      // function to get prices from DB for a single gas station and show price trend
      function getPricesAndShowTrend() {
        // check if we already received gas stations from the API request
        if(data) {
          // temp. variables
          var exists;
          var details;

          // retrieve Tankerkoenig ID from readonly input field
          var idTankerkoenig = document.getElementById("idTankerkoenig").value;

          // check if ID is not 0 or empty
          if (idTankerkoenig == 0 || idTankerkoenig == "") {
            // show error message
            showalert("Keine Tankstelle ausgewählt.", "danger");
            return;
          }
          else
          {
            // loop through all previously received gas stations
            for(var i = 0; i < data.stations.length; i++)
            {
              // check if selected gas station is within our received data set of gas stations
              if(idTankerkoenig == data.stations[i].id) {
                // it exists
                exists = true;
                // store all details of that gas station
                details = data.stations[i];
              }
            }

            // check if the temp. variable is set
            if(exists) {
              $.ajax({
                type: "POST",
                data: {
                  "idTankerkoenig": JSON.stringify(idTankerkoenig)
                },
                url: "scripts/getPrices.php",
                dataType: "json",
                success: function(JSONObject) {
                  var jsonResponse = JSON.parse(JSONObject);

                  // check if we have actually DB entries for that tankerkoenig ID
                  if(jsonResponse.length > 0) {

                    // show success message
                    showalert("Preisabfrage erfolgreich.", "success");

                    // show price trend
                    displayPriceTrend(details, jsonResponse);
                  }
                  // no DB entries found
                  else {
                    // show warning message
                    showalert("Kein Datenbankeintrag zur ausgewählten Tankstelle vorhanden.", "warning");
                  }
                }
              });
            }
            // doesn't exist in the received gas stations
            else {
              // show error message
              showalert("Tankerkoenig ID nicht in geladenen Tankstellen gefunden.", "danger");
            }
          }
        }
        // no gas stations received yet from Tankerkoenig API
        else {
          // show error message
          showalert("Noch keine Tankstellen abgerufen.", "danger");
        }
      }
    </script>

  </head>

  <!-- site structure -->
  <body>

    <!-- Navigation -->
    <nav class="navbar navbar-inverse navbar-fixed-top" role="navigation">
        <div class="navbar-header">
          <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navBarCollapse" aria-expanded="false">
            <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
          <a class="navbar-brand" href="index.php">Tankerk&ouml;nig API</a>
          <ul class="nav navbar-nav">
            <li><a href="#" data-toggle="modal" data-target="#infoModal">Info</a></li>
          </ul>
        </div>
    </nav>

    <!-- Modal -->
    <div class="modal fade" id="infoModal" tabindex="-1" role="dialog" aria-labelledby="infoModal">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title" id="infoModalLabel">Informationen zur Webseite</h4>
          </div>
          <div class="modal-body">
            <p>Diese Webseite verwendet die <a href="https://creativecommons.tankerkoenig.de/" target="_blank">Tankerk&ouml;nig Echtzeit-Benzinpreis-API</a>.</p>
            <p><strong>Features</strong></p>
            <ul>
              <li>Umkreissuche: Suche von Tankstellen in einem Radius von 5km.</li>
              <li>Karten&uuml;bersicht: Anzeige gefundener Tankstellen.</li>
              <li>Tabellen&uuml;bersicht: Tabellarische &Uuml;bersicht gefundener Tankstellen.</li>
              <li>Snapshot: Abspeichern von Preisen der gefundenen Tankstellen in der Datenbank.</li>
              <li>Preistrendabfrage: Abfrage von Preisen zu einer selektierten Tankstelle.</li>
            </ul>
            <p><strong>Frameworks</strong></p>
            <ul>
              <li><a href="https://getbootstrap.com/" target="_blank">Bootstrap</a> (HTML/CSS Grundger&uuml;st)</li>
              <li><a href="http://leafletjs.com/" target="_blank">Leaflet</a> (Open-Source-JavaScript-Bibliothek f&uuml;r interaktive Karten)</li>
              <li><a href="https://datatables.net/" target="_blank">DataTables</a> (Tabellen-Plugin f&uuml;r jQuery)</li>
              <li><a href="https://www.highcharts.com/" target="_blank">Highcharts</a> (Interaktive JavaScript Visualisierungen)</li>
              <li><a href="https://momentjs.com/" target="_blank">Moment.js</a> (Datum- &amp; Uhrzeit-Framework f&uuml;r JavaScript)</li>
            </ul>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">Schlie&szlig;en</button>
          </div>
        </div>
      </div>
    </div>

    <!-- main container for contents -->
    <div class="container-fluid">

      <!-- map title (collapsible) -->
      <div class="row" id="map-title">
          <div class="col-sm-12">
              <h2 class="page-header collapse-header" data-toggle="collapse" data-target="#map-container">Karten&uuml;bersicht</h2>
              <span class="label label-info">Klicken Sie auf die &Uuml;berschrift um die Karte ein-/auszublenden.</span>
              <br /><br />
          </div>
          <div class="col-sm-12" id="map-container">
            <div id="map"></div>
          </div>
      </div>

      <!-- features title (collapsible) -->
      <div class="row" id="features-title">
        <div class="col-sm-12">
            <h2 class="page-header collapse-header" data-toggle="collapse" data-target="#features-container">Features</h2>
            <span class="label label-info">Klicken Sie auf die &Uuml;berschrift um die Features ein-/auszublenden.</span>
            <br /><br />
        </div>
        <!-- container for all features -->
        <div class="col-sm-12 collapse" id="features-container">
          <div class="col-xs-12" id="messages-container" style="display:none;">
            <div id="alert_placeholder"></div>
          </div>
          <!-- surrounding area search -->
          <div class="col-xs-12 col-sm-6 col-md-4" id="umkreissuche-container">
            <h3>Umkreissuche</h3>
            <div id="umkreissuche-content">
              <span class="label label-info">Klicken Sie auf die Karte um die Koordinaten zu erhalten.</span>
              <br /><br />
              <label>Koordinaten:</label>
              <div class="form-group">
                <label class="sr-only" for="latitude">Latitude:</label>
                <input type="text" class="form-control" id="latitude" placeholder="Latitude..." name="latitude" readonly>
              </div>
              <div class="form-group">
                <label class="sr-only" for="longitude">Longitude:</label>
                <input type="text" class="form-control" id="longitude" placeholder="Longitude..." name="longitude" readonly>
              </div>
              <p class="help-block"><strong>Hinweis:</strong> Standardradius ist 5km und Sortierung nach Distanz.</p>
              <button onclick="checkInputAndAPIRequest();" id="btn-umkreissuche" type="submit" class="btn btn-default suche" disabled>Umkreissuche starten</button>
              <button onclick="resetMap();" class="btn btn-default">Reset</button>
            </div>
          </div>
          <!-- snapshot -->
          <div class="col-xs-12 col-sm-6 col-md-4" id="snapshot-container">
            <h3>Snapshot</h3>
            <span class="label label-info">Snapshot ist erst möglich, wenn eine Umkreissuche ausgeführt worden ist.</span>
            <br /><br />
            <div id="snapshot-content">
              <div class="form-group">
                <label for="anzahlTankstellen">Anzahl an Tankstellen:</label>
                <input type="text" class="form-control" id="anzahlTankstellen" name="anzahlTankstellen" readonly>
              </div>
              <p class="help-block"><strong>Hinweis:</strong> Es werden von allen Tankstellen - sofern vorhanden - nur die Diesel-, E5- und E10-Preise gespeichert.</p>
              <button onclick="makeSnapshot();" id="btn-snapshot" class="btn btn-default" disabled>Speichern</button>
            </div>
          </div>
          <!-- price trend -->
          <div class="col-xs-12 col-sm-12 col-md-4" id="preistrend-container">
            <h3>Preistrendabfrage</h3>
            <div id="preistrend-content">
              <span class="label label-info">Bitte selektieren Sie eine Tankstelle auf der Karte.</span>
              <br /><br />
              <div class="form-group">
                <label for="idTankerkoenig">Selektierte Tankstelle:</label>
                <input type="text" class="form-control" id="idTankerkoenig" name="idTankerkoenig" readonly>
              </div>
              <button onclick="getPricesAndShowTrend();" id="btn-preisabfrage" class="btn btn-default" disabled>Preisabfrage</button>
            </div>
          </div>
        </div>
      </div>

      <!-- chart title (collapsible) -->
      <div class="row" id="chart-title" style="display: none;">
          <div class="col-sm-12">
              <h2 class="page-header collapse-header" data-toggle="collapse" data-target="#chart-container">Preistrend</h2>
              <span class="label label-info">Klicken Sie auf die &Uuml;berschrift um den Preistrend-Bereich ein-/auszublenden.</span>
              <br /><br />
          </div>
      </div>

      <!-- chart container -->
      <div class="row collapse" id="chart-container">
          <div class="col-sm-12">
            <div id="chart-content" style="min-width: 310px; height: 400px; margin: 0 auto"></div>
          </div>
      </div>

      <!-- table title (collapsible) -->
      <div class="row" id="table-title" style="display: none;">
          <div class="col-sm-12">
              <h2 class="page-header collapse-header" data-toggle="collapse" data-target="#table-container">Tabellen&uuml;bersicht</h2>
              <span class="label label-info">Klicken Sie auf die &Uuml;berschrift um die Tabelle ein-/auszublenden.</span>
              <br /><br />
          </div>
      </div>

      <!-- table container -->
      <div class="row collapse" id="table-container">
          <div class="col-sm-12">
            <div id="table-content" class="table-responsive">
              </table>
            </div>
          </div>
      </div>

      <!-- give some space between last container and page end -->
      <br /><br />

    </div>

    <!-- Include JS resources -->

    <!-- jQuery -->
    <script src="vendor/jquery/jquery.min.js"></script>

    <!-- external resources (jQuery) -->
    <!-- <script src="//code.jquery.com/jquery-1.12.4.js"></script> -->

    <!-- Bootstrap Core JavaScript -->
    <script src="vendor/bootstrap/js/bootstrap.min.js"></script>

    <!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
    <script src="vendor/bootstrap/js/ie10-viewport-bug-workaround.js"></script>

    <!-- DataTables JavaScript -->
    <script src="vendor/datatables/js/jquery.dataTables.min.js"></script>
    <script src="vendor/datatables-plugins/dataTables.bootstrap.min.js"></script>
    <script src="vendor/datatables-responsive/dataTables.responsive.js"></script>

    <!-- external resources (leaflet) -->
    <script src="https://unpkg.com/leaflet@1.0.3/dist/leaflet.js"></script>

    <!-- external resources (leaflet clustering of markers) -->
    <script src="https://leaflet.github.io/Leaflet.markercluster/dist/leaflet.markercluster-src.js"></script>

    <!-- external resources (highcharts) -->
    <script src="https://code.highcharts.com/highcharts.js"></script>
    <script src="https://code.highcharts.com/modules/exporting.js"></script>

    <!-- small JS helper for date/time handling -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.18.1/moment.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.18.1/locale/de.js"></script>

    <!-- Custom JavaScript (initMap, resetMap, plotData, generateTable, etc) -->
    <script src="assets/js/custom.js"></script>

    <!-- Custom JavaScript (resize map for leaflet) -->
    <script type="text/javascript" src="assets/js/resize_map.js"></script>

    <script>

      // helper function for creating alert messages
      function showalert(message, alerttype) {
        document.getElementById("messages-container").style.display = "block";

        if(document.getElementById("alertdiv")) {
          $("#alertdiv").remove();
        }

        $('#alert_placeholder').append('<div id="alertdiv" class="alert alert-' +  alerttype + '"><a class="close" data-dismiss="alert">×</a><span>'+message+'</span></div>')
        setTimeout(function() { // automatically close alert and remove it if the users doesnt close within 5 secs
          document.getElementById("messages-container").style.display = "none";
          $("#alertdiv").remove();
        }, 5000);
      }

      // initialize and create leaflet map
      initMap();

      // hekper for collapsibles
      $('.collapse-header').on('click touchstart', function () {
        $($(this).data('target')).collapse('toggle');
      });

    </script>

  </body>
</html>
