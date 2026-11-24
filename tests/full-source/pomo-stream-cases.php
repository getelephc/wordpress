<?php

function pomo_stream_reader(string $kind, string $content): mixed {
    if ($kind === 'string') return new POMO_StringReader($content);
    if ($kind === 'cached') return new POMO_CachedFileReader(POMO_TEST_FILE);
    if ($kind === 'cached-int') return new POMO_CachedIntFileReader(POMO_TEST_FILE);
    return new POMO_FileReader(POMO_TEST_FILE);
}

function pomo_stream_cases(): array {
    $rows = array();
    foreach (array('', "a\0b\nc", 'abcdef', 'éè汉字') as $content) {
        file_put_contents(POMO_TEST_FILE, $content);
        foreach (array('string', 'file', 'cached', 'cached-int') as $kind) {
            $reader = pomo_stream_reader($kind, $content);
            $rows[] = array($kind, 'open', $reader->is_resource(), $reader->pos());
            foreach (array(1, 2, 8, 3) as $length) {
                $data = $reader->read($length);
                $rows[] = array($kind, 'read', $data === false ? false : base64_encode($data), $reader->pos());
            }
            foreach (array(0, 2, 8, '2') as $offset) {
                $result = $reader->seekto($offset);
                $data = $reader->read(2);
                $rows[] = array($kind, 'seek', $result, $data === false ? false : base64_encode($data), $reader->pos());
            }
            $data = $reader->read_all();
            $rows[] = array($kind, 'all', $data === false ? false : base64_encode($data), $reader->pos(), $reader->close());
        }
    }
    foreach (array('little', 'big') as $endian) {
        $content = hex2bin($endian === 'little' ? '0100000002000000ffffffff' : '0000000100000002ffffffff');
        file_put_contents(POMO_TEST_FILE, $content);
        foreach (array('string', 'file', 'cached', 'cached-int') as $kind) {
            $reader = pomo_stream_reader($kind, $content);
            $reader->setEndian($endian);
            $rows[] = array($kind, $endian, $reader->readint32(), $reader->readint32array(2),
                $reader->readint32(), $reader->pos(), $reader->close());
        }
    }
    foreach (array(1, '2', 1.5, null, false, 0, -1, 'bad', array()) as $length) {
        file_put_contents(POMO_TEST_FILE, 'abcdef');
        $reader = new POMO_FileReader(POMO_TEST_FILE);
        try {
            $data = $reader->read($length);
            $result = array('ok', $data === false ? false : base64_encode($data));
        } catch (TypeError $error) {
            $result = array('error', 'TypeError');
        } catch (ValueError $error) {
            $result = array('error', 'ValueError');
        }
        $rows[] = array('file', 'weak-read', $result, $reader->pos(), $reader->feof(), $reader->close());
    }
    return $rows;
}
