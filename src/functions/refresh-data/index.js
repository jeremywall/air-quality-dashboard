const _ = require("lodash");
const crypto = require("crypto");
const fetch = require('node-fetch');

const aqi_breakpoints = [
    [0, 50],
    [51, 100],
    [101, 150],
    [151, 200],
    [201, 300],
    [301, 400],
    [401, 500],
];

const pm25_breakpoints = [
    [0.0, 12.0],
    [12.1, 35.4],
    [35.5, 55.4],
    [55.5, 150.4],
    [150.5, 250.4],
    [250.5, 350.4],
    [350.5, 500.4],
];

function breakpointIndex(value, breakpoints) {
    return _.findIndex(breakpoints, function(breakpoint) {
        if (null === breakpoint) {
            return false;
        }
        return breakpoint[0] <= value && value <= breakpoint[1];
    });
}

function aqi(concentration, breakpoints) {
    let index = breakpointIndex(concentration, breakpoints);

    if (-1 === index) {
        return NaN;
    }

    let i_high = aqi_breakpoints[index][1];
    let i_low = aqi_breakpoints[index][0];
    let c_high = breakpoints[index][1];
    let c_low = breakpoints[index][0];

    return (i_high - i_low) / (c_high - c_low) * (concentration - c_low) + i_low;
}

function getCurrentBaseParameters() {
    let now = Math.floor(Date.now() / 1000);
    return {
        "api-key": process.env.WEATHERLINK_V2_API_KEY,
        "station-id": process.env.STATION_ID
    };
}

function getHistoricBaseParameters() {
    let now = Math.floor(Date.now() / 1000);
    return {
        "api-key": process.env.WEATHERLINK_V2_API_KEY,
        "station-id": process.env.STATION_ID,
        "start-timestamp": now - (3600 * 3),
        "end-timestamp": now
    };
}

exports.handler = async function(event, context) {
    const data = {
        current: {
            data: []    // { timestamp: number, pm25: number, pm25_aqi_value: number, pm25_aqi_desc: string }
        },
        historic: {
            data: []    // { timestamp: number, pm25_aqi_value: number }
        }
    };

    const sensorId = +process.env.SENSOR_ID;

    const currentParameters = getCurrentBaseParameters();

    const headers = {
        "X-Api-Secret": process.env.WEATHERLINK_V2_API_SECRET
    };

    const currentUrl = process.env.WEATHERLINK_V2_API_BASE_URL + "/current/" + currentParameters["station-id"] + "?api-key=" + currentParameters["api-key"];
    const currentResponse = await fetch(currentUrl, { headers: headers });
    const currentJson = await currentResponse.json();
    data.raw_current = currentJson;
    data.url_current = currentUrl;

    let currentSensor = _.find(currentJson.sensors, {lsid: sensorId});
    if (!_.isNil(currentSensor)) {
        let dataRecord = currentSensor.data[0];

        data.current.data.push({
            timestamp: dataRecord.ts,
            pm25: dataRecord.pm_2p5,
            pm25_aqi_value: _.round(dataRecord.aqi_val, 1),
            pm25_aqi_desc: dataRecord.aqi_desc,
            pm25_ws: dataRecord.pm_2p5 * 0.48,
            pm25_aqi_value_calc: aqi(dataRecord.pm_2p5),
            pm25_aqi_value_ws_calc: aqi(dataRecord.pm_2p5 * 0.48)
        });
    }

    if (_.has(event.queryStringParameters, "historic")) {
        const historicParameters = getHistoricBaseParameters();

        const historicUrl = process.env.WEATHERLINK_V2_API_BASE_URL + "/historic/" + historicParameters["station-id"] +
            "?api-key=" + historicParameters["api-key"] +
            "&start-timestamp=" + historicParameters["start-timestamp"] +
            "&end-timestamp=" + historicParameters["end-timestamp"];
        const historicResponse = await fetch(historicUrl, { headers: headers });
        const historicJson = await historicResponse.json();
        data.raw_historic = historicJson;
        data.url_historic = historicUrl;

        let historicSensor = _.find(historicJson.sensors, {lsid: sensorId});
        if (!_.isNil(historicSensor)) {
            for (let i = 0 ; i < historicSensor.data.length ; i++) {
                let dataRecord = historicSensor.data[i];

                data.historic.data.push({
                    timestamp: dataRecord.ts,
                    pm25_aqi_value: _.round(dataRecord.aqi_avg_val, 1)
                });
            }
        }
    }

    return {
        statusCode: 200,
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify(data)
    };
}
