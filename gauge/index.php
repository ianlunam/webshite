<html>
<head>
<link rel="stylesheet" href="/style.css" type="text/css" charset="utf-8" /> 
<title>Whats Happening</title>
<script type="text/javascript" src="js/fusioncharts.js"></script>
<script type="text/javascript" src="js/themes/fusioncharts.theme.ocean.js"></script>
<?php

include("includes/fusioncharts.php");

##################################
# Distance calculations for planes
##################################

function vincentyGreatCircleDistance( $latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo, $earthRadius = 6371000) {
  // convert from degrees to radians
  $latFrom = deg2rad($latitudeFrom);
  $lonFrom = deg2rad($longitudeFrom);
  $latTo = deg2rad($latitudeTo);
  $lonTo = deg2rad($longitudeTo);

  $lonDelta = $lonTo - $lonFrom;
  $a = pow(cos($latTo) * sin($lonDelta), 2) +
    pow(cos($latFrom) * sin($latTo) - sin($latFrom) * cos($latTo) * cos($lonDelta), 2);
  $b = sin($latFrom) * sin($latTo) + cos($latFrom) * cos($latTo) * cos($lonDelta);

  $angle = atan2(sqrt($a), $b);
  return $angle * $earthRadius;
}

##################################
# Plane data from URL
##################################

$json = file_get_contents('http://192.168.0.58:8090/dump1090/data.json');
$obj = json_decode($json, true);

$baseLat = 51.300261;
$baseLon = 0.579266;
$earthRad = 3440;

$FURTHEST = 0;
$PLANE_COUNT = 0;

foreach ($obj as $plane) {
  if ( $plane['validposition'] == '1' && $plane['validtrack'] == '1' && $plane['seen'] <= 60 ) {
    $PLANE_COUNT++;
    $planeDist = vincentyGreatCircleDistance($baseLat, $baseLon, $plane['lat'], $plane['lon'], $earthRad);
    echo "<!-- Lat: " . $plane['lat'] . " Long: " . $plane['lon'] . " Distance: " . $planeDist . " -->";
    if ( $planeDist > $FURTHEST ) {
      $FURTHEST = $planeDist;
    }
  }
}

##################################
# Plane gauges
##################################

# Plane counter gauge
$chartPlaneCount = new FusionCharts("AngularGauge", "ex5", "100%", "200", "chart-plane-count", "json", '{
    "chart": {
        "dataStreamUrl": "update.php?data=planecnt",
        "refreshInterval": "30",
        "caption": "Planes Monitored",
        "lowerlimit": "0",
        "upperlimit": "50",
        "showValue": "1",
        "valueBelowPivot": "1",
        "gaugeFillMix": "{dark-30},{light-60},{dark-10}",
        "gaugeFillRatio": "15",
        "gaugeInnerRadius": "40%",
        "theme": "fint"
    },
    "colorrange": {
        "color": [
            {
                "minvalue": "0",
                "maxvalue": "5",
                "code": "e44a00"
            },
            {
                "minvalue": "5",
                "maxvalue": "10",
                "code": "f8bd19"
            },
            {
                "minvalue": "10",
                "maxvalue": "50",
                "code": "6baa01"
            }
        ]
    },
    "dials": {
        "dial": [
            {
                "value": "' . $PLANE_COUNT . '",
                "rearextension": "8",
                "radius": "85",
                "bgcolor": "333333",
                "bordercolor": "333333"
            }
        ]
    }
}');
$chartPlaneCount->render();

# Plane distance gauge
$chartPlaneDist = new FusionCharts("AngularGauge", "ex6", "100%", "200", "chart-plane-distance", "json", '{
    "chart": {
        "dataStreamUrl": "update.php?data=planedist",
        "refreshInterval": "30",
        "caption": "Most Distant Plane (nm)",
        "lowerlimit": "0",
        "upperlimit": "150",
        "showValue": "1",
        "valueBelowPivot": "1",
        "gaugeFillMix": "{dark-30},{light-60},{dark-10}",
        "gaugeFillRatio": "15",
        "gaugeInnerRadius": "40%",
        "theme": "fint"
    },
    "colorrange": {
        "color": [
            {
                "minvalue": "0",
                "maxvalue": "15",
                "code": "e44a00"
            },
            {
                "minvalue": "15",
                "maxvalue": "30",
                "code": "f8bd19"
            },
            {
                "minvalue": "30",
                "maxvalue": "150",
                "code": "6baa01"
            }
        ]
    },
    "dials": {
        "dial": [
            {
                "value": "' . $FURTHEST . '",
                "rearextension": "8",
                "radius": "85",
                "bgcolor": "333333",
                "bordercolor": "333333"
            }
        ]
    }
}');
$chartPlaneDist->render();

