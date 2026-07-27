<?php

namespace KiisKepri\Esign\Tests\V2;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use KiisKepri\Esign\DTO\SignatureProperties;
use KiisKepri\Esign\Exception\InvalidArgumentException;
use KiisKepri\Esign\V2\Esign;
use PHPUnit\Framework\TestCase;

class EsignTest extends TestCase
{
    /** @var string */
    private $samplePdf;

    protected function setUp(): void
    {
        $this->samplePdf = sys_get_temp_dir() . '/esign-v2-' . uniqid('', true) . '.pdf';
        file_put_contents($this->samplePdf, '%PDF-1.4 test');
    }

    protected function tearDown(): void
    {
        if (is_file($this->samplePdf)) {
            unlink($this->samplePdf);
        }
    }

    private function client(MockHandler $mock, array &$history = []): Esign
    {
        $stack = HandlerStack::create($mock);
        $stack->push(Middleware::history($history));

        $http = new Client([
            'handler' => $stack,
            'http_errors' => false,
        ]);

        return (new Esign('https://esign.test', 'user', 'pass', [], $http))
            ->setNIK('3200123456789012')
            ->setEmail('user@example.go.id');
    }

    private function bareClient(MockHandler $mock, array &$history = []): Esign
    {
        $stack = HandlerStack::create($mock);
        $stack->push(Middleware::history($history));

        $http = new Client([
            'handler' => $stack,
            'http_errors' => false,
        ]);

        return new Esign('https://esign.test', 'user', 'pass', [], $http);
    }

