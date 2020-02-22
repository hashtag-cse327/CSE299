<!DOCTYPE html>
<html>

  <head>
    <meta charset='utf-8' />
    <title>Delivery App</title>
    <meta name='viewport' content='initial-scale=1,maximum-scale=1,user-scalable=no' />
    <script src='https://npmcdn.com/@turf/turf/turf.min.js'></script>
    <script src='https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js'></script>
    <script src='https://api.tiles.mapbox.com/mapbox-gl-js/v1.8.0/mapbox-gl.js'></script>
    <link href='https://api.tiles.mapbox.com/mapbox-gl-js/v1.8.0/mapbox-gl.css' rel='stylesheet' />
    <style>

      body { margin: 0; padding: 0; }

      #map { position: absolute; top: 0; bottom: 0; width: 100%; }

      .truck {
        margin: -10px -10px;
        width: 20px;
        height: 20px;
        border: 2px solid #fff;
        border-radius: 50%;
        background: #3887be;
        pointer-events: none;
      }

    </style>
  </head>

  <body style="text-align: center;">

    <form style="margin-top: 35%;" method="POST">
      <input type="number" name="source" placeholder="Source"><br>
      <input type="number" name="dest" placeholder="Destination"><br>
      <button type="submit" name="submit">Find Best Route</button>
    </form>

    <?php 

    if(isset($_POST["submit"])){
      $s = intval($_POST["source"]);
      $d = intval($_POST["dest"]);


      $INT_MAX = 0x7FFFFFFF; #max value for 32 bit

      function MinimumDistance($distance, $shortestPathTreeSet, $verticesCount)
      {
        global $INT_MAX;
        $min = $INT_MAX;
        $minIndex = 0;

        for ($v = 0; $v < $verticesCount; ++$v)
        {
          if ($shortestPathTreeSet[$v] == false && $distance[$v] <= $min)
          {
            $min = $distance[$v];
            $minIndex = $v;
          }
        }

        return $minIndex;
      }

      function PrintResult($distance, $verticesCount)
      {
        global $d;

        for ($i = 0; $i < $verticesCount; ++$i){
          if($d == $i) {
            echo "The distance from source to destination is: " . $distance[$i] . " km";
          }
        }
      }

      function Dijkstra($graph, $source, $verticesCount)
      {
        global $INT_MAX;
        $distance = array();
        $shortestPathTreeSet = array();


        for ($i = 0; $i < $verticesCount; ++$i)
        {
          $distance[$i] = $INT_MAX;
          $shortestPathTreeSet[$i] = false;
        }

        $distance[$source] = 0;

        for ($count = 0; $count < $verticesCount - 1; ++$count)
        {
          $u = MinimumDistance($distance, $shortestPathTreeSet, $verticesCount);
          $shortestPathTreeSet[$u] = true;

          for ($v = 0; $v < $verticesCount; ++$v)
            if (!$shortestPathTreeSet[$v] && $graph[$u][$v] && $distance[$u] != $INT_MAX && $distance[$u] + $graph[$u][$v] < $distance[$v])
              $distance[$v] = $distance[$u] + $graph[$u][$v];
        }

        PrintResult($distance, $verticesCount);
      }

      $graph = array(               
      array(0, 0.074, 0, 0, 0, 0, 0, 0, 0.151),
      array(0.074, 0, 0.092, 0, 0, 0, 0, 0.152, 0),
      array(0, 0.092, 0, 0.205, 0, 0.236, 0, 0, 0),
      array(0, 0, 0.205, 0, 0.174, 0, 0, 0, 0),
      array(0, 0, 0, 0, 0, 0.08, 0, 0, 0),
      array(0, 0, 0.236, 0, 0.08, 0, 0.078, 0, 0),
      array(0, 0, 0, 0, 0, 0.078, 0, 0.085, 0),
      array(0, 0.152, 0, 0, 0, 0, 0.085, 0, 0.074),
      array(0.151, 0, 0, 0, 0, 0, 0, 0.074, 0)
      );

    ?>

    <?php

      Dijkstra($graph, $s, 9);
    }

    ?>


    <div id='map' class='contain' style="width: 50%;height: 70%;margin-left: 30%;"></div>
    <script>
      var truckLocation = [90.40339350700378, 23.798033382050917];
      var warehouseLocation = [90.40329158306122, 23.797412480561615];
      var lastQueryTime = 0;
      var lastAtRestaurant = 0;
      var keepTrack = [];
      var currentSchedule = [];
      var currentRoute = null;
      var pointHopper = {};
      var pause = true;
      var speedFactor = 50;

      // Add your access token
      mapboxgl.accessToken = 'pk.eyJ1IjoiemlhZGh6IiwiYSI6ImNrNnF0NDV4MzAwMjYzaW56dXJwa3FycjUifQ.axDYCUEC_w87JiM5L57skQ';

      // Initialize a map
      var map = new mapboxgl.Map({
        container: 'map', // container id
        style: 'mapbox://styles/mapbox/light-v10', // stylesheet location
        center: [90.40515303611754,23.79718424329992], // starting position
        zoom: 16 // starting zoom
      });

      /*map.addControl(
        new MapboxDirections({
          accessToken: mapboxgl.accessToken
        }),
        'top-left'
      );*/

      // Create a GeoJSON feature collection for the warehouse
      var warehouse = turf.featureCollection([turf.point(warehouseLocation)]);

      // Create an empty GeoJSON feature collection for drop-off locations
      var dropoffs = turf.featureCollection([]);

      // Create an empty GeoJSON feature collection, which will be used as the data source for the route before users add any new data
      var nothing = turf.featureCollection([]);

      //Start of map.on load function
      map.on('load', function() {
        var marker = document.createElement('div');
        marker.classList = 'truck';

        // Create a new marker
        truckMarker = new mapboxgl.Marker(marker)
          .setLngLat(truckLocation)
          .addTo(map);

        // Create source for the markers placed in the map  
        map.addSource('points', {
            'type': 'geojson',
            'data': {
                'type': 'FeatureCollection',
                'features': [
                    {
                        // feature for Mapbox DC
                        'type': 'Feature',
                        'geometry': {
                            'type': 'Point',
                            'coordinates': [90.40494918823242, 23.79872545056962]
                        },
                        'properties': {
                            'title': '0',
                            'icon': 'monument'
                        }
                    },
                    {
                        // feature for Mapbox SF
                        'type': 'Feature',
                        'geometry': {
                            'type': 'Point',
                            'coordinates': [90.40568947792053,23.798470220055645]
                        },
                        'properties': {
                            'title': '1',
                            'icon': 'monument'
                        }
                    },
                    {
                        // feature for Mapbox SF
                        'type': 'Feature',
                        'geometry': {
                            'type': 'Point',
                            'coordinates': [90.40640830993652,23.79812663972582]
                        },
                        'properties': {
                            'title': '2',
                            'icon': 'monument'
                        }
                    },
                    {
                        // feature for Mapbox SF
                        'type': 'Feature',
                        'geometry': {
                            'type': 'Point',
                            'coordinates': [90.40753483772278,23.796634508894723]
                        },
                        'properties': {
                            'title': '3',
                            'icon': 'monument'
                        }
                    },
                    {
                        // feature for Mapbox SF
                        'type': 'Feature',
                        'geometry': {
                            'type': 'Point',
                            'coordinates': [90.4060971736908,23.795770635844292]
                        },
                        'properties': {
                            'title': '4',
                            'icon': 'monument'
                        }
                    },
                    {
                        // feature for Mapbox SF
                        'type': 'Feature',
                        'geometry': {
                            'type': 'Point',
                            'coordinates': [90.40557146072388,23.79617312286651]
                        },
                        'properties': {
                            'title': '5',
                            'icon': 'monument'
                        }
                    },
                    {
                        // feature for Mapbox SF
                        'type': 'Feature',
                        'geometry': {
                            'type': 'Point',
                            'coordinates': [90.40488481521606,23.796457808056232]
                        },
                        'properties': {
                            'title': '6',
                            'icon': 'monument'
                        }
                    },
                    {
                        // feature for Mapbox SF
                        'type': 'Feature',
                        'geometry': {
                            'type': 'Point',
                            'coordinates': [90.40515303611754,23.79718424329992]
                        },
                        'properties': {
                            'title': '7',
                            'icon': 'monument'
                        }
                    },
                    {
                        // feature for Mapbox SF
                        'type': 'Feature',
                        'geometry': {
                            'type': 'Point',
                            'coordinates': [90.40444493293762,23.797429659694107]
                        },
                        'properties': {
                            'title': '8',
                            'icon': 'monument'
                        }
                    }
                ]
            }
        }); // End of Source

        // Create layer to place the symbols
        map.addLayer({
            'id': 'points',
            'type': 'symbol',
            'source': 'points',
            'layout': {
                // get the icon name from the source's "icon" property
                // concatenate the name to get an icon from the style's sprite sheet
                'icon-image': ['concat', ['get', 'icon'], '-15'],
                // get the title name from the source's "title" property
                'text-field': ['get', 'title'],
                'text-font': ['Open Sans Semibold', 'Arial Unicode MS Bold'],
                'text-offset': [0, 0.6],
                'text-anchor': 'top'
            }
        }); // End of layer

        // Create a circle layer
        map.addLayer({
          id: 'warehouse',
          type: 'circle',
          source: {
            data: warehouse,
            type: 'geojson'
          },
          paint: {
            'circle-radius': 20,
            'circle-color': 'white',
            'circle-stroke-color': '#3887be',
            'circle-stroke-width': 3
          }
        }); // End map.addLayer({})

        // Create a symbol layer on top of circle layer
        map.addLayer({
          id: 'warehouse-symbol',
          type: 'symbol',
          source: {
            data: warehouse,
            type: 'geojson'
          },
          layout: {
            'icon-image': 'grocery-15',
            'icon-size': 1
          },
          paint: {
            'text-color': '#3887be'
          }
        }); // End map.addLayer({})

        // Create layer for the dropoff symbols
        map.addLayer({
          id: 'dropoffs-symbol',
          type: 'symbol',
          source: {
            data: dropoffs,
            type: 'geojson'
          },
          layout: {
            'icon-allow-overlap': true,
            'icon-ignore-placement': true,
            'icon-image': 'marker-15',
          }
        }); // End map.addLayer({})

        // Create a source for the route
        map.addSource('route', {
          type: 'geojson',
          data: nothing
        }); // End map.addSource

        // Create layer for routeline from location
        map.addLayer({
          id: 'routeline-active',
          type: 'line',
          source: 'route',
          layout: {
            'line-join': 'round',
            'line-cap': 'round'
          },
          paint: {
            'line-color': '#3887be',
            'line-width': [
              "interpolate",
              ["linear"],
              ["zoom"],
              12, 3,
              22, 12
            ]
          }
        }, 'waterway-label'); // End map.addLayer({})

        // Create layer for route symbols
        map.addLayer({
          id: 'routearrows',
          type: 'symbol',
          source: 'route',
          layout: {
            'symbol-placement': 'line',
            'text-field': '▶',
            'text-size': [
              "interpolate",
              ["linear"],
              ["zoom"],
              12, 24,
              22, 60
            ],
            'symbol-spacing': [
              "interpolate",
              ["linear"],
              ["zoom"],
              12, 30,
              22, 160
            ],
            'text-keep-upright': false
          },
          paint: {
            'text-color': '#3887be',
            'text-halo-color': 'hsl(55, 11%, 96%)',
            'text-halo-width': 3
          }
        }, 'waterway-label'); // End map layer
      
        // Listen for a click on the map
        map.on('click', function(e) {
          // When the map is clicked, add a new drop-off point
          // and update the `dropoffs-symbol` layer
          newDropoff(map.unproject(e.point));
          updateDropoffs(dropoffs);
        }); // End map.on('click', function(e){})

      }); // End map.on('load',function(){})

      // Start function for new drop offs
      function newDropoff(coords) {
        // Store the clicked point as a new GeoJSON feature with
        // two properties: `orderTime` and `key`
        var pt = turf.point(
          [coords.lng, coords.lat],
          {
            orderTime: Date.now(),
            key: Math.random()
          }
        );
        dropoffs.features.push(pt);
        pointHopper[pt.properties.key] = pt;

        // Make a request to the Optimization API
        $.ajax({
          method: 'GET',
          url: assembleQueryURL(),
        }).done(function(data) {
          // Create a GeoJSON feature collection
          var routeGeoJSON = turf.featureCollection([turf.feature(data.trips[0].geometry)]);

          // If there is no route provided, reset
          if (!data.trips[0]) {
            routeGeoJSON = nothing;
          } else {
            // Update the `route` source by getting the route source
            // and setting the data equal to routeGeoJSON
            map.getSource('route')
              .setData(routeGeoJSON);
          }

          if (data.waypoints.length === 12) {
            window.alert('Maximum number of points reached. Read more at docs.mapbox.com/api/navigation/#optimization.');
          }
        });
      } // End of function for new drop offs

      // Start of function for updating dropoffs
      function updateDropoffs(geojson) {
        map.getSource('dropoffs-symbol')
          .setData(geojson);
      } // End function updateDropoffs(geojson)

      // Here you'll specify all the parameters necessary for requesting a response from the Optimization API
      function assembleQueryURL() {

        // Store the location of the truck in a variable called coordinates
        var coordinates = [truckLocation];
        var distributions = [];
        keepTrack = [truckLocation];

        // Create an array of GeoJSON feature collections for each point
        var restJobs = objectToArray(pointHopper);

        // If there are any orders from this restaurant
        if (restJobs.length > 0) {

          // Check to see if the request was made after visiting the restaurant
          var needToPickUp = restJobs.filter(function(d, i) {
            return d.properties.orderTime > lastAtRestaurant;
          }).length > 0;

          // If the request was made after picking up from the restaurant,
          // Add the restaurant as an additional stop
          if (needToPickUp) {
            var restaurantIndex = coordinates.length;
            // Add the restaurant as a coordinate
            coordinates.push(warehouseLocation);
            // push the restaurant itself into the array
            keepTrack.push(pointHopper.warehouse);
          }

          restJobs.forEach(function(d, i) {
            // Add dropoff to list
            keepTrack.push(d);
            coordinates.push(d.geometry.coordinates);
            // if order not yet picked up, add a reroute
            if (needToPickUp && d.properties.orderTime > lastAtRestaurant) {
              distributions.push(restaurantIndex + ',' + (coordinates.length - 1));
            }
          });
        }

        // Set the profile to `driving`
        // Coordinates will include the current location of the truck,
        return 'https://api.mapbox.com/optimized-trips/v1/mapbox/driving/' + coordinates.join(';') + '?distributions=' + distributions.join(';') + '&overview=full&steps=true&geometries=geojson&source=first&access_token=' + mapboxgl.accessToken;
      }

      function objectToArray(obj) {
        var keys = Object.keys(obj);
        var routeGeoJSON = keys.map(function(key) {
          return obj[key];
        });
        return routeGeoJSON;
      }

    </script>
  </body>

</html>