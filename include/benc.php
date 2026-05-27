<?php

/**
 * Bencode encoder/decoder.
 * [
 *   'type'   => 'string|integer|list|dictionary',
 *   'value'  => mixed,
 *   'strlen' => int,
 *   'string' => string
 * ]
 */

function benc($obj) {
    if (!is_array($obj) || !isset($obj['type'], $obj['value'])) {
        return null;
    }

    switch ($obj['type']) {
        case 'string':
            return benc_str((string)$obj['value']);

        case 'integer':
            return benc_int($obj['value']);

        case 'list':
            return is_array($obj['value']) ? benc_list($obj['value']) : null;

        case 'dictionary':
            return is_array($obj['value']) ? benc_dict($obj['value']) : null;

        default:
            return null;
    }
}

function benc_str($s) {
    $s = (string)$s;
    return strlen($s) . ':' . $s;
}

function benc_int($i) {
    return 'i' . (string)$i . 'e';
}

function benc_list($a) {
    if (!is_array($a)) {
        return null;
    }

    $out = ['l'];

    foreach ($a as $e) {
        $encoded = benc($e);

        if ($encoded === null) {
            return null;
        }

        $out[] = $encoded;
    }

    $out[] = 'e';

    return implode('', $out);
}

function benc_dict($d) {
    if (!is_array($d)) {
        return null;
    }

    $keys = array_keys($d);
    sort($keys, SORT_STRING);

    $out = ['d'];

    foreach ($keys as $k) {
        $encoded = benc($d[$k]);

        if ($encoded === null) {
            return null;
        }

        $out[] = benc_str((string)$k);
        $out[] = $encoded;
    }

    $out[] = 'e';

    return implode('', $out);
}

function bdec_file($f, $ms) {
    if (!is_string($f) || $f === '' || !is_file($f) || !is_readable($f)) {
        return null;
    }

    $ms = (int)$ms;

    if ($ms <= 0) {
        return null;
    }

    $fp = @fopen($f, 'rb');

    if (!$fp) {
        return null;
    }

    $data = fread($fp, $ms);
    fclose($fp);

    if ($data === false || $data === '') {
        return null;
    }

    return bdec($data);
}

function bdec($s) {
    if (!is_string($s) || $s === '') {
        return null;
    }

    $pos = 0;
    $len = strlen($s);

    return bdec_parse($s, $pos, $len);
}

function bdec_list($s) {
    if (!is_string($s) || $s === '' || $s[0] !== 'l') {
        return null;
    }

    $pos = 0;
    $len = strlen($s);

    return bdec_parse_list($s, $pos, $len);
}

function bdec_dict($s) {
    if (!is_string($s) || $s === '' || $s[0] !== 'd') {
        return null;
    }

    $pos = 0;
    $len = strlen($s);

    return bdec_parse_dict($s, $pos, $len);
}

/**
 * Внутренний быстрый парсер без substr() на каждом шаге.
 */
function bdec_parse($s, &$pos, $len) {
    if ($pos >= $len) {
        return null;
    }

    $ch = $s[$pos];

    if ($ch >= '0' && $ch <= '9') {
        return bdec_parse_string($s, $pos, $len);
    }

    if ($ch === 'i') {
        return bdec_parse_integer($s, $pos, $len);
    }

    if ($ch === 'l') {
        return bdec_parse_list($s, $pos, $len);
    }

    if ($ch === 'd') {
        return bdec_parse_dict($s, $pos, $len);
    }

    return null;
}

function bdec_parse_string($s, &$pos, $len) {
    $start = $pos;
    $num = 0;

    while ($pos < $len) {
        $ch = $s[$pos];

        if ($ch === ':') {
            break;
        }

        if ($ch < '0' || $ch > '9') {
            return null;
        }

        $num = ($num * 10) + (ord($ch) - 48);
        $pos++;
    }

    if ($pos >= $len || $s[$pos] !== ':') {
        return null;
    }

    $lengthText = substr($s, $start, $pos - $start);

    if ($lengthText === '') {
        return null;
    }

    /*
     * Защита от некорректных длин:
     * 01:abc в bencode считается неправильной длиной.
     */
    if (strlen($lengthText) > 1 && $lengthText[0] === '0') {
        return null;
    }

    $pos++; // пропускаем ":"

    if ($num < 0 || $pos + $num > $len) {
        return null;
    }

    $value = substr($s, $pos, $num);
    $pos += $num;

    $rawLen = $pos - $start;
    $raw = substr($s, $start, $rawLen);

    return [
        'type'   => 'string',
        'value'  => $value,
        'strlen' => $rawLen,
        'string' => $raw,
    ];
}

function bdec_parse_integer($s, &$pos, $len) {
    $start = $pos;

    if ($s[$pos] !== 'i') {
        return null;
    }

    $pos++;

    if ($pos >= $len) {
        return null;
    }

    $numStart = $pos;

    if ($s[$pos] === '-') {
        $pos++;

        if ($pos >= $len) {
            return null;
        }
    }

    while ($pos < $len && $s[$pos] >= '0' && $s[$pos] <= '9') {
        $pos++;
    }

    if ($pos >= $len || $s[$pos] !== 'e') {
        return null;
    }

    $value = substr($s, $numStart, $pos - $numStart);

    if ($value === '' || $value === '-') {
        return null;
    }

    if ($value === '-0') {
        return null;
    }

    if ($value[0] === '0' && strlen($value) !== 1) {
        return null;
    }

    if (isset($value[1]) && $value[0] === '-' && $value[1] === '0') {
        return null;
    }

    $pos++; // пропускаем "e"

    $rawLen = $pos - $start;
    $raw = substr($s, $start, $rawLen);

    return [
        'type'   => 'integer',
        'value'  => $value,
        'strlen' => $rawLen,
        'string' => $raw,
    ];
}

function bdec_parse_list($s, &$pos, $len) {
    $start = $pos;

    if ($pos >= $len || $s[$pos] !== 'l') {
        return null;
    }

    $pos++;
    $value = [];

    while ($pos < $len) {
        if ($s[$pos] === 'e') {
            $pos++;

            $rawLen = $pos - $start;
            $raw = substr($s, $start, $rawLen);

            return [
                'type'   => 'list',
                'value'  => $value,
                'strlen' => $rawLen,
                'string' => $raw,
            ];
        }

        $item = bdec_parse($s, $pos, $len);

        if (!is_array($item)) {
            return null;
        }

        $value[] = $item;
    }

    return null;
}

function bdec_parse_dict($s, &$pos, $len) {
    $start = $pos;

    if ($pos >= $len || $s[$pos] !== 'd') {
        return null;
    }

    $pos++;
    $value = [];

    while ($pos < $len) {
        if ($s[$pos] === 'e') {
            $pos++;

            $rawLen = $pos - $start;
            $raw = substr($s, $start, $rawLen);

            return [
                'type'   => 'dictionary',
                'value'  => $value,
                'strlen' => $rawLen,
                'string' => $raw,
            ];
        }

        $key = bdec_parse_string($s, $pos, $len);

        if (!is_array($key) || $key['type'] !== 'string') {
            return null;
        }

        $item = bdec_parse($s, $pos, $len);

        if (!is_array($item)) {
            return null;
        }

        $value[$key['value']] = $item;
    }

    return null;
}