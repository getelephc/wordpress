<?php

// Close the standalone control's PSR-18 reflection world without sending requests.
class AotAiControlClient implements \WordPress\AiClientDependencies\Psr\Http\Client\ClientInterface {
    public function sendRequest(\WordPress\AiClientDependencies\Psr\Http\Message\RequestInterface $request): \WordPress\AiClientDependencies\Psr\Http\Message\ResponseInterface {
        throw new LogicException('HTTP transport is outside this control.');
    }
    public function send(\WordPress\AiClientDependencies\Psr\Http\Message\RequestInterface $request, array $options = array()): \WordPress\AiClientDependencies\Psr\Http\Message\ResponseInterface {
        throw new LogicException('HTTP transport is outside this control.');
    }
}

class AotAiOtherClient extends AotAiControlClient {}
class AotAiControlDiscoveryStrategy extends \WordPress\AiClient\Providers\Http\Abstracts\AbstractClientDiscoveryStrategy {
    protected static function createClient(\WordPress\AiClientDependencies\Nyholm\Psr7\Factory\Psr17Factory $factory): \WordPress\AiClientDependencies\Psr\Http\Client\ClientInterface {
        return new AotAiControlClient();
    }
}
class AotAiOtherDiscoveryStrategy extends AotAiControlDiscoveryStrategy {
    protected static function createClient(\WordPress\AiClientDependencies\Nyholm\Psr7\Factory\Psr17Factory $factory): \WordPress\AiClientDependencies\Psr\Http\Client\ClientInterface {
        return new AotAiOtherClient();
    }
}

function aot_ai_case_rows(array $cases): array {
    $rows = array();
    foreach ($cases as $case) {
        $enum = wp_aot_require_WordPress_AiClient_Common_AbstractEnum($case);
        $rows[] = array($enum->name, $enum->value, $enum->jsonSerialize(), (string) $enum);
    }
    return $rows;
}

