<html>
  <head>
    <link rel="stylesheet" href="/style.css" type="text/css" charset="utf-8" /> 
    <META HTTP-EQUIV="refresh" CONTENT="60">
    <title>Oil Tank</title>
    <script type="text/javascript" src="https://www.google.com/jsapi"></script>
    <script type="text/javascript">
      google.load("visualization", "1", {packages:["corechart"]});
      google.setOnLoadCallback(drawChart);
      function drawChart() {
        var data = google.visualization.arrayToDataTable([
<?php
print("['Time', 'Temperature', 'Oil Level'],");
$mysqli = new mysqli("localhost", "ian", null, "temps");
if ($result = $mysqli->query("select date_format(cap_time, '%d-%b') as c_time, temperature, round((129-depth)*100/130,1) as depth from oiltank where cap_time > DATE_SUB(NOW(), INTERVAL 400 DAY) order by cap_time")) {
while($row = $result->fetch_assoc()){
  print("['" . $row['c_time'] . "', " . $row['temperature'] . ", " . $row['depth'] . "],\n");
}
    /* free result set */
    $result->close();
}
mysqli_close($mysqli);
?>
        ]);

        var options = {
          title: 'Temperature (C) Oil Level (%)',
          curveType: 'none',
          legend: { position: 'bottom' }
        };

        var chart = new google.visualization.LineChart(document.getElementById('chart_div'));

        chart.draw(data, options);
      }
    </script>
  </head>
  <body>
<center>
    <div id="chart_div" style="width: 900px; height: 500px;"></div>
<a href='temp.php'>Temperatures</a><br/>
<a href='humid.php'>Humidity</a><br/>
<a href="/">Click here to go back</a>
</center>
  </body>
</html>
