const _ = require("lodash");

exports.handler = async function(event, context) {
    let data = {
      siteTitle: process.env.SITE_TITLE,
      timeZone: process.env.TIMEZONE
    };
    return {
        statusCode: 200,
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify(data)
    };
}