$rows = array();
$rows[] = aot_ai_case_rows(\WordPress\AiClient\Files\Enums\FileTypeEnum::cases());
$rows[] = aot_ai_case_rows(\WordPress\AiClient\Files\Enums\MediaOrientationEnum::cases());
$rows[] = aot_ai_case_rows(\WordPress\AiClient\Messages\Enums\MessagePartChannelEnum::cases());
$rows[] = aot_ai_case_rows(\WordPress\AiClient\Messages\Enums\MessagePartTypeEnum::cases());
$rows[] = aot_ai_case_rows(\WordPress\AiClient\Messages\Enums\MessageRoleEnum::cases());
$rows[] = aot_ai_case_rows(\WordPress\AiClient\Messages\Enums\ModalityEnum::cases());
$rows[] = aot_ai_case_rows(\WordPress\AiClient\Operations\Enums\OperationStateEnum::cases());
$rows[] = aot_ai_case_rows(\WordPress\AiClient\Providers\Enums\ProviderTypeEnum::cases());
$rows[] = aot_ai_case_rows(\WordPress\AiClient\Providers\Enums\ToolTypeEnum::cases());
$rows[] = aot_ai_case_rows(\WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum::cases());
$rows[] = aot_ai_case_rows(\WordPress\AiClient\Providers\Http\Enums\RequestAuthenticationMethod::cases());
$rows[] = aot_ai_case_rows(\WordPress\AiClient\Providers\Models\Enums\CapabilityEnum::cases());
$rows[] = aot_ai_case_rows(\WordPress\AiClient\Providers\Models\Enums\OptionEnum::cases());
$rows[] = aot_ai_case_rows(\WordPress\AiClient\Results\Enums\FinishReasonEnum::cases());
$rows[] = \WordPress\AiClient\Messages\Enums\ModalityEnum::from('text') === \WordPress\AiClient\Messages\Enums\ModalityEnum::text();
$rows[] = \WordPress\AiClient\Messages\Enums\ModalityEnum::tryFrom('missing');
$config = new \WordPress\AiClient\Providers\Models\DTO\ModelConfig();
$rows[] = $config->toArray();
$config->setMaxTokens(256);
$config->setTemperature(0.5);
$config->setSystemInstruction('hello');
$config->setCustomOptions(array('seed' => 7));
$rows[] = $config->toArray();
$rows[] = \WordPress\AiClient\Providers\Models\DTO\ModelConfig::fromArray($config->toArray())->toArray();
$copy = clone $config;
$copy->setMaxTokens(512);
$rows[] = array($config->getMaxTokens(), $copy->getMaxTokens());
foreach (array('txt', 'jpg', 'png', 'PDF', '.png', 'unknown') as $extension) {
    try {
    $mime = \WordPress\AiClient\Files\ValueObjects\MimeType::fromExtension($extension);
    $rows[] = array((string) $mime, $mime->toExtension(), $mime->equals(strtoupper((string) $mime)));
    } catch (\WordPress\AiClient\Common\Exception\InvalidArgumentException $error) {
        $rows[] = array('error', $error->getMessage());
    }
}
$uri = new \WordPress\AiClientDependencies\Nyholm\Psr7\Uri('https://User:pass@Example.org:443/a%20b?q=hello#f');
$rows[] = array((string) $uri, $uri->getScheme(), $uri->getAuthority(), $uri->getPath(), $uri->getQuery(), $uri->getPort());
$changed = $uri->withPort(8080)->withPath('/next space')->withQuery('x=a b')->withFragment('new fragment');
$rows[] = array((string) $uri, (string) $changed);
foreach (array('http', 'https', 'custom') as $port_scheme) {
    foreach (array(null, 0, 1, 80, 443, 8080, 65535, '80', '443', '8080') as $port_input) {
        $port_base = (new \WordPress\AiClientDependencies\Nyholm\Psr7\Uri())->withScheme($port_scheme)->withHost('example.org');
        $port_uri = $port_base->withPort($port_input);
        $port_expected = $port_input === null ? null : (int) $port_input;
        if (($port_scheme === 'http' && $port_expected === 80) || ($port_scheme === 'https' && $port_expected === 443)) {
            $port_expected = null;
        }
        $port_authority = 'example.org' . ($port_expected === null ? '' : ':' . $port_expected);
        $port_next_scheme = $port_scheme === 'https' ? 'http' : 'https';
        $port_next_expected = $port_expected;
        if (($port_next_scheme === 'http' && $port_next_expected === 80) || ($port_next_scheme === 'https' && $port_next_expected === 443)) {
            $port_next_expected = null;
        }
        $port_roundtrip = new \WordPress\AiClientDependencies\Nyholm\Psr7\Uri((string) $port_uri);
        $port_row = array($port_uri->getPort(), $port_uri->getAuthority(), $port_base->getPort(),
            $port_uri->withPort($port_input) === $port_uri, $port_uri === $port_base,
            $port_roundtrip->getPort(), $port_uri->withScheme($port_next_scheme)->getPort());
        if ($port_row !== array($port_expected, $port_authority, null, true, $port_expected === null, $port_expected, $port_next_expected)) {
            throw new LogicException('PSR URI nullable port normalization differs.');
        }
        $rows[] = $port_row;
    }
    foreach (array(-1, 65536) as $port_invalid) {
        $port_base = (new \WordPress\AiClientDependencies\Nyholm\Psr7\Uri())->withScheme($port_scheme)->withHost('example.org');
        try {
            $port_base->withPort($port_invalid);
            throw new LogicException('Invalid URI port was accepted.');
        } catch (InvalidArgumentException $port_error) {
            $port_message = 'Invalid port: ' . $port_invalid . '. Must be between 0 and 65535';
            $port_row = array(get_class($port_error), $port_error->getMessage(), $port_base->getPort(), $port_base->getAuthority());
            if ($port_row !== array('InvalidArgumentException', $port_message, null, 'example.org')) {
                throw new LogicException('PSR URI invalid port changed state.');
            }
            $rows[] = $port_row;
        }
    }
}
$response = new \WordPress\AiClientDependencies\Nyholm\Psr7\Response(201, array('X-Test' => array('a', 'b')));
$changedResponse = $response->withAddedHeader('x-test', 'c')->withHeader('Content-Type', 'text/plain');
$rows[] = array($response->getHeaders(), $changedResponse->getHeaders(), $response->getStatusCode(), $response->getReasonPhrase());
$request = new \WordPress\AiClient\Providers\Http\DTO\Request(wp_aot_require_WordPress_AiClient_Providers_Http_Enums_HttpMethodEnum(\WordPress\AiClient\Providers\Http\Enums\HttpMethodEnum::GET()), 'https://example.org/api', array('X-Test' => 'ok'), array('q' => 'a b', 'ids' => array(2, 3), 'empty' => null));
$rows[] = array($request->getUri(), $request->getBody(), $request->getHeader('x-test'));
$rows[] = \WordPress\AiClient\Providers\Http\DTO\Request::fromArray($request->toArray())->toArray();
foreach (array('', 'small body', str_repeat('large body ', 20000)) as $body) {
    $stream = \WordPress\AiClientDependencies\Nyholm\Psr7\Stream::create($body);
    $before = array($stream->getSize(), $stream->tell(), $stream->isReadable(), $stream->isWritable(), $stream->isSeekable());
    $written = $stream->write('XYZ');
    $stream->rewind();
    $first = $stream->read(3);
    $rest = $stream->getContents();
    $rows[] = array($before, $written, $first, strlen($rest), hash('sha256', $rest), $stream->tell(), $stream->getSize(), $stream->eof());
    $stream->close();
}
$stream_cache_bodies = array('', 'x', 'abc', "binary\0body", 'Unicode π', str_repeat('large body ', 20000));
foreach ($stream_cache_bodies as $stream_cache_body) {
    $stream_cache_resource = fopen('php://memory', 'r+');
    fwrite($stream_cache_resource, (string) $stream_cache_body);
    rewind($stream_cache_resource);
    $stream_cache = new \WordPress\AiClientDependencies\Nyholm\Psr7\Stream($stream_cache_resource);
    $stream_cache_initial = $stream_cache->getSize();
    $stream_cache_repeat = $stream_cache->getSize();
    fseek($stream_cache_resource, 0, SEEK_END);
    fwrite($stream_cache_resource, 'external');
    $stream_cache_stale = $stream_cache->getSize();
    $stream_cache_written = $stream_cache->write('!');
    $stream_cache_updated = $stream_cache->getSize();
    $stream_cache_detached = $stream_cache->detach();
    $stream_cache_same = $stream_cache_detached === $stream_cache_resource;
    $stream_cache_missing = $stream_cache->getSize();
    $stream_cache->close();
    $stream_cache_after_close = $stream_cache->getSize();
    $stream_cache_attached = new \WordPress\AiClientDependencies\Nyholm\Psr7\Stream($stream_cache_detached);
    $stream_cache_reattached = $stream_cache_attached->getSize();
    $stream_cache_attached->rewind();
    $stream_cache_hash = hash('sha256', $stream_cache_attached->getContents());
    $stream_cache_attached->close();
    $stream_cache_closed = $stream_cache_attached->getSize();
    $stream_cache_row = array($stream_cache_initial, $stream_cache_repeat, $stream_cache_stale,
        $stream_cache_written, $stream_cache_updated, $stream_cache_same, $stream_cache_missing,
        $stream_cache_after_close, $stream_cache_reattached, $stream_cache_hash, $stream_cache_closed);
    $stream_cache_length = strlen((string) $stream_cache_body);
    $stream_cache_expected = array($stream_cache_length, $stream_cache_length, $stream_cache_length,
        1, $stream_cache_length + 9, true, null, null, $stream_cache_length + 9,
        hash('sha256', (string) $stream_cache_body . 'external!'), null);
    if ($stream_cache_row !== $stream_cache_expected) {
        throw new LogicException('PSR stream size-cache lifecycle differs.');
    }
    $rows[] = $stream_cache_row;
}
$discoveries = array(
    AotAiControlDiscoveryStrategy::getCandidates(\WordPress\AiClientDependencies\Psr\Http\Client\ClientInterface::class),
    AotAiOtherDiscoveryStrategy::getCandidates(\WordPress\AiClientDependencies\Psr\Http\Client\ClientInterface::class),
);
foreach ($discoveries as $discovery) {
    $createClient = $discovery[0]['class'];
    $rows[] = get_class($createClient());
}
foreach (array(null,0,12,PHP_INT_MAX) as $limit) {
    if ($limit === null) {
        $limit_error = new \WordPress\AiClient\Common\Exception\TokenLimitReachedException('budget');
    } else {
        $limit_error = new \WordPress\AiClient\Common\Exception\TokenLimitReachedException('budget', (int) $limit);
    }
    $rows[] = array($limit_error->getMessage(), $limit_error->getMaxTokens());
}
echo json_encode($rows, JSON_THROW_ON_ERROR) . "\n";
