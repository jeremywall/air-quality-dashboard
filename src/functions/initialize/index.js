const _ = require("lodash");

exports.handler = async function(event, context) {
    let data = {
      title: process.env.SITE_TITLE,
      timezone: process.env.TIMEZONE
    };
    return {
        statusCode: 200,
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify(data)
    };
}
