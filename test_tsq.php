<?php

$service = new App\Services\Rfc3161TimestampService;
$ref = new ReflectionMethod($service, 'montarTsq');
$ref->setAccessible(true);

$hashInput = hash('sha256', 'teste payproxy rfc3161 ' . now()->toIso8601String());
$tsq = $ref->invoke($service, $hashInput);

echo "TSQ length: " . strlen($tsq) . " bytes (esperado: 69)\n";
echo "Byte[0]: 0x" . bin2hex($tsq[0]) . " (esperado: 30 = SEQUENCE)\n";
echo "Byte[1]: 0x" . bin2hex($tsq[1]) . " (esperado: 43 = length 67)\n";
echo "Byte[2]: 0x" . bin2hex($tsq[2]) . " (esperado: 02 = INTEGER)\n";
echo "Byte[3]: 0x" . bin2hex($tsq[3]) . " (esperado: 01 = length 1)\n";
echo "Byte[4]: 0x" . bin2hex($tsq[4]) . " (esperado: 01 = version 1)\n";
echo "Byte[5]: 0x" . bin2hex($tsq[5]) . " (esperado: 30 = messageImprint SEQUENCE)\n";
echo "Byte[6]: 0x" . bin2hex($tsq[6]) . " (esperado: 31 = length 49)\n";
echo "Byte[7]: 0x" . bin2hex($tsq[7]) . " (esperado: 30 = hashAlgorithm SEQUENCE)\n";
echo "Byte[8]: 0x" . bin2hex($tsq[8]) . " (esperado: 0d = length 13)\n";
echo "Byte[9]: 0x" . bin2hex($tsq[9]) . " (esperado: 06 = OID tag)\n";
echo "Byte[10]: 0x" . bin2hex($tsq[10]) . " (esperado: 09 = OID length 9)\n";
echo "OID bytes: " . bin2hex(substr($tsq, 11, 9)) . " (esperado: 608648016503040201)\n";
echo "Byte[20]: 0x" . bin2hex($tsq[20]) . " (esperado: 05 = NULL tag)\n";
echo "Byte[21]: 0x" . bin2hex($tsq[21]) . " (esperado: 00 = NULL length)\n";
echo "Byte[22]: 0x" . bin2hex($tsq[22]) . " (esperado: 04 = OCTET STRING)\n";
echo "Byte[23]: 0x" . bin2hex($tsq[23]) . " (esperado: 20 = length 32)\n";
echo "\nTSQ hex completo:\n" . chunk_split(bin2hex($tsq), 2, ' ') . "\n";

echo "\n--- Enviando para FreeTSA ---\n";
$response = Illuminate\Support\Facades\Http::timeout(20)
    ->withBody($tsq, 'application/timestamp-query')
    ->post('https://freetsa.org/tsr');

echo "HTTP Status: " . $response->status() . "\n";
echo "Content-Type: " . $response->header('Content-Type') . "\n";
echo "Response length: " . strlen($response->body()) . " bytes\n";

if ($response->successful() && strlen($response->body()) > 10) {
    $firstByte = bin2hex($response->body()[0]);
    echo "First byte TSR: 0x" . $firstByte . "\n";
    echo "\n✓ FreeTSA aceitou o TSQ — TSR recebido!\n";
    echo "TSR base64: " . base64_encode($response->body()) . "\n";
} else {
    echo "✗ Falha\n";
    echo "Body (hex): " . bin2hex(substr($response->body(), 0, 64)) . "\n";
    echo "Body (raw): " . substr($response->body(), 0, 200) . "\n";
}
