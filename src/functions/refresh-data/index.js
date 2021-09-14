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
    data.raw_current = currentJson;

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

    if (_.has(event.queryStringParameters, "historic") || new Date().getMinutes() % 5 == 0) {
        const historicParameters = getParameters(getHistoricBaseParameters());

        const historicUrl = process.env.WEATHERLINK_V2_API_BASE_URL + "/historic/" + historicParameters["station-id"] +
            "?api-key=" + historicParameters["api-key"] +
            "&api-signature=" + historicParameters["api-signature"] +
            "&start-timestamp=" + historicParameters["start-timestamp"] +
            "&end-timestamp=" + historicParameters["end-timestamp"] +
            "&t=" + historicParameters["t"];
        const historicResponse = await fetch(historicUrl);
        const historicJson = await historicResponse.json();
        data.raw_historic = historicJson;

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
