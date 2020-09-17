<?php
$siteTitle = getenv("SITE_TITLE");

// get current data
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

$currentData = null;
if ($aqSensor != null) {
    $currentData = new stdClass();

    $record = $aqSensor->data[0];

    $recordDateTime = new \DateTime();
    $recordDateTime->setTimestamp($record->ts);
    $recordDateTime->setTimezone(new \DateTimeZone(getenv("TIMEZONE")));
    
    $currentData->dateTime = $recordDateTime;

    $currentData->pm25 = $record->pm_2p5;
    $currentData->pm25AqiValue = round($record->aqi_val, 1);
    $currentData->pm25AqiDesc = $record->aqi_desc;
}

// get trend data
$parameters = array(
    "api-key" => getenv("WEATHERLINK_V2_API_KEY"),
    "station-id" => getenv("STATION_ID"),
    "start-timestamp" => time() - (3600 * 12),
    "end-timestamp" => time(),
    "t" => time()
);

ksort($parameters);

$stringToHash = "";
foreach ($parameters as $key => $value) {
    $stringToHash = $stringToHash . $key . $value;
}

$apiSignature = hash_hmac("sha256", $stringToHash, getenv("WEATHERLINK_V2_API_SECRET"));

$json = file_get_contents(getenv("WEATHERLINK_V2_API_BASE_URL") .
    "/historic/" . $parameters["station-id"] .
    "?api-key=" . $parameters["api-key"] .
    "&api-signature=$apiSignature" .
    "&start-timestamp=" . $parameters["start-timestamp"] .
    "&end-timestamp=" . $parameters["end-timestamp"] .
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

$trendData = null;
if ($aqSensor != null) {
    $trendData = new stdClass();

    $trendData->avg = new stdClass();
    $trendData->avg->data = [];
    
    $trendData->hi = new stdClass();
    $trendData->hi->data = [];
    
    foreach ($aqSensor->data as $record) {
        $recordDateTime = new \DateTime();
        $recordDateTime->setTimestamp($record->ts);
        $recordDateTime->setTimezone(new \DateTimeZone(getenv("TIMEZONE")));
        
        $avgArr = [$record->ts * 1000, round($record->aqi_avg_val, 1)];
        $trendData->avg->data[] = $avgArr;
        
        $hiArr = [$record->ts * 1000, round($record->aqi_hi_val, 1)];
        $trendData->hi->data[] = $hiArr;
    }
}



?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" integrity="sha384-JcKb8q3iqJ61gNV9KGb8thSsNjpSL0n8PARn9HuZOnIxN0hoP+VmmDGMN5t9UJ0Z" crossorigin="anonymous">
<style type="text/css">
#aqi-current-gauge-container {
    height: 400px;
}
#aqi-trend-chart-container {
    height: 400px;
}
</style>
<title><?php echo( $siteTitle); ?></title>
</head>
<body>

<div id="aqi-current-gauge-container"></div>
<div id="aqi-trend-chart-container"></div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js" integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js" integrity="sha384-9/reFTGAW83EW2RDu2S0VKaIzap3H66lZH81PoYlFhbGU+6BZp6G7niu735Sk7lN" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js" integrity="sha384-B4gt1jrGC7Jh4AgTPSdUtOBvfO8shuf57BaghqFfPlYxofvL8/KUEfYiJOMMV+rV" crossorigin="anonymous"></script>
<script src="https://code.highcharts.com/highcharts.js"></script>
<script src="https://code.highcharts.com/highcharts-more.js"></script>
<?php if ($currentData != null) { ?>
<script>
    Highcharts.chart('aqi-current-gauge-container', {
        chart: {
            type: 'gauge',
            plotBackgroundColor: null,
            plotBackgroundImage: null,
            plotBorderWidth: 0,
            plotShadow: false
        },
        title: {
            text: 'Last Updated: <?php echo($currentData->dateTime->format("M j, Y @ h:i A")); ?><br>Current PM 2.5 AQI<br><?php echo($currentData->pm25AqiDesc); ?>'
        },
        credits: {
            enabled: false
        },
        pane: {
            startAngle: -135,
            endAngle: 135,
            background: [ ]
        },
        // the value axis
        yAxis: {
            min: 0,
            max: 500,
            minorTickInterval: 'auto',
            minorTickWidth: 1,
            minorTickLength: 10,
            minorTickPosition: 'inside',
            minorTickColor: '#000',
            tickPixelInterval: 30,
            tickWidth: 2,
            tickPosition: 'inside',
            tickLength: 10,
            tickColor: '#000',
            labels: {
                step: 2,
                rotation: 'auto',
                distance: 5
            },
            plotBands: [{
                innerRadius: 50,
                from: 0,
                to: 50,
                color: '#0bab8b' // green
            }, {
                innerRadius: 50,
                from: 50,
                to: 100,
                color: '#ede400' // yellow
            }, {
                innerRadius: 50,
                from: 100,
                to: 150,
                color: '#ed8b00' // orange
            }, {
                innerRadius: 50,
                from: 150,
                to: 200,
                color: '#bd0000' // red
            }, {
                innerRadius: 50,
                from: 200,
                to: 300,
                color: '#a7005b' // purple
            }, {
                innerRadius: 50,
                from: 300,
                to: 500,
                color: '#5f0000' // maroon
            }]
        },
        series: [{
            name: 'AQI',
            data: [<?php echo($currentData->pm25AqiValue); ?>]
        }]
    });
</script>
<?php } ?>
<?php if ($trendData != null) { ?>
<script>
    Highcharts.chart('aqi-trend-chart-container', {
        chart: {
            type: 'spline',
            plotBackgroundColor: null,
            plotBackgroundImage: null,
            plotBorderWidth: 0,
            plotShadow: false
        },
        title: {
            text: 'Last 12 Hours Avg and High'
        },
        credits: {
            enabled: false
        },
        // the value axis
        yAxis: {
            min: 0,
            max: 500
        },
        series: [{
            name: 'Avg',
            data: <?php echo(json_encode($trendData->avg->data)); ?>
        },{
            name: 'High',
            data: <?php echo(json_encode($trendData->hi->data)); ?>
        }]
    });
</script>
<?php } ?>
</body>
</html>
