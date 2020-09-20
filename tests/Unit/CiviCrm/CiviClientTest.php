<?php

namespace Tests\Unit\CiviCrm;

use App\CiviCrm\CiviClient;
use App\CiviCrm\CiviException;
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
        $organisationMock = $this->createMock(Organisation::class);

        $responseMock = $this->createResponseMock(json_encode([
            'id' => 'test-id',
        ]));

        $httpClientMock = $this->createMock(Client::class);
        $httpClientMock->expects($this->at(0))
            ->method('__call')
            ->with(
                'post',
                $this->createPostArguments('Contact', 'create', ['test_key' => 'contact-value'])
            )
            ->willReturn($responseMock);
        $httpClientMock->expects($this->at(1))
            ->method('__call')
            ->with(
                'post',
                $this->createPostArguments('Website', 'create', ['test_key' => 'website-value'])
            )
            ->willReturn($responseMock);
        $httpClientMock->expects($this->at(2))
            ->method('__call')
            ->with(
                'post',
                $this->createPostArguments('Phone', 'create', ['test_key' => 'phone-value'])
            )
            ->willReturn($responseMock);
        $httpClientMock->expects($this->at(3))
            ->method('__call')
            ->with(
                'post',
                $this->createPostArguments('Address', 'create', ['test_key' => 'address-value'])
            )
            ->willReturn($responseMock);

        $transformerMock = $this->createMock(OrganisationTransformer::class);
        $transformerMock->expects($this->once())
            ->method('transformCreateContact')
            ->with($organisationMock)
            ->willReturn(['test_key' => 'contact-value']);
        $transformerMock->expects($this->once())
            ->method('transformCreateWebsite')
            ->with($organisationMock)
            ->willReturn(['test_key' => 'website-value']);
        $transformerMock->expects($this->once())
            ->method('transformCreatePhone')
            ->with($organisationMock)
            ->willReturn(['test_key' => 'phone-value']);
        $transformerMock->expects($this->once())
            ->method('transformCreateAddress')
            ->with($organisationMock)
            ->willReturn(['test_key' => 'address-value']);


        $client = new CiviClient(
            $httpClientMock,
            'http://example.com',
            'test-site-key',
            'test-api-key',
            $transformerMock
        );

        $id = $client->create($organisationMock);

        $this->assertEquals('test-id', $id);
    }

    public function test_create_throws_exception()
    {
        $organisationMock = $this->createMock(Organisation::class);

        $responseMock = $this->createResponseMock(json_encode([
            'error_code' => 0,
            'is_error' => 1,
            'error_message' => 'test error message',
        ]));

        $httpClientMock = $this->createMock(Client::class);
        $httpClientMock->expects($this->once())
            ->method('__call')
            ->with(
                'post',
                $this->createPostArguments('Contact', 'create', ['test_key' => 'contact-value'])
            )
            ->willReturn($responseMock);

        $transformerMock = $this->createMock(OrganisationTransformer::class);
        $transformerMock->expects($this->once())
            ->method('transformCreateContact')
            ->with($organisationMock)
            ->willReturn(['test_key' => 'contact-value']);

        $client = new CiviClient(
            $httpClientMock,
            'http://example.com',
            'test-site-key',
            'test-api-key',
            $transformerMock
        );

        $this->expectException(CiviException::class);
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage('test error message');

        $client->create($organisationMock);
    }

    public function test_update_works()
    {
        $organisationMock = $this->createMock(Organisation::class);

        $responseMock = $this->createResponseMock(json_encode([
            'id' => 'test-id',
            'values' => [
                ['id' => 'related-id'],
            ],
        ]));

        $httpClientMock = $this->createMock(Client::class);
        $httpClientMock->expects($this->at(0))
            ->method('__call')
            ->with(
                'post',
                $this->createPostArguments('Contact', 'create', ['test_key' => 'contact-value'])
            )
            ->willReturn($responseMock);
        $httpClientMock->expects($this->at(1))
            ->method('__call')
            ->with(
                'post',
                $this->createPostArguments('Website', 'get', ['test_key' => 'website-value'])
            )
            ->willReturn($responseMock);
        $httpClientMock->expects($this->at(2))
            ->method('__call')
            ->with(
                'post',
                $this->createPostArguments('Website', 'create', ['test_key' => 'website-value'])
            )
            ->willReturn($responseMock);
        $httpClientMock->expects($this->at(3))
            ->method('__call')
            ->with(
                'post',
                $this->createPostArguments('Phone', 'get', ['test_key' => 'phone-value'])
            )
            ->willReturn($responseMock);
        $httpClientMock->expects($this->at(4))
            ->method('__call')
            ->with(
                'post',
                $this->createPostArguments('Phone', 'create', ['test_key' => 'phone-value'])
            )
            ->willReturn($responseMock);
        $httpClientMock->expects($this->at(5))
            ->method('__call')
            ->with(
                'post',
                $this->createPostArguments('Address', 'get', ['test_key' => 'address-value'])
            )
            ->willReturn($responseMock);
        $httpClientMock->expects($this->at(6))
            ->method('__call')
            ->with(
                'post',
                $this->createPostArguments('Address', 'create', ['test_key' => 'address-value'])
            )
            ->willReturn($responseMock);

        $transformerMock = $this->createMock(OrganisationTransformer::class);
        $transformerMock->expects($this->once())
            ->method('transformUpdateContact')
            ->with($organisationMock)
            ->willReturn(['test_key' => 'contact-value']);
        $transformerMock->expects($this->once())
            ->method('transformGetWebsite')
            ->with($organisationMock)
            ->willReturn(['test_key' => 'website-value']);
        $transformerMock->expects($this->once())
            ->method('transformUpdateWebsite')
            ->with($organisationMock, 'related-id')
            ->willReturn(['test_key' => 'website-value']);
        $transformerMock->expects($this->once())
            ->method('transformGetPhone')
            ->with($organisationMock)
            ->willReturn(['test_key' => 'phone-value']);
        $transformerMock->expects($this->once())
            ->method('transformUpdatePhone')
            ->with($organisationMock, 'related-id')
            ->willReturn(['test_key' => 'phone-value']);
        $transformerMock->expects($this->once())
            ->method('transformGetAddress')
            ->with($organisationMock)
            ->willReturn(['test_key' => 'address-value']);
        $transformerMock->expects($this->once())
            ->method('transformUpdateAddress')
            ->with($organisationMock, 'related-id')
            ->willReturn(['test_key' => 'address-value']);

        $client = new CiviClient(
            $httpClientMock,
            'http://example.com',
            'test-site-key',
            'test-api-key',
            $transformerMock
        );

        $client->update($organisationMock);
    }

    public function test_update_throws_exception()
    {
        $organisationMock = $this->createMock(Organisation::class);

        $responseMock = $this->createResponseMock(json_encode([
            'error_code' => 0,
            'is_error' => 1,
            'error_message' => 'test error message',
        ]));

        $httpClientMock = $this->createMock(Client::class);
        $httpClientMock->expects($this->once())
            ->method('__call')
            ->with(
                'post',
                $this->createPostArguments('Contact', 'create', ['test_key' => 'contact-value'])
            )
            ->willReturn($responseMock);

        $transformerMock = $this->createMock(OrganisationTransformer::class);
        $transformerMock->expects($this->once())
            ->method('transformUpdateContact')
            ->with($organisationMock)
            ->willReturn(['test_key' => 'contact-value']);

        $client = new CiviClient(
            $httpClientMock,
            'http://example.com',
            'test-site-key',
            'test-api-key',
            $transformerMock
        );

        $this->expectException(CiviException::class);
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage('test error message');

        $client->update($organisationMock);
    }

    public function test_delete_works()
    {
        $organisationMock = $this->createMock(Organisation::class);

        $responseMock = $this->createResponseMock(json_encode([
            'id' => 'test-id',
        ]));

        $httpClientMock = $this->createMock(Client::class);
        $httpClientMock->expects($this->once())
            ->method('__call')
            ->with(
                'post',
                $this->createPostArguments('Contact', 'create', ['test_key' => 'contact-value'])
            )
            ->willReturn($responseMock);

        $transformerMock = $this->createMock(OrganisationTransformer::class);
        $transformerMock->expects($this->once())
            ->method('transformDeleteContact')
            ->with($organisationMock)
            ->willReturn(['test_key' => 'contact-value']);

        $client = new CiviClient(
            $httpClientMock,
            'http://example.com',
            'test-site-key',
            'test-api-key',
            $transformerMock
        );

        $client->delete($organisationMock);
    }

    public function test_delete_throws_exception()
    {
        $organisationMock = $this->createMock(Organisation::class);

        $responseMock = $this->createResponseMock(json_encode([
            'error_code' => 0,
            'is_error' => 1,
            'error_message' => 'test error message',
        ]));

        $httpClientMock = $this->createMock(Client::class);
        $httpClientMock->expects($this->once())
            ->method('__call')
            ->with(
                'post',
                $this->createPostArguments('Contact', 'create', ['test_key' => 'contact-value'])
            )
            ->willReturn($responseMock);

        $transformerMock = $this->createMock(OrganisationTransformer::class);
        $transformerMock->expects($this->once())
            ->method('transformDeleteContact')
            ->with($organisationMock)
            ->willReturn(['test_key' => 'contact-value']);

        $client = new CiviClient(
            $httpClientMock,
            'http://example.com',
            'test-site-key',
            'test-api-key',
            $transformerMock
        );

        $this->expectException(CiviException::class);
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage('test error message');

        $client->delete($organisationMock);
    }

    /**
     * @param string $response
     * @return \PHPUnit\Framework\MockObject\MockObject|\Psr\Http\Message\ResponseInterface
     */
    protected function createResponseMock(string $response): ResponseInterface
    {
        $streamMock = $this->createMock(StreamInterface::class);
        $streamMock->expects($this->any())
            ->method('getContents')
            ->willReturn($response);

        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock->expects($this->any())
            ->method('getBody')
            ->willReturn($streamMock);

        return $responseMock;
    }

    /**
     * @param string $entity
     * @param string $action
     * @param array $json
     * @return array
     */
    protected function createPostArguments(string $entity, string $action, array $json): array
    {
        return [
            'http://example.com/sites/all/modules/civicrm/extern/rest.php',
            [
                'query' => [
                    'key' => 'test-site-key',
                    'api_key' => 'test-api-key',
                    'entity' => $entity,
                    'action' => $action,
                    'json' => json_encode(
                        array_merge(['sequential' => 1], $json)
                    ),
                ],
            ],
        ];
    }
}
