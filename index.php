<?php
$siteTitle = getenv("SITE_TITLE");

$parameters = array(
    "api-key" => getenv("WEATHERLINK_V2_API_KEY"),
    "station-id" => getenv("STATION_ID"),
    "t" => time()
);

ksort($parameters);

$stringToHash = "";
foreach ($parameters as $key => $value) {
    $stringToHash = $stringToHash . $key . $value;
}

$apiSignature = hash_hmac("sha256", $stringToHash, getenv("WEATHERLINK_V2_API_SECRET"));

$json = file_get_contents(getenv("WEATHERLINK_V2_API_BASE_URL") .
    "/current/" . $parameters["station-id"] .
    "?api-key=" . $parameters["api-key"] .
    "&api-signature=$apiSignature" .
    "&t=" . $parameters["t"]
);

$data = json_decode($json);

$sensors = $data->sensors;
$aqSensor = null;
$sensorId = (int) getenv("SENSOR_ID");
foreach ($sensors as $sensor) {
    if ($sensor->lsid == $sensorId) {
        $aqSensor = $sensor;
    }
}

$recordTs = null;
$pm25 = null;
$pm25AqiValue = null;
$pm25AqiDesc = null;
if ($aqSensor != null) {
    $record = $aqSensor->data[0];

    $recordTs = $record->ts;
    $pm25 = $record->pm_2p5;
    $pm25AqiValue = $record->aqi_val;
    $pm25AqiDesc = $record->aqi_desc;
}

?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" integrity="sha384-JcKb8q3iqJ61gNV9KGb8thSsNjpSL0n8PARn9HuZOnIxN0hoP+VmmDGMN5t9UJ0Z" crossorigin="anonymous">

<title><?php echo( $siteTitle); ?></title>
</head>
<body>
<p>Last Data Reading at <?php echo($recordTs); ?></p>
<p>PM 2.5 Concentration = <?php echo($pm25); ?></p>
<p>PM 2.5 AQI = <?php echo($pm25AqiValue); ?> <?php echo($pm25AqiDesc); ?></p>
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js" integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js" integrity="sha384-9/reFTGAW83EW2RDu2S0VKaIzap3H66lZH81PoYlFhbGU+6BZp6G7niu735Sk7lN" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js" integrity="sha384-B4gt1jrGC7Jh4AgTPSdUtOBvfO8shuf57BaghqFfPlYxofvL8/KUEfYiJOMMV+rV" crossorigin="anonymous"></script>
</body>
</html>
