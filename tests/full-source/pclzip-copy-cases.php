<?php

// Exercise the original uncompressed copy utility from the complete ZIP library.
function pclzip_copy_cases(): array {
    global $pclzip_definition_trace;
    $rows = array(array('constant-state', $pclzip_definition_trace));
    foreach (array('', "a\0b\nc", 'éè汉字', str_repeat("block\0", 700)) as $content) {
        foreach (array(0, 1, 2, '2', '2e0', 1.5, true, false, null, 2048, 2050, 5000) as $size) {
            foreach (array(0, 2) as $start) {
                if ($start > strlen($content)) continue;
                $source = fopen('php://memory', 'w+');
                $target = fopen('php://memory', 'w+');
                fwrite($source, $content);
                fseek($source, $start);
                fwrite($target, 'prefix:');
                try {
                    $result = array('ok', PclZipUtilCopyBlock($source, $target, $size, 0));
                } catch (TypeError $error) {
                    $result = array('error', 'TypeError');
                } catch (ValueError $error) {
                    $result = array('error', 'ValueError');
                }
                $trace = array($result, ftell($source), feof($source), ftell($target));
                rewind($target);
                $bytes = '';
                while (!feof($target)) {
                    $chunk = fread($target, 8192);
                    if ($chunk === false) throw new RuntimeException('Cannot read the copy control output.');
                    $bytes .= $chunk;
                }
                $rows[] = array($trace, base64_encode($bytes), fclose($source), fclose($target));
            }
        }
    }
    return $rows;
}