##################################
# Oil data from DB
##################################

# NOTE: depth is where I'm storing the reading from the oil level sonar (cm from sensor to oil surface)

$mysqli = new mysqli("localhost", "ian", null, "temps");

# Oil tank level
$TANK_LEVEL = 0;
if ($result = $mysqli->query("select round((129 - depth) * 100 / 130, 1) as level from oiltank where cap_time = (select max(cap_time) from oiltank)")) {
  while($row = $result->fetch_assoc()){
    $TANK_LEVEL = $row['level'];
  }
  $result->close();
}

# Level around 14 days ago
$OLDDT = 0;
$OLDLVL = 0;
if ($result = $mysqli->query("select cap_time, round((129 - depth) * 100 / 130, 1) as level from oiltank where cap_time = (select max(cap_time) from oiltank where cap_time < adddate(now(), interval -14 day))")) {
  while($row = $result->fetch_assoc()){
    $OLDDT = $row['cap_time'];
    $OLDLVL = $row['level'];
  }
  $result->close();
}

# Latest Level
$NEWDT = 0;
$NEWLVL = 0;
if ($result = $mysqli->query("select cap_time, round((129 - depth) * 100 / 130, 1) as level from oiltank where cap_time = (select max(cap_time) from oiltank)")) {
  while($row = $result->fetch_assoc()){
    $NEWDT = $row['cap_time'];
    $NEWLVL = $row['level'];
  }
  $result->close();
}

# Trends over time
date_default_timezone_set('Europe/London');
$TILLGONE = 300;
$ENDOIL = new DateTime();
$LDIFF = $OLDLVL - $NEWLVL;
echo "<!-- $OLDLVL $NEWLVL -->";
if ($LDIFF >= 0) {
  $DDIFF = date_diff(date_create_from_format("Y-m-d H:i:s", $OLDDT), date_create_from_format("Y-m-d H:i:s", $NEWDT));
  $MINS = ($DDIFF->format('%d') * 24 * 60) + ($DDIFF->format('%h') * 60) + $DDIFF->format('%i');
  $TILLGONE = ($NEWLVL * ($MINS / $LDIFF) / 60 / 24);
  $ENDOIL = new DateTime($NEWDT);
  $ENDOIL->add(new DateInterval('PT' . round($TILLGONE * 24 * 60) . 'M'));
}

##################################
# Syntax for the constructor - new FusionCharts("type of chart", "unique chart id", "width of chart", "height of chart", "div id to render the chart", "type of data", "actual data")
##################################
# Oil level gauges
##################################

# Days remaining gauge
$chartDaysRemaining = new FusionCharts("AngularGauge", "ex1", "100%", "200", "chart-remain", "json", '{
    "chart": {
        "dataStreamUrl": "update.php?data=dremain",
        "refreshInterval": "300",
        "caption": "Oil Days Remaining (2 week trend)",
        "subcaption": "Run Dry: ' . $ENDOIL->format('d-m-Y H:i') . '",
        "lowerlimit": "0",
        "upperlimit": "250",
        "showValue": "1",
        "valueBelowPivot": "1",
        "gaugeFillMix": "{dark-30},{light-60},{dark-10}",
        "gaugeFillRatio": "15",
        "gaugeInnerRadius": "40%",
        "theme": "fint"
    },
    "colorrange": {
        "color": [
            {
                "minvalue": "0",
                "maxvalue": "14",
                "code": "e44a00"
            },
            {
                "minvalue": "10",
                "maxvalue": "30",
                "code": "f8bd19"
            },
            {
                "minvalue": "25",
                "maxvalue": "250",
                "code": "6baa01"
            }
        ]
    },
    "dials": {
        "dial": [
            {
                "value": "' . $TILLGONE . '",
                "rearextension": "8",
                "radius": "85",
                "bgcolor": "333333",
                "bordercolor": "333333"
            }
        ]
    }
}');
$chartDaysRemaining->render();

# Tank level gauge
$chartTankLevel = new FusionCharts("cylinder", "ex2", "100%", "200", "chart-tank", "json", '{
    "chart": {
        "dataStreamUrl": "update.php?data=tlvl",
        "refreshInterval": "300",
        "caption": "Oil Tank View",
        "lowerLimitDisplay": "Empty",
        "upperLimitDisplay": "Full",
        "lowerlimit": "0",
        "upperlimit": "100",
        "numbersuffix": "%",
        "cylfillcolor": "#1aaf5d"
    },
    "value": "' . $TANK_LEVEL . '"
}');
$chartTankLevel->render();

