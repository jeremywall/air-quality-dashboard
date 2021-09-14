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
    for (let parameterName in sortedParameterNames) {
        stringToHash = stringToHash + parameterName + baseParameters[parameterName];
    }
    console.log(stringToHash);

    let hmac = crypto.createHmac("sha256", process.env.WEATHERLINK_V2_API_SECRET);
    hmac.update(stringToHash);
    let apiSignature = hmac.digest("hex").toLowerCase();
    console.log(apiSignature);

    sth = stringToHash;

    baseParameters["api-signature"] = apiSignature;

    return baseParameters;
}

let sth = "";

exports.handler = async function(event, context) {
    const data = {
        current: {
            timestamp: null, // number
            pm25: null, // number
            pm25_aqi_value: null, // number
            pm25_aqi_desc: null // string
        },
        historic: {
            data: []    // { timestamp: number, pm25_aqi_value: number }
        }
    };

    data.sth = sth;

    const sensorId = +process.env.SENSOR_ID;

    const currentParameters = getParameters(getCurrentBaseParameters());
    data.woot = currentParameters;

    let url = process.env.WEATHERLINK_V2_API_BASE_URL + "/current/" + currentParameters["station-id"] +
    "?api-key=" + currentParameters["api-key"] +
    "&api-signature=" + currentParameters["api-signature"] +
    "&t=" + currentParameters["t"];
    data.url = url;

    const currentResponse = await fetch(url);
    const currentJson = await currentResponse.json();

    data.extra = currentJson;

    return {
        statusCode: 200,
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify(data)
    };
}
