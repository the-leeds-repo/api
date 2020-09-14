<?php

namespace Tests\Unit\CiviCrm;

use App\CiviCrm\CiviClient;
use App\Models\Organisation;
use App\Transformers\CiviCrm\OrganisationTransformer;
use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Tests\TestCase;

class CiviClientTest extends TestCase
{
    public function test_create_works()
    {
        $organisation = new Organisation();

        $responseMock = $this->createResponseMock(json_encode([
            'id' => 'test-id',
        ]));

        $httpClientMock = $this->createMock(Client::class);
        $httpClientMock->expects($this->once())
            ->method('__call')
            ->with('post', [
                'http://example.com/sites/all/modules/civicrm/extern/rest.php',
                [
                    'query' => [
                        'key' => 'test-site-key',
                        'api_key' => 'test-api-key',
                        'entity' => 'Contact',
                        'action' => 'create',
                        'json' => json_encode([
                            'test_key' => 'test-value',
                        ]),
                    ],
                ]
            ])
            ->willReturn($responseMock);

        $transformerMock = $this->createMock(OrganisationTransformer::class);
        $transformerMock->expects($this->once())
            ->method('transformCreate')
            ->with($organisation)
            ->willReturn(['test_key' => 'test-value']);

        $client = new CiviClient(
            $httpClientMock,
            'http://example.com',
            'test-site-key',
            'test-api-key',
            $transformerMock
        );

        $id = $client->create($organisation);

        $this->assertEquals('test-id', $id);
    }

    /**
     * @param string $response
     * @return \PHPUnit\Framework\MockObject\MockObject|\Psr\Http\Message\ResponseInterface
     */
    protected function createResponseMock(string $response): ResponseInterface
    {
        $streamMock = $this->createMock(StreamInterface::class);
        $streamMock->expects($this->once())
            ->method('getContents')
            ->willReturn($response);

        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock->expects($this->once())
            ->method('getBody')
            ->willReturn($streamMock);

        return $responseMock;
    }
}