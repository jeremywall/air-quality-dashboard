const _ = require("lodash");
const crypto = require("crypto");
const fetch = require('node-fetch');

function getCurrentBaseParameters() {
    let now = Math.floor(Date.now() / 1000);
    return {
        "api-key": process.env.WEATHERLINK_V2_API_KEY,
        "station-id": process.env.STATION_ID,
        "t": now
    };
}

function getHistoricBaseParameters() {
    let now = Math.floor(Date.now() / 1000);
    return {
        "api-key": process.env.WEATHERLINK_V2_API_KEY,
        "station-id": process.env.STATION_ID,
        "start-timestamp": now - (3600 * 3),
        "end-timestamp": now,
        "t": now
    };
}

function getParameters(baseParameters) {
    let sortedParameterNames = _.keys(baseParameters).sort();

    let stringToHash = "";
    for (let i = 0 ; i < sortedParameterNames.length ; i++) {
        let parameterName = sortedParameterNames[i];
        stringToHash = stringToHash + parameterName + baseParameters[parameterName];
    }

    let hmac = crypto.createHmac("sha256", process.env.WEATHERLINK_V2_API_SECRET);
    hmac.update(stringToHash);
    let apiSignature = hmac.digest("hex").toLowerCase();

    let parameters = _.clone(baseParameters);
    parameters["api-signature"] = apiSignature;

    return parameters;
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

    const currentParameters = getParameters(getCurrentBaseParameters());

    const currentUrl = process.env.WEATHERLINK_V2_API_BASE_URL + "/current/" + currentParameters["station-id"] +
        "?api-key=" + currentParameters["api-key"] +
        "&api-signature=" + currentParameters["api-signature"] +
        "&t=" + currentParameters["t"];
    const currentResponse = await fetch(currentUrl);
    const currentJson = await currentResponse.json();
    data.extra = currentJson;

    let currentSensor = _.find(currentJson.sensors, {lsid: sensorId});
    if (!_.isNil(currentSensor)) {
        let dataRecord = currentSensor.data[0];

        data.current.data.push({
            timestamp: dataRecord.ts,
            pm25: dataRecord.pm_2p5,
            pm25_aqi_value: _.round(dataRecord.aqi_val, 1),
            pm25_aqi_desc: dataRecord.aqi_desc,
        });
    }

    return {
        statusCode: 200,
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify(data)
    };
}
