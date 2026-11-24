<?php

// The control loads the complete original/patched AVIF file, including its parser.
function avif_io_cases(): array {
    $rows = array();
    foreach (array('', "a\0b\nc", 'abcdef', 'éè汉字') as $content) {
        foreach (array(1, 2, 4, 64, '2', '2e0', 1.5, true, 0, -1, null, false, 'bad', array()) as $length) {
            $stream = fopen('php://memory', 'w+');
            fwrite($stream, $content);
            rewind($stream);
            try {
                $value = \Avifinfo\read($stream, $length);
                $result = array('ok', $value === false ? false : base64_encode($value));
            } catch (TypeError $error) {
                $result = array('error', 'TypeError');
            } catch (ValueError $error) {
                $result = array('error', 'ValueError');
            }
            $rows[] = array('read', $result, ftell($stream), feof($stream));
            fclose($stream);
        }
    }
    foreach (array(0, 1, 2, -1, '2', 1.5, null, true, 'bad', array()) as $offset) {
        $stream = fopen('php://memory', 'w+');
        fwrite($stream, 'abcdef');
        fseek($stream, 2);
        try {
            $result = array('ok', \Avifinfo\skip($stream, $offset));
        } catch (TypeError $error) {
            $result = array('error', 'TypeError');
        }
        $rows[] = array('skip', $result, ftell($stream), feof($stream));
        fclose($stream);
    }
    foreach (array('00', 'ff', '1234', 'ffff', '123456', 'ffffff', '12345678', 'ffffffff') as $hex) {
        $data = hex2bin($hex);
        $rows[] = array('integer', \Avifinfo\read_big_endian($data, strlen($data)));
    }
    $headers = array(
        array('', 64, 0),
        array('0000000866747970', 64, 0),
        array('0000000466747970', 64, 0),
        array('0000000066726565', 32, 0),
        array('0000001066726565', 8, 0),
        array('0000000866747970', 7, 0),
        array('0000000866747970', 64, \Avifinfo\MAX_NUM_BOXES - 1),
        array('00000001667265650000000000000010', 32, 0),
        array('00000001667265650000000100000000', 32, 0),
        array('0000000c7069746d00000000', 64, 0),
        array('0000000c7069746d02000001', 64, 0),
        array('0000000c7069746d0000', 64, 0),
    );
    foreach ($headers as $case) {
        $stream = fopen('php://memory', 'w+');
        fwrite($stream, hex2bin($case[0]));
        rewind($stream);
        $box = new \Avifinfo\Box();
        $count = $case[2];
        $status = $box->parse($stream, $count, $case[1]);
        $fields = $status === \Avifinfo\FOUND
            ? array($box->size, $box->type, $box->version, $box->flags, $box->content_size)
            : null;
        $rows[] = array('box', $status, $count, ftell($stream), $fields);
        fclose($stream);
    }
    foreach (array('', '00000010667479706176696600000000',
        '00000014667479706d6966310000000061766973', '00000010667479706d69663100000000',
        '0000001066747970617669') as $hex) {
        $stream = fopen('php://memory', 'w+');
        fwrite($stream, hex2bin($hex));
        rewind($stream);
        $parser = new \Avifinfo\Parser($stream);
        $rows[] = array('brand', $parser->parse_ftyp(), ftell($stream));
        fclose($stream);
    }
    return $rows;
}
