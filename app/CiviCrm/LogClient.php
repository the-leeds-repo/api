<?php

namespace App\CiviCrm;

use App\Models\Organisation;
use App\Transformers\CiviCrm\OrganisationTransformer;

class LogClient implements ClientInterface
{
    /**
     * @var \App\Transformers\CiviCrm\OrganisationTransformer
     */
    protected $transformer;

    /**
     * LogClient constructor.
     *
     * @param \App\Transformers\CiviCrm\OrganisationTransformer $transformer
     */
    public function __construct(OrganisationTransformer $transformer)
    {
        $this->transformer = $transformer;
    }

    /**
     * @inheritDoc
     */
    public function create(Organisation $organisation): string
    {
        logger()->info(
            "Created contact for organisation [{$organisation->id}].",
            $this->transformer->transformCreate($organisation)
        );

        return 'log-id';
    }

    /**
     * @inheritDoc
     */
    public function update(Organisation $organisation): void
    {
        logger()->info(
            "Updated contact for organisation [{$organisation->id}].",
            $this->transformer->transformCreate($organisation)
        );
    }

    /**
     * @inheritDoc
     */
    public function delete(Organisation $organisation): void
    {
        logger()->info(
            "Marked contact as deleted for organisation [{$organisation->id}].",
            $this->transformer->transformCreate($organisation)
        );
    }
}
