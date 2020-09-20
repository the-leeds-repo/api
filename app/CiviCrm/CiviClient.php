<?php

namespace App\CiviCrm;

use App\Models\Organisation;
use App\Transformers\CiviCrm\OrganisationTransformer;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Psr\Http\Message\ResponseInterface;

class CiviClient implements ClientInterface
{
    protected const ENTITY_CONTACT = 'Contact';
    protected const ENTITY_PHONE = 'Phone';
    protected const ENTITY_WEBSITE = 'Website';
    protected const ENTITY_ADDRESS = 'Address';

    protected const ACTION_GET = 'get';
    protected const ACTION_CREATE = 'create';

    /**
     * @var \GuzzleHttp\Client
     */
    protected $httpClient;

    /**
     * @var string
     */
    protected $domain;

    /**
     * @var string
     */
    protected $siteKey;

    /**
     * @var string
     */
    protected $apiKey;

    /**
     * @var \App\Transformers\CiviCrm\OrganisationTransformer
     */
    protected $transformer;

    /**
     * CiviClient constructor.
     *
     * @param \GuzzleHttp\Client $httpClient
     * @param string $domain
     * @param string $siteKey
     * @param string $apiKey
     * @param \App\Transformers\CiviCrm\OrganisationTransformer $transformer
     */
    public function __construct(
        Client $httpClient,
        string $domain,
        string $siteKey,
        string $apiKey,
        OrganisationTransformer $transformer
    ) {
        $this->httpClient = $httpClient;
        $this->domain = $domain;
        $this->siteKey = $siteKey;
        $this->apiKey = $apiKey;
        $this->transformer = $transformer;
    }

    /**
     * @inheritDoc
     * @throws \App\CiviCrm\CiviException
     */
    public function create(Organisation $organisation): string
    {
        $response = $this->postRequest(
            static::ENTITY_CONTACT,
            static::ACTION_CREATE,
            $this->transformer->transformCreateContact($organisation)
        );

        $response = $this->decodeResponse($response);
        $contactId = $response['id'];

        $this->postRequest(
            static::ENTITY_WEBSITE,
            static::ACTION_CREATE,
            $this->transformer->transformCreateWebsite($organisation)
        );

        $this->postRequest(
            static::ENTITY_PHONE,
            static::ACTION_CREATE,
            $this->transformer->transformCreatePhone($organisation)
        );

        $this->postRequest(
            static::ENTITY_ADDRESS,
            static::ACTION_CREATE,
            $this->transformer->transformCreateAddress($organisation)
        );

        return $contactId;
    }

    /**
     * @inheritDoc
     * @throws \App\CiviCrm\CiviException
     */
    public function update(Organisation $organisation): void
    {
        $this->postRequest(
            static::ENTITY_CONTACT,
            static::ACTION_CREATE,
            $this->transformer->transformUpdateContact($organisation)
        );

        $websiteResponse = $this->postRequest(
            static::ENTITY_WEBSITE,
            static::ACTION_GET,
            $this->transformer->transformGetWebsite($organisation)
        );

        if (count($websiteResponse['values']) > 0) {
            $this->postRequest(
                static::ENTITY_WEBSITE,
                static::ACTION_CREATE,
                $this->transformer->transformUpdateWebsite(
                    $organisation,
                    $websiteResponse['values'][0]['id']
                )
            );
        } else {
            $this->postRequest(
                static::ENTITY_WEBSITE,
                static::ACTION_CREATE,
                $this->transformer->transformCreateWebsite($organisation)
            );
        }

        $phoneResponse = $this->postRequest(
            static::ENTITY_PHONE,
            static::ACTION_GET,
            $this->transformer->transformGetPhone($organisation)
        );

        if (count($phoneResponse['values']) > 0) {
            $this->postRequest(
                static::ENTITY_PHONE,
                static::ACTION_CREATE,
                $this->transformer->transformUpdatePhone(
                    $organisation,
                    $phoneResponse['values'][0]['id']
                )
            );
        } else {
            $this->postRequest(
                static::ENTITY_PHONE,
                static::ACTION_CREATE,
                $this->transformer->transformCreatePhone($organisation)
            );
        }

        $addressResponse = $this->postRequest(
            static::ENTITY_ADDRESS,
            static::ACTION_GET,
            $this->transformer->transformGetAddress($organisation)
        );

        if (count($addressResponse['values']) > 0) {
            $this->postRequest(
                static::ENTITY_ADDRESS,
                static::ACTION_CREATE,
                $this->transformer->transformUpdateAddress(
                    $organisation,
                    $addressResponse['values'][0]['id']
                )
            );
        } else {
            $this->postRequest(
                static::ENTITY_ADDRESS,
                static::ACTION_CREATE,
                $this->transformer->transformCreateAddress($organisation)
            );
        }
    }

    /**
     * @inheritDoc
     * @throws \App\CiviCrm\CiviException
     */
    public function delete(Organisation $organisation): void
    {
        $this->postRequest(
            static::ENTITY_CONTACT,
            static::ACTION_CREATE,
            $this->transformer->transformDeleteContact($organisation)
        );
    }

    /**
     * @return string
     */
    protected function getEndpoint(): string
    {
        return "{$this->domain}/sites/all/modules/civicrm/extern/rest.php";
    }

    /**
     * @param string $entity
     * @param string $action
     * @param array $params
     * @throws \App\CiviCrm\CiviException
     * @return \Psr\Http\Message\ResponseInterface
     */
    protected function postRequest(string $entity, string $action, array $params): ResponseInterface
    {
        try {
            $response = $this->httpClient->post($this->getEndpoint(), [
                'query' => $this->transformParams($entity, $action, $params),
            ]);
        } catch (ClientException $exception) {
            throw new CiviException($exception->getMessage(), $exception->getCode(), $exception);
        }

        $data = $this->decodeResponse($response);

        if ($data['is_error'] ?? 0 === 1) {
            throw new CiviException(
                $data['error_message'] ?? 'No error message provided.',
                $data['error_code'] ?? 400
            );
        }

        return $response;
    }

    /**
     * @param string $entity
     * @param string $action
     * @param array $params
     * @return array
     */
    protected function transformParams(string $entity, string $action, array $params): array
    {
        return [
            'key' => $this->siteKey,
            'api_key' => $this->apiKey,
            'entity' => $entity,
            'action' => $action,
            'json' => json_encode(
                array_merge(['sequential' => 1], $params)
            ),
        ];
    }

    /**
     * @param \Psr\Http\Message\ResponseInterface $response
     * @return array
     */
    protected function decodeResponse(ResponseInterface $response): array
    {
        $contents = $response->getBody()->getContents();
        $response->getBody()->rewind();

        return json_decode($contents, true);
    }
}