    public function testSignInvisibleSendsJsonPayloadWithoutDebugNoise(): void
    {
        $history = [];
        $esign = $this->client(new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'time' => '2024-01-01T00:00:00Z',
                'file' => [base64_encode('%PDF-1.4 signed')],
            ])),
        ]), $history);

        $response = $esign->signInvisible('secret', $this->samplePdf);

        $this->assertTrue($response->isSuccess());
        $this->assertSame('2024-01-01T00:00:00Z', $response->getTimestamp());
        $this->assertSame('%PDF-1.4 signed', $response->getDecodedFile());

        $payload = json_decode((string) $history[0]['request']->getBody(), true);
        $this->assertSame('3200123456789012', $payload['nik']);
        $this->assertSame('user@example.go.id', $payload['email']);
        $this->assertSame('secret', $payload['passphrase']);
        $this->assertSame('INVISIBLE', $payload['signatureProperties'][0]['tampilan']);
        $this->assertCount(1, $payload['file']);
        $this->assertStringContainsString('/api/v2/sign/pdf', (string) $history[0]['request']->getUri());
    }

    public function testSignVisibleIncludesImageBase64AndCoordinates(): void
    {
        $image = sys_get_temp_dir() . '/esign-v2-ttd-' . uniqid('', true) . '.png';
        file_put_contents($image, 'PNGDATA');

        $history = [];
        $esign = $this->client(new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'file' => [base64_encode('%PDF')],
            ])),
        ]), $history);

        $props = SignatureProperties::visible($image, 2, 11, 22, 100, 50)
            ->withReason('Approve');

        $response = $esign->signVisible('secret', $this->samplePdf, $props);
        $this->assertTrue($response->isSuccess());

        $payload = json_decode((string) $history[0]['request']->getBody(), true);
        $sp = $payload['signatureProperties'][0];
        $this->assertSame('VISIBLE', $sp['tampilan']);
        $this->assertSame(base64_encode('PNGDATA'), $sp['imageBase64']);
        $this->assertSame(2, $sp['page']);
        $this->assertEquals(11, $sp['originX']);
        $this->assertSame('Approve', $sp['reason']);

        unlink($image);
    }

    public function testBulkSignMapsOutputs(): void
    {
        $second = sys_get_temp_dir() . '/esign-v2-b-' . uniqid('', true) . '.pdf';
        file_put_contents($second, '%PDF-1.4 b');

        $out1 = sys_get_temp_dir() . '/esign-out1-' . uniqid('', true) . '.pdf';
        $out2 = sys_get_temp_dir() . '/esign-out2-' . uniqid('', true) . '.pdf';

        $esign = $this->client(new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'file' => [
                    base64_encode('%PDF A'),
                    base64_encode('%PDF B'),
                ],
            ])),
        ]));

        $response = $esign->signInvisibleMultiple('secret', [
            $this->samplePdf => $out1,
            $second => $out2,
        ]);

        $saved = $response->saveAll();
        $this->assertSame([$out1, $out2], $saved);
        $this->assertSame('%PDF A', file_get_contents($out1));
        $this->assertSame('%PDF B', file_get_contents($out2));

        unlink($second);
        unlink($out1);
        unlink($out2);
    }

    public function testRequestSignTotp(): void
    {
        $history = [];
        $esign = $this->client(new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode(['message' => 'OTP sent'])),
        ]), $history);

        $response = $esign->requestSignTotp(null, null, 2);
        $this->assertTrue($response->isSuccess());

        $payload = json_decode((string) $history[0]['request']->getBody(), true);
        $this->assertSame(2, $payload['data']);
        $this->assertStringContainsString('/api/v2/sign/get/totp', (string) $history[0]['request']->getUri());
    }

    public function testCheckUserStatusCanSign(): void
    {
        $esign = $this->client(new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode(['status' => 'ISSUE'])),
        ]));

        $response = $esign->checkUserStatus();
        $this->assertTrue($response->isSuccess());
        $this->assertSame('ISSUE', $response->getUserStatus());
        $this->assertTrue($response->canSign());
    }

    public function testSealPdf(): void
    {
        $history = [];
        $esign = $this->client(new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'file' => [base64_encode('%PDF seal')],
            ])),
        ]), $history);

        $response = $esign->sealPdf('SUB-1', '123456', [$this->samplePdf], [
            SignatureProperties::invisible(),
        ]);

        $this->assertTrue($response->isSuccess());
        $payload = json_decode((string) $history[0]['request']->getBody(), true);
        $this->assertSame('SUB-1', $payload['idSubscriber']);
        $this->assertSame('123456', $payload['totp']);
        $this->assertStringContainsString('/api/v2/seal/pdf', (string) $history[0]['request']->getUri());
    }

    public function testVerifyCamelCase(): void
    {
        $payload = [
            'documentName' => 'a.pdf',
            'signatureCount' => 1,
            'conclusion' => 'VALID',
            'description' => 'ok',
            'signatureInformations' => [
                [
                    'signerName' => 'Ani',
                    'fieldName' => 'Sig1',
                    'integrityValid' => true,
                    'timestampInfomation' => [
                        'signerName' => 'TSA',
                        'timestampDate' => '2024-01-01',
                    ],
                ],
            ],
        ];

        $esign = $this->client(new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode($payload)),
        ]));

        $response = $esign->signVerification($this->samplePdf);
        $this->assertTrue($response->isSuccess());
        $this->assertSame('a.pdf', $response->getDocumentName());
        $this->assertSame('VALID', $response->getSummary());
        $this->assertSame(['Ani'], $response->getSigners());
        $this->assertTrue($response->getDetails()[0]->isIntegrityValid());
    }

    public function testRequiresIdentity(): void
    {
        $esign = $this->bareClient(new MockHandler([]));

        $this->expectException(InvalidArgumentException::class);
        $esign->signInvisible('x', $this->samplePdf);
    }

    public function testSignLowLevelWithArraySignatureProperties(): void
    {
        $history = [];
        $esign = $this->client(new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'file' => [base64_encode('%PDF low')],
            ])),
        ]), $history);

        $response = $esign->sign(
            [$this->samplePdf],
            [['tampilan' => 'INVISIBLE', 'reason' => 'array-props']],
            'secret',
            null,
            null,
            null
        );

        $this->assertTrue($response->isSuccess());
        $payload = json_decode((string) $history[0]['request']->getBody(), true);
        $this->assertSame('INVISIBLE', $payload['signatureProperties'][0]['tampilan']);
        $this->assertSame('array-props', $payload['signatureProperties'][0]['reason']);
        $this->assertSame('secret', $payload['passphrase']);
    }

    public function testSignWithTotpOnlyNoPassphrase(): void
    {
        $history = [];
        $esign = $this->client(new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'file' => [base64_encode('%PDF totp')],
            ])),
        ]), $history);

        $response = $esign->sign(
            [$this->samplePdf],
            [SignatureProperties::invisible()],
            null,
            '654321'
        );

        $this->assertTrue($response->isSuccess());
        $payload = json_decode((string) $history[0]['request']->getBody(), true);
        $this->assertSame('654321', $payload['totp']);
        $this->assertArrayNotHasKey('passphrase', $payload);
    }

    public function testEmailOnlyIdentityWorks(): void
    {
        $history = [];
        $esign = $this->bareClient(new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'file' => [base64_encode('%PDF email')],
            ])),
        ]), $history)->setEmail('only@example.go.id');

        $response = $esign->signInvisible('secret', $this->samplePdf);

        $this->assertTrue($response->isSuccess());
        $payload = json_decode((string) $history[0]['request']->getBody(), true);
        $this->assertSame('only@example.go.id', $payload['email']);
        $this->assertArrayNotHasKey('nik', $payload);
    }

    public function testRegisterUserPostsNamaAndEmail(): void
    {
        $history = [];
        $esign = $this->client(new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode(['message' => 'registered'])),
        ]), $history);

        $response = $esign->registerUser('Nama Lengkap', 'new@example.go.id');

        $this->assertTrue($response->isSuccess());
        $payload = json_decode((string) $history[0]['request']->getBody(), true);
        $this->assertSame('Nama Lengkap', $payload['nama']);
        $this->assertSame('new@example.go.id', $payload['email']);
        $this->assertStringContainsString('/api/v2/user/register', (string) $history[0]['request']->getUri());
    }

    public function testRequestSealActivationWithoutTotp(): void
    {
        $history = [];
        $esign = $this->client(new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode(['message' => 'ok'])),
        ]), $history);

        $response = $esign->requestSealActivation('SUB-1');

        $this->assertTrue($response->isSuccess());
        $payload = json_decode((string) $history[0]['request']->getBody(), true);
        $this->assertSame('SUB-1', $payload['idSubscriber']);
        $this->assertArrayNotHasKey('totp', $payload);
        $this->assertStringContainsString('/api/v2/seal/get/activation', (string) $history[0]['request']->getUri());
    }

    public function testRequestSealActivationWithTotp(): void
    {
        $history = [];
        $esign = $this->client(new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode(['message' => 'ok'])),
        ]), $history);

        $response = $esign->requestSealActivation('SUB-2', '111222');

        $this->assertTrue($response->isSuccess());
        $payload = json_decode((string) $history[0]['request']->getBody(), true);
        $this->assertSame('SUB-2', $payload['idSubscriber']);
        $this->assertSame('111222', $payload['totp']);
    }

    public function testRevokeSealActivation(): void
    {
        $history = [];
        $esign = $this->client(new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode(['message' => 'revoked'])),
        ]), $history);

        $response = $esign->revokeSealActivation('SUB-3', '999888');

        $this->assertTrue($response->isSuccess());
        $payload = json_decode((string) $history[0]['request']->getBody(), true);
        $this->assertSame('SUB-3', $payload['idSubscriber']);
        $this->assertSame('999888', $payload['totp']);
        $this->assertStringContainsString('/api/v2/seal/revoke/activation', (string) $history[0]['request']->getUri());
    }

    public function testRequestSealTotp(): void
    {
        $history = [];
        $esign = $this->client(new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode(['message' => 'otp'])),
        ]), $history);

        $response = $esign->requestSealTotp('SUB-4', 3, 'act-totp');

        $this->assertTrue($response->isSuccess());
        $payload = json_decode((string) $history[0]['request']->getBody(), true);
        $this->assertSame('SUB-4', $payload['idSubscriber']);
        $this->assertSame(3, $payload['data']);
        $this->assertSame('act-totp', $payload['totp']);
        $this->assertStringContainsString('/api/v2/seal/get/totp', (string) $history[0]['request']->getUri());
    }

    public function testSignVerificationWithPdfPassword(): void
    {
        $history = [];
        $esign = $this->client(new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'documentName' => 'secured.pdf',
                'signatureCount' => 0,
                'conclusion' => 'OK',
            ])),
        ]), $history);

        $response = $esign->signVerification($this->samplePdf, 'pdf-secret');

        $this->assertTrue($response->isSuccess());
        $payload = json_decode((string) $history[0]['request']->getBody(), true);
        $this->assertArrayHasKey('file', $payload);
        $this->assertSame('pdf-secret', $payload['password']);
        $this->assertStringContainsString('/api/v2/verify/pdf', (string) $history[0]['request']->getUri());
    }

    public function testEmptyFileMapOnSignInvisibleMultipleThrows(): void
    {
        $esign = $this->client(new MockHandler([]));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('fileMap must not be empty');
        $esign->signInvisibleMultiple('secret', []);
    }

    public function testSignWithoutPassphraseAndWithoutTotpThrows(): void
    {
        $esign = $this->client(new MockHandler([]));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Either passphrase or totp is required for V2 signing');
        $esign->sign([$this->samplePdf], [SignatureProperties::invisible()], null, null);
    }

    public function testRequestSignTotpWithoutIdentityThrows(): void
    {
        $esign = $this->bareClient(new MockHandler([]));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Either NIK or email is required to request sign TOTP');
        $esign->requestSignTotp(null, null, 1);
    }

    public function testCheckUserStatusWithoutIdentityThrows(): void
    {
        $esign = $this->bareClient(new MockHandler([]));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Either NIK or email is required to check user status');
        $esign->checkUserStatus();
    }

    public function testSealPdfEmptyFilesThrows(): void
    {
        $esign = $this->client(new MockHandler([]));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('At least one PDF file is required for seal');
        $esign->sealPdf('SUB-1', '123456', [], [SignatureProperties::invisible()]);
    }
}
