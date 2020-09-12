<?php

namespace App\Civi;

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
    public function create(Organisation $organisation): void
    {
        logger()->info(
            "Created contact for organisation [{$organisation->id}].",
            $this->transformer->transform($organisation)
        );
    }

    /**
     * @inheritDoc
     */
    public function update(Organisation $organisation): void
    {
        logger()->info(
            "Updated contact for organisation [{$organisation->id}].",
            $this->transformer->transform($organisation)
        );
    }

    /**
     * @inheritDoc
     */
    public function delete(Organisation $organisation): void
    {
        logger()->info(
            "Marked contact as deleted for organisation [{$organisation->id}].",
            $this->transformer->transform($organisation)
        );
    }
}
