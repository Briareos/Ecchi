var dgram = require('dgram');

var s = dgram.createSocket('udp4');
s.on('message', function (msg, rinfo) {
    console.log(msg.toString());
});
s.bind(1337);

