<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8" />
<title>Measure distances</title>
<meta name="viewport" content="initial-scale=1,maximum-scale=1,user-scalable=no" />
<script src="https://api.mapbox.com/mapbox-gl-js/v1.7.0/mapbox-gl.js"></script>
<link href="https://api.mapbox.com/mapbox-gl-js/v1.7.0/mapbox-gl.css" rel="stylesheet" />
<style>
    body { margin: 0; padding: 0; }
    #map { position: absolute; top: 0; bottom: 0; width: 100%; }
</style>
</head>
<body>
<style>
    .distance-container {
        position: absolute;
        top: 10px;
        left: 10px;
        z-index: 1;
    }

    .distance-container > * {
        background-color: rgba(0, 0, 0, 0.5);
        color: #fff;
        font-size: 11px;
        line-height: 18px;
        display: block;
        margin: 0;
        padding: 5px 10px;
        border-radius: 3px;
    }
</style>

<div style="width: 50%;height: 70%;margin-left: 30%;" id="map"></div>
<div id="distance" class="distance-container"></div>

<script src="https://npmcdn.com/@turf/turf@5.1.6/turf.min.js"></script>
<script>
    mapboxgl.accessToken = 'pk.eyJ1IjoiaWZ0aWF6YWhtZWQiLCJhIjoiY2s2YjAzOHNkMHc5djNucWpycXdiZXZpMyJ9.ngwkmBE-oRh69WSxoWBFEg';
    var map = new mapboxgl.Map({
        container: 'map',
        style: 'mapbox://styles/mapbox/streets-v11',
        center: [90.4076099395752, 23.79755276035878],
        zoom: 16
    });

    var distanceContainer = document.getElementById('distance');

    // GeoJSON object to hold our measurement features
    var geojson = {
        'type': 'FeatureCollection',
        'features': []
    };

    // Used to draw a line between points
    var linestring = {
        'type': 'Feature',
        'geometry': {
            'type': 'LineString',
            'coordinates': []
        }
    };

    map.on('load', function() {
        map.addSource('geojson', {
            'type': 'geojson',
            'data': geojson
        });

        // Add styles to the map
        map.addLayer({
            id: 'measure-points',
            type: 'circle',
            source: 'geojson',
            paint: {
                'circle-radius': 5,
                'circle-color': '#000'
            },
            filter: ['in', '$type', 'Point']
        });
        map.addLayer({
            id: 'measure-lines',
            type: 'line',
            source: 'geojson',
            layout: {
                'line-cap': 'round',
                'line-join': 'round'
            },
            paint: {
                'line-color': '#000',
                'line-width': 2.5
            },
            filter: ['in', '$type', 'LineString']
        });

        map.on('click', function(e) {
            var features = map.queryRenderedFeatures(e.point, {
                layers: ['measure-points']
            });

            // Remove the linestring from the group
            // So we can redraw it based on the points collection
            if (geojson.features.length > 1) geojson.features.pop();

            // Clear the Distance container to populate it with a new value
            distanceContainer.innerHTML = '';

            // If a feature was clicked, remove it from the map
            if (features.length) {
                var id = features[0].properties.id;
                geojson.features = geojson.features.filter(function(point) {
                    return point.properties.id !== id;
                });
            } else {
                var point = {
                    'type': 'Feature',
                    'geometry': {
                        'type': 'Point',
                        'coordinates': [e.lngLat.lng, e.lngLat.lat]
                    },
                    'properties': {
                        'id': String(new Date().getTime())
                    }
                };

                geojson.features.push(point);
            }

            if (geojson.features.length > 1) {
                linestring.geometry.coordinates = geojson.features.map(function(
                    point
                ) {
                    return point.geometry.coordinates;
                });

                geojson.features.push(linestring);

                // Populate the distanceContainer with total distance
                var value = document.createElement('pre');
                value.textContent =
                    'Total distance: ' +
                    turf.length(linestring).toLocaleString() +
                    'km';
                distanceContainer.appendChild(value);
            }

            map.getSource('geojson').setData(geojson);
        });
    });

    map.on('mousemove', function(e) {
        var features = map.queryRenderedFeatures(e.point, {
            layers: ['measure-points']
        });
        // UI indicator for clicking/hovering a point on the map
        map.getCanvas().style.cursor = features.length
            ? 'pointer'
            : 'crosshair';
    });
</script>

</body>
</html>