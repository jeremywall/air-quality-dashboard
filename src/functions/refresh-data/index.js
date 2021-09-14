const _ = require("lodash");
const crypto = require("crypto");

import fetch from "node-fetch";

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

    const response = await fetch("http://httpbin.org/get");
    const json = await response.json();
    data.extra = json;

    return {
        statusCode: 200,
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify(data)
    };
}