# Tank level gauge (litres)
$chartOilLitres = new FusionCharts("AngularGauge", "ex7", "100%", "200", "chart-litres", "json", '{
    "chart": {
        "dataStreamUrl": "update.php?data=tltrs",
        "refreshInterval": "300",
        "caption": "Oil Level (litres)",
        "lowerlimit": "0",
        "upperlimit": "2500",
        "showValue": "1",
        "valueBelowPivot": "1",
        "gaugeFillMix": "{dark-30},{light-60},{dark-10}",
        "gaugeFillRatio": "15",
        "gaugeInnerRadius": "40%",
        "theme": "fint"
    },
    "colorrange": {
        "color": [
            {
                "minvalue": "0",
                "maxvalue": "150",
                "code": "e44a00"
            },
            {
                "minvalue": "150",
                "maxvalue": "300",
                "code": "f8bd19"
            },
            {
                "minvalue": "300",
                "maxvalue": "2500",
                "code": "6baa01"
            }
        ]
    },
    "dials": {
        "dial": [
            {
                "value": "' . (2500 / 100 * $TANK_LEVEL) . '",
                "rearextension": "8",
                "radius": "85",
                "bgcolor": "333333",
                "bordercolor": "333333"
            }
        ]
    }
}');
$chartOilLitres->render();

##################################
# Temperature values from DB
##################################

# Outside temperature
$TEMP_OUT = 0;
$BATTERY = "";
if ($result = $mysqli->query("select temperature, battery from temperature where cap_time = (select max(cap_time) from temperature)")) {
  while($row = $result->fetch_assoc()){
    $TEMP_OUT = $row['temperature'];
    $BATTERY = $row['battery'];
  }
  $result->close();
}
if ( $BATTERY == "OK" ) {
  $BATTERY = 1;
} else {
  $BATTERY = 0;
}

# Oil tank temp
$TEMP_TANK = 0;
if ($result = $mysqli->query("select temperature from oiltank where cap_time = (select max(cap_time) from oiltank)")) {
  while($row = $result->fetch_assoc()){
    $TEMP_TANK = $row['temperature'];
  }
  $result->close();
}

##################################
# Temperature gauges
##################################

# Outside temperature gauge
$SHOWBULB = false;
if ( $SHOWBULB ) {
  $chartOutsideTemp = new FusionCharts("thermometer", "ex3", "100%", "200", "chart-outside-temp", "json", '{
    "chart": {
        "dataStreamUrl": "update.php?data=otmp",
        "refreshInterval": "30",
        "caption": "Outside Temperature",
        "lowerlimit": "-10",
        "upperlimit": "40",
        "decimals": "1",
        "numberSuffix": "°C",
        "showhovereffect": "1",
        "thmFillColor": "#008ee4",
        "showGaugeBorder": "1",
        "thmOriginX": "200",
        "chartBottomMargin": "20",
        "valueFontColor": "#000000",
        "theme": "fint"
    },
    "value": "' . $TEMP_OUT . '"
}');
  $chartOutsideTemp->render();
} else {
  $chartOutsideTemp = new FusionCharts("AngularGauge", "ex3", "100%", "200", "chart-outside-temp", "json", '{
    "chart": {
        "dataStreamUrl": "update.php?data=otmp",
        "refreshInterval": "30",
        "caption": "Outside Temperature",
        "lowerlimit": "-10",
        "upperlimit": "50",
        "showValue": "1",
        "valueBelowPivot": "1",
        "gaugeFillMix": "{dark-30},{light-60},{dark-10}",
        "gaugeFillRatio": "15",
        "gaugeInnerRadius": "40%",
        "theme": "fint"
    },
    "colorrange": {
        "color": [
            {
                "minvalue": "-10",
                "maxvalue": "5",
                "code": "e44a00"
            },
            {
                "minvalue": "5",
                "maxvalue": "15",
                "code": "f8bd19"
            },
            {
                "minvalue": "15",
                "maxvalue": "25",
                "code": "6baa01"
            },
            {
                "minvalue": "25",
                "maxvalue": "30",
                "code": "f8bd19"
            },
            {
                "minvalue": "30",
                "maxvalue": "50",
                "code": "e44a00"
            }
        ]
    },
    "dials": {
        "dial": [
            {
                "value": "' . $TEMP_OUT . '",
                "rearextension": "8",
                "radius": "85",
                "bgcolor": "333333",
                "bordercolor": "333333"
            }
        ]
    }
}');
  $chartOutsideTemp->render();
}

