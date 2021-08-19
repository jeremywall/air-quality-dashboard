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
    $currentData->pm25ws = $record->pm_2p5 * 0.48;
    $currentData->pm25AqiValue = round($record->aqi_val, 1);
    $currentData->pm25AqiDesc = $record->aqi_desc;
}

// get trend data
$parameters = array(
    "api-key" => getenv("WEATHERLINK_V2_API_KEY"),
    "station-id" => getenv("STATION_ID"),
    "start-timestamp" => time() - (3600 * 3),
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
<!--<meta http-equiv="refresh" content="60">-->
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.0/css/bootstrap.min.css" integrity="sha512-F7WyTLiiiPqvu2pGumDR15med0MDkUIo5VTVyyfECR5DZmCnDhti9q5VID02ItWjq6fvDfMaBaDl2J3WdL1uxA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
<style type="text/css">
.aqi-current-gauge-container {
    height: 400px;
}
#aqi-trend-chart-container {
    height: 400px;
}
#bottom-padding-container {
    height: 200px;
}
</style>
<title><?php echo( $siteTitle); ?></title>
</head>
<body>
<div class="container">
    <div class="row">
        <div id="title" class="col">Last Updated: <?php echo($currentData->dateTime->format("M j, Y @ h:i A")); ?><br>Current PM 2.5 AQI<br><?php echo($currentData->pm25AqiDesc); ?></div>
    </div>
    <div class="row">
        <div id="aqi-current-gauge-container1" class="col aqi-current-gauge-container"></div>
    </div>
    <div class="row">
        <div id="aqi-trend-chart-container" class="col"></div>
    </div>
    <div class="row">
        <div id="bottom-padding-container" class="col"></div>
    </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.0/js/bootstrap.bundle.min.js" integrity="sha512-PqRelaJGXVuQ81N6wjUrRQelCDR7z8RvKGiR9SbSxKHPIt15eJDmIVv9EJgwq0XvgylszsjzvQ0+VyI2WtIshQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.slim.min.js" integrity="sha512-6ORWJX/LrnSjBzwefdNUyLCMTIsGoNP6NftMy2UAm1JBm6PRZCO1d7OHBStWpVFZLO+RerTvqX/Z9mBFfCJZ4A==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js" integrity="sha512-qTXRIMyZIFb8iQcfjXWCO8+M5Tbc38Qi5WzdPOYZHIlZpzBHG3L3by84BBBOiRGiEb7KKtAOAs5qYdUiZiQNNQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment-timezone/0.5.33/moment-timezone-with-data.min.js" integrity="sha512-rjmacQUGnwQ4OAAt3MoAmWDQIuswESNZwYcKC8nmdCIxAVkRC/Lk2ta2CWGgCZyS+FfBWPgaO01LvgwU/BX50Q==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/highcharts/9.2.0/highcharts.min.js" integrity="sha512-kn4TcXE2oE4PiPDkcEqRHHjdNLTNDuk/OWjPlZKHGxfWGVpcBwutjJKdfUwhjlCtDB55YBEey3LqFMgJmigWIA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/highcharts/9.2.0/highcharts-more.min.js" integrity="sha512-6ihnpiPyliUGs7Kp28EbOs3yVw7W2z0n7HOO4udA+adzezinDHSidP5WlkyJXDBbiBmGo8PEgjO2k1IkhTmNvQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script>
    Highcharts.setOptions({
        time: {
            timezone: '<?php echo(getenv("TIMEZONE")); ?>'
        }
    });
</script>
<?php if ($currentData != null) { ?>
<script>
    Highcharts.chart('aqi-current-gauge-container1', {
        chart: {
            type: 'gauge',
            plotBackgroundColor: null,
            plotBackgroundImage: null,
            plotBorderWidth: 0,
            plotShadow: false
        },
        title: null,
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
            data: [<?php echo($currentData->pm25AqiValue); ?>],
            dial: {
                backgroundColor: '#000000',
                borderColor: '#cccccc',
                baseWidth: 8
            }
        }, {
            name: 'AQI WS',
            data: [<?php echo($currentData->pm25AqiValue * 0.48); ?>],
            dial: {
                backgroundColor: '#cccccc',
                borderColor: '#000000',
                baseWidth: 8
            }
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
        plotOptions: {
            spline: {
                dataLabels: {
                    enabled: true
                }
            }
        },
        title: {
            text: 'Last 3 Hours Avg PM 2.5 AQI'
        },
        credits: {
            enabled: false
        },
        yAxis: {
            min: 0,
            max: 500,
            gridLineWidth: 0,
            title: {
                enabled: false
            },
            plotBands: [{
                from: 0,
                to: 50,
                color: '#0bab8b55' // green
            }, {
                from: 50,
                to: 100,
                color: '#ede40055' // yellow
            }, {
                from: 100,
                to: 150,
                color: '#ed8b0055' // orange
            }, {
                from: 150,
                to: 200,
                color: '#bd000055' // red
            }, {
                from: 200,
                to: 300,
                color: '#a7005b55' // purple
            }, {
                from: 300,
                to: 500,
                color: '#5f000055' // maroon
            }]
        },
        xAxis: {
            type: 'datetime'
        },
        series: [{
            name: 'Avg',
            color: '#000000',
            data: <?php echo(json_encode($trendData->avg->data)); ?>
        }]
    });
</script>
<?php } ?>
</body>
</html>
