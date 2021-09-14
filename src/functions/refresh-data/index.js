const _ = require("lodash");
const crypto = require("crypto");

exports.handler = async function(event, context) {
    const data = {
        current: {
            
        },
        historic: {

        }
    };

    return {
        statusCode: 200,
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify(data)
    };
}
