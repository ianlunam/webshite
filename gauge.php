<html>
<head>
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

$baseLat = 51.1541;
$baseLon = -0.2255;
$earthRad = 3440.227;

$FURTHEST = 0;
$PLANE_COUNT = 0;

foreach ($obj as $plane) {
  if ( $plane['validposition'] == '1' && $plane['validtrack'] == '1' ) {
    $PLANE_COUNT++;
    $planeDist = vincentyGreatCircleDistance($baseLat, $baseLon, $plane['lat'], $plane['lon'], $earthRad);
    if ( $planeDist > $FURTHEST ) {
      $FURTHEST = $planeDist;
    }
  }
}

# Convert to KM
$FURTHEST = $FURTHEST * 1.60934;

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
        "bgcolor": "FFFFFF",
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
                "rearextension": "15",
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
        "caption": "Most Distant Plane (km)",
        "lowerlimit": "0",
        "upperlimit": "300",
        "bgcolor": "FFFFFF",
        "gaugeFillMix": "{dark-30},{light-60},{dark-10}",
        "gaugeFillRatio": "15",
        "gaugeInnerRadius": "40%",
        "theme": "fint"
    },
    "colorrange": {
        "color": [
            {
                "minvalue": "0",
                "maxvalue": "50",
                "code": "e44a00"
            },
            {
                "minvalue": "25",
                "maxvalue": "100",
                "code": "f8bd19"
            },
            {
                "minvalue": "50",
                "maxvalue": "300",
                "code": "6baa01"
            }
        ]
    },
    "dials": {
        "dial": [
            {
                "value": "' . $FURTHEST . '",
                "rearextension": "15",
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

$mysqli = new mysqli("localhost", "ian", null, "temps");

# Oil tank level
$TANK_LEVEL = 0;
if ($result = $mysqli->query("select round((129-humidity)*100/130,1) as level from oil where cap_time = (select max(cap_time) from oil)")) {
  while($row = $result->fetch_assoc()){
    $TANK_LEVEL = $row['level'];
  }
  $result->close();
}

# Level around 14 days ago
$OLDDT = 0;
$OLDLVL = 0;
if ($result = $mysqli->query("select cap_time, round((129-humidity)*100/130,1) as level from oil where cap_time = (select max(cap_time) from oil where cap_time < adddate(now(), interval -14 day))")) {
  while($row = $result->fetch_assoc()){
    $OLDDT = $row['cap_time'];
    $OLDLVL = $row['level'];
  }
  $result->close();
}

# Latest Level
$NEWDT = 0;
$NEWLVL = 0;
if ($result = $mysqli->query("select cap_time, round((129-humidity)*100/130,1) as level from oil where cap_time = (select max(cap_time) from oil)")) {
  while($row = $result->fetch_assoc()){
    $NEWDT = $row['cap_time'];
    $NEWLVL = $row['level'];
  }
  $result->close();
}

# Trends over time
$TILLGONE = 300;
$LDIFF = $OLDLVL - $NEWLVL;
if ($LDIFF >= 0) {
  date_default_timezone_set('Europe/London');
  $DDIFF = date_diff(date_create_from_format("Y-m-d H:i:s", $OLDDT), date_create_from_format("Y-m-d H:i:s", $NEWDT));
  $MINS = ($DDIFF->format('%d')*24*60) + ($DDIFF->format('%h') *60) + $DDIFF->format('%i');
  $TILLGONE = ($NEWLVL*($MINS/$LDIFF)/60/24);
  $ENDOIL = new DateTime($NEWDT);
  $ENDOIL->add(new DateInterval('PT' . round($TILLGONE*24*60) . 'M'));
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
        "bgcolor": "FFFFFF",
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
                "rearextension": "15",
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
        "bgcolor": "FFFFFF",
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
        "bgcolor": "FFFFFF",
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
                "value": "' . (2500/100*$TANK_LEVEL) . '",
                "rearextension": "15",
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
if ($result = $mysqli->query("select temperature from history where cap_time = (select max(cap_time) from history)")) {
  while($row = $result->fetch_assoc()){
    $TEMP_OUT = $row['temperature'];
  }
  $result->close();
}

# Oil tank temp
$TEMP_TANK = 0;
if ($result = $mysqli->query("select temperature from oil where cap_time = (select max(cap_time) from oil)")) {
  while($row = $result->fetch_assoc()){
    $TEMP_TANK = $row['temperature'];
  }
  $result->close();
}

##################################
# Temperature gauges
##################################

# Outside temperature gauge
$chartOutsideTemp = new FusionCharts("thermometer", "ex3", "100%", "200", "chart-outside-temp", "json", '{
    "chart": {
        "dataStreamUrl": "update.php?data=otmp",
        "refreshInterval": "30",
        "caption": "Outside Temperature",
        "lowerlimit": "-10",
        "upperlimit": "40",
        "bgcolor": "FFFFFF",
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

# Tank temperature gauge
$chartTankTemp = new FusionCharts("thermometer", "ex4", "100%", "200", "chart-tank-temp", "json", '{
    "chart": {
        "dataStreamUrl": "update.php?data=ttmp",
        "refreshInterval": "30",
        "caption": "Oil Tank Temperature",
        "lowerlimit": "-10",
        "upperlimit": "40",
        "bgcolor": "FFFFFF",
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

# Clean up
mysqli_close($mysqli);

?>
</head>
<body>
</head>
<body>
<center>
<table width="50%" border="2">
<tr><th colspan="2">Oil</th></tr>
<tr><td width="50%"><div id="chart-litres"></div></td><td width="50%"><div id="chart-tank"></div></td></tr>
<tr><td colspan="2"><div id="chart-remain"></div></td></tr>
<tr><th colspan="2">Temperature</th></tr>
<tr><td width="50%"><div id="chart-outside-temp"></div></td><td width="50%"><div id="chart-tank-temp"></div></td></tr>
<tr><th colspan="2">Planes</th></tr>
<tr><td width="50%"><div id="chart-plane-count"></div></td><td width="50%"><div id="chart-plane-distance"></div></td></tr>
</table>
</center>
</body>
</html>
