const _ = require("lodash");

exports.handler = async function(event, context) {
    let data = {
      hello: "world"
    };
    data = _.orderBy(data, ['epoch_of_change', 'zone'], ['asc', 'asc']);
    return {
        statusCode: 200,
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify(data)
    };
}
