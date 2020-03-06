/*
 * AMSAT Leaderboard Map
 */

var leaderboardMap = null;;

function get_amsat_feature_style() {
    var stroke = new ol.style.Stroke({color: 'black', width: 2});
    var fill = new ol.style.Fill({color: 'red'});
    var styles = {
      'square': new ol.style.Style({
        image: new ol.style.RegularShape({
          fill: fill,
          stroke: stroke,
          points: 4,
          radius: 10,
          angle: Math.PI / 4
        })
      })
    };

    return styles['square'];
}

function create_ground_station_feature(features, lat_lon, style) {
    i = features.push(new ol.Feature({
        geometry: new ol.geom.Point(ol.proj.fromLonLat(lat_lon)), 
    }));
    features[i-1].setStyle(style);
}

function parse_amsat_map_ajax_result(data) {
    var features = [];

    style = get_amsat_feature_style();
    jQuery.each(data, function(index, ground_station) {
        create_ground_station_feature(features, ground_station['rx_location'], style)
    });

    return features;
}

function init_amsat_ground_station_map(spacecraft, last_x) {
    jQuery.ajax({
        url: "map_ajax.php", 
        method: 'POST',
        data: {
            'spacecraft': spacecraft,
            'last_x':     last_x
        },
        success: function( data ) {
            features = parse_amsat_map_ajax_result(data);

            create_amsat_leaderboard_map(create_amsat_feature_layer(features));
        }
    });
}

function update_amsat_ground_station_map(spacecraft, last_x) {
   jQuery.ajax({
        url: "map_ajax.php", 
        method: 'POST',
        data: {
            'last_x':     last_x,
            'spacecraft': spacecraft
        },
        success: function( data ) {
            features = parse_amsat_map_ajax_result(data);

            leaderboardMap.getLayers().forEach(function(el) {
                if(el.get('name') === 'GroundStationLayer') {
                    leaderboardMap.removeLayer(el);
                    leaderboardMap.addLayer(create_amsat_feature_layer(features));
                }
            });
        }
    });
}

function create_amsat_feature_layer(features) {
    var source = new ol.source.Vector({
        features: features
    });

    ground_station_layer = new ol.layer.Vector({
        source: source
    });
    ground_station_layer.set('name', 'GroundStationLayer');

    return ground_station_layer;
}

function create_amsat_leaderboard_map(feature_layer) {
    base_layer = new ol.layer.Tile({
          source: new ol.source.OSM()
    });
    base_layer.set('name', 'BaseLayer');

    leaderboardMap = new ol.Map({
      layers: [base_layer, feature_layer],
      target: 'map',
      view: new ol.View({
        center: [0, 0],
        zoom: 2
      })
   });
}