# Tank temperature gauge
$chartTankTemp = new FusionCharts("thermometer", "ex4", "100%", "200", "chart-tank-temp", "json", '{
    "chart": {
        "dataStreamUrl": "update.php?data=ttmp",
        "refreshInterval": "30",
        "caption": "Oil Tank Temperature",
        "lowerlimit": "-10",
        "upperlimit": "40",
        "decimals": "1",
        "numberSuffix": "°C",
        "showhovereffect": "1",
        "thmFillColor": "#008ee4",
        "showGaugeBorder": "1",
        "thmOriginX": "200",
        "chartBottomMargin": "20",
        "valueFontColor": "#000000",
        "theme": "fint"
    },
    "value": "' . $TEMP_TANK . '"
}');
$chartTankTemp->render();

# Temperature gauge battery
$chartTempBattery = new FusionCharts("bulb", "ex8", "100%", "200", "chart-temp-battery", "json", '{
    "chart": {
        "dataStreamUrl": "update.php?data=battery",
        "refreshInterval": "30",
        "caption": "Battery Status",
        "upperlimit": "1",
        "lowerlimit": "0",
        "captionPadding": "30",
        "showshadow": "0",
        "showvalue": "1",
        "useColorNameAsValue": "1",
        "placeValuesInside": "1",
        "theme": "fint"
     },
     "colorrange": {
        "color": [
            {
                "minvalue": "-60",
                "maxvalue": "0",
                "label": "Bat Flattery!",
                "code": "#ff0000"
            },
            {
                "minvalue": "1",
                "maxvalue": "1",
                "label": "All good",
                "code": "#00ff00"
            }
        ]
    },
    "value": "' . $BATTERY . '"
}');
$chartTempBattery->render();

# Clean up
mysqli_close($mysqli);

?>

<script>
var imageCount = 6;

function setImageTimer() {
  // Set timer to happen at the end of the next minute
  var d = new Date();
  setTimeout(function(){updateImages()}, ((60 - d.getSeconds()) * 1000));
}

function updateImages() {
  for (x = 1; x <= imageCount; x++) {
    updateImage(x);
  }
  // Set timer to happen at the end of the next minute
  var d = new Date();
  setTimeout(function(){updateImages()}, ((60 - d.getSeconds()) * 1000));
}

function updateImage(imageNum) {
  // Update image, forced by addition of current date to URI
  var imageName = "webcam" + imageNum;
  var image = document.getElementById(imageName);
  if ( image.complete ) {
    image.src = "cam" + imageNum + "/lastsnap.jpg?time=" + new Date();
  }
}

</script>

</head>
<body>
</head>
<body onload="setImageTimer();">
<center>
<table width="90%" border="0">
<tr><td width="50%">
<table width="788" border="2">
<tr><th colspan="2">Oil Tank</th></tr>
<tr>
  <td width="50%"><a href="oil.php"><div id="chart-litres"></div></a></td>
  <td width="50%"><a href="oil.php"><div id="chart-tank"></div></a></td>
</tr>
<tr>
  <td><a href="oil.php"><div id="chart-remain"></div></a></td>
  <td><a href="oil.php"><div id="chart-tank-temp"></div></a></td>
</tr>
<tr><th colspan="2">Temperature</th></tr>
<tr>
  <td><a href="temp.php"><div id="chart-outside-temp"></div></a></td>
  <td><a href="temp.php"><div id="chart-temp-battery"></div></a></td>
</tr>
</table>
</td><td width="50%">
<table width="788" border="2">
<tr><th colspan="2">Cameras</th></tr>
<tr><td colspan="2">
<a href="cam1/events.php?cam=1"><img id="webcam1" width="256" height="196" src="cam1/lastsnap.jpg"/></a>
<a href="cam2/events.php?cam=2"><img id="webcam2" width="256" height="196" src="cam2/lastsnap.jpg"/></a>
<a href="cam3/events.php?cam=3"><img id="webcam3" width="256" height="196" src="cam3/lastsnap.jpg"/></a>
<a href="cam4/events.php?cam=4"><img id="webcam4" width="256" height="196" src="cam4/lastsnap.jpg"/></a>
<a href="cam5/events.php?cam=5"><img id="webcam5" width="256" height="196" src="cam5/lastsnap.jpg"/></a>
<a href="cam6/events.php?cam=6"><img id="webcam6" width="256" height="196" src="cam6/lastsnap.jpg"/></a>
</td></tr>
<tr><th colspan="2">Planes</th></tr>
<tr><td width="50%"><a href="/fr24"><div id="chart-plane-count"></div></a></td><td width="50%"><a href="/fr24"><div id="chart-plane-distance"></div></a></td></tr>
</table>
</td></tr>
</table>
</center>
</body>
</html>
