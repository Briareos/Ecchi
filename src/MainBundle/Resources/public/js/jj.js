var _0xdd02 = ["host", "javjunkies.com", "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=", "", "substr", "charAt", "indexOf", "fromCharCode", "length", "join", "charCodeAt"];

function jj(_0x95d8x2) {
    var _0x95d8x3 = _0xdd02[2];
    var _0x95d8x4, _0x95d8x5, _0x95d8x6, _0x95d8x7, _0x95d8x8, _0x95d8x9, _0x95d8xa, _0x95d8xb, _0x95d8xc = 0,
        _0x95d8xd = 0,
        _0x95d8xe = _0xdd02[3],
        _0x95d8xf = [];
    if (!_0x95d8x2) {
        return _0x95d8x2
    };
    _0x95d8x2 = _0x95d8x2[_0xdd02[4]](3);
    _0x95d8x2 += _0xdd02[3];
    do {
        _0x95d8x7 = _0x95d8x3[_0xdd02[6]](_0x95d8x2[_0xdd02[5]](_0x95d8xc++));
        _0x95d8x8 = _0x95d8x3[_0xdd02[6]](_0x95d8x2[_0xdd02[5]](_0x95d8xc++));
        _0x95d8x9 = _0x95d8x3[_0xdd02[6]](_0x95d8x2[_0xdd02[5]](_0x95d8xc++));
        _0x95d8xa = _0x95d8x3[_0xdd02[6]](_0x95d8x2[_0xdd02[5]](_0x95d8xc++));
        _0x95d8xb = _0x95d8x7 << 18 | _0x95d8x8 << 12 | _0x95d8x9 << 6 | _0x95d8xa;
        _0x95d8x4 = _0x95d8xb >> 16 & 0xff;
        _0x95d8x5 = _0x95d8xb >> 8 & 0xff;
        _0x95d8x6 = _0x95d8xb & 0xff;
        if (_0x95d8x9 == 64) {
            _0x95d8xf[_0x95d8xd++] = String[_0xdd02[7]](_0x95d8x4)
        } else {
            if (_0x95d8xa == 64) {
                _0x95d8xf[_0x95d8xd++] = String[_0xdd02[7]](_0x95d8x4, _0x95d8x5)
            } else {
                _0x95d8xf[_0x95d8xd++] = String[_0xdd02[7]](_0x95d8x4, _0x95d8x5, _0x95d8x6)
            }
        }
    } while (_0x95d8xc < _0x95d8x2[_0xdd02[8]]);
    _0x95d8xe = _0x95d8xf[_0xdd02[9]](_0xdd02[3]);
    return utf8d(_0x95d8xe)
};

function utf8d(_0x95d8x11) {
    var _0x95d8x12 = _0xdd02[3],
        _0x95d8xc = 0,
        _0x95d8x13 = c1 = c2 = 0;
    while (_0x95d8xc < _0x95d8x11[_0xdd02[8]]) {
        _0x95d8x13 = _0x95d8x11[_0xdd02[10]](_0x95d8xc);
        if (_0x95d8x13 < 128) {
            _0x95d8x12 += String[_0xdd02[7]](_0x95d8x13);
            _0x95d8xc++
        } else {
            if ((_0x95d8x13 > 191) && (_0x95d8x13 < 224)) {
                c2 = _0x95d8x11[_0xdd02[10]](_0x95d8xc + 1);
                _0x95d8x12 += String[_0xdd02[7]](((_0x95d8x13 & 31) << 6) | (c2 & 63));
                _0x95d8xc += 2
            } else {
                c2 = _0x95d8x11[_0xdd02[10]](_0x95d8xc + 1);
                c3 = _0x95d8x11[_0xdd02[10]](_0x95d8xc + 2);
                _0x95d8x12 += String[_0xdd02[7]](((_0x95d8x13 & 15) << 12) | ((c2 & 63) << 6) | (c3 & 63));
                _0x95d8xc += 3
            }
        }
    };
    return _0x95d8x12
};
