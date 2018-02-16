&value=<?php

$WANTS = 'otmp';
if (array_key_exists('data', $_GET)) {
  $WANTS = strtolower($_GET['data']);
}

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


function getPlaneCount() {
  $json = file_get_contents('http://192.168.0.58:8090/dump1090/data.json');
  $obj = json_decode($json, true);

  $PCNT = 0;

  foreach ($obj as $plane) {
    if ( $plane['validposition'] == '1' && $plane['validtrack'] == '1' ) {
      $PCNT++;
    }
  }
  echo $PCNT;
}

function getPlaneDistance() {
  $json = file_get_contents('http://192.168.0.58:8090/dump1090/data.json');
  $obj = json_decode($json, true);

  $baseLat = 51.300261;
  $baseLon = 0.579266;
  $earthRad = 3440;

  $furthest = 0;

  foreach ($obj as $plane) {
    if ( $plane['validposition'] == '1' && $plane['validtrack'] == '1' && $plane['seen'] <= 60 ) {
      $planeDist = vincentyGreatCircleDistance($baseLat, $baseLon, $plane['lat'], $plane['lon'], $earthRad);
      if ( $planeDist > $furthest ) {
        $furthest = $planeDist;
      }
    }
  }
  echo $furthest;
}

function getTankData($mysqli, $WANTS) {
  $PCT = 0;
  $TMP = 0;
  $AGE = 0;
  # Oil tank level and temp
  if ($result = $mysqli->query("select temperature, round((129-depth) * 100 / 130, 1) as level, ((UNIX_TIMESTAMP(cap_time) - UNIX_TIMESTAMP()) /60) as datage from oiltank where cap_time = (select max(cap_time) from oiltank)")) {
    while($row = $result->fetch_assoc()){
      $PCT = $row['level'];
      $TMP = $row['temperature'];
      $AGE = $row['datage'];
    }
    $result->close();
  }
  switch ($WANTS) {
    case "tlvl":
      echo $PCT;
      break;
    case "tltrs":
      echo (2500 / 100 * $PCT);
      break;
    case "oilage":
      echo $AGE;
      break;
    default:
      echo $TMP;
      break;
  }
}

function getTemp($mysqli, $WANTS) {
  $TMPo = 0;
  $AGE = 0;
  # Outside temperature
  if ($result = $mysqli->query("select temperature, ((UNIX_TIMESTAMP(cap_time) - UNIX_TIMESTAMP()) /60) as datage from temperature where cap_time = (select max(cap_time) from temperature)")) {
    while($row = $result->fetch_assoc()){
      $TMPo = $row['temperature'];
      $AGE = $row['datage'];
    }
    $result->close();
  }
  switch ($WANTS) {
    case "tempage":
      echo $AGE;
      break;
    default:
      echo $TMPo;
      break;
  }
}

function getBattery($mysqli) {
  $BATT = 0;
  # Outside temperature
  if ($result = $mysqli->query("select battery from temperature where cap_time = (select max(cap_time) from temperature)")) {
    while($row = $result->fetch_assoc()){
      $BATT = $row['battery'];
    }
    $result->close();
  }
  if ( $BATT == "OK" ) {
    $BATT = 1;
  } else {
    $BATT = 0;
  }
  echo $BATT;
}

function getDayRemaining($mysqli) {
  # Level around 14 days ago
  $OLDDT = 0;
  $OLDLVL = 0;
  if ($result = $mysqli->query("select cap_time, round((129-depth) * 100 / 130, 1) as level from oiltank where cap_time = (select max(cap_time) from oiltank where cap_time < adddate(now(), interval -14 day))")) {
    while($row = $result->fetch_assoc()){
      $OLDDT = $row['cap_time'];
      $OLDLVL = $row['level'];
    }
    $result->close();
  }

  # Latest Level
  $NEWDT = 0;
  $NEWLVL = 0;
  if ($result = $mysqli->query("select cap_time, round((129-depth) * 100 / 130, 1) as level from oiltank where cap_time = (select max(cap_time) from oiltank)")) {
    while($row = $result->fetch_assoc()){
      $NEWDT = $row['cap_time'];
      $NEWLVL = $row['level'];
    }
    $result->close();
  }

  # Difference between Old and New levels over time
  $TILLGONE = 300;
  $LDIFF = $OLDLVL - $NEWLVL;
  if ($LDIFF >= 0) {
    date_default_timezone_set('Europe/London');
    $DDIFF = date_diff(date_create_from_format("Y-m-d H:i:s", $OLDDT), date_create_from_format("Y-m-d H:i:s", $NEWDT));
    $MINS = ($DDIFF->format('%d') * 24 * 60) + ($DDIFF->format('%h') * 60) + $DDIFF->format('%i');
    $TILLGONE = ($NEWLVL * ($MINS / $LDIFF) / 60 / 24);
  }
  echo $TILLGONE;
}

function getMysqlData($WANTS) {
  ##################################
  # Temperature and oil gauges
  ##################################
  $mysqli = new mysqli("localhost", "ian", null, "temps");

  switch ($WANTS) {
    case "ttmp":
      getTankData($mysqli, $WANTS);
      break;
    case "tlvl":
      getTankData($mysqli, $WANTS);
      break;
    case "tltrs":
      getTankData($mysqli, $WANTS);
      break;
    case "oilage":
      getTankData($mysqli, $WANTS);
      break;
    case "dremain":
      getDayRemaining($mysqli);
      break;
    case "battery":
      getBattery($mysqli);
      break;
    default:
      getTemp($mysqli, $WANTS);
  }

  mysqli_close($mysqli);
}

##################################

switch ($WANTS) {
  case "planecnt":
    getPlaneCount();
    break;
  case "planedist":
    getPlaneDistance();
    break;
  default:
    getMysqlData($WANTS);
}

?>
