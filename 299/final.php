<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8" />
<title>Best Route</title>
<meta name="viewport" content="initial-scale=1,maximum-scale=1,user-scalable=no" />
<script src="https://api.mapbox.com/mapbox-gl-js/v1.7.0/mapbox-gl.js"></script>
<link href="https://api.mapbox.com/mapbox-gl-js/v1.7.0/mapbox-gl.css" rel="stylesheet" />
<style>
  body { margin: 0; padding: 0; }
  #map { position: absolute; top: 0; bottom: 0; width: 100%; }
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
  
<div id="map" style="width: 50%;height: 70%;margin-left: 30%;"></div>
<script>
  mapboxgl.accessToken = 'pk.eyJ1IjoiaWZ0aWF6YWhtZWQiLCJhIjoiY2s2YjAzOHNkMHc5djNucWpycXdiZXZpMyJ9.ngwkmBE-oRh69WSxoWBFEg';
    var map = new mapboxgl.Map({
        container: 'map',
        style: 'mapbox://styles/mapbox/streets-v11',
        center: [90.40515303611754,23.79718424329992],
        zoom: 16
    });

    map.on('load', function() {
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
        });
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
        });
    });
</script>

</body>
</html>