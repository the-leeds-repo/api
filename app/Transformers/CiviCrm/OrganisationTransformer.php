<?php

namespace App\Transformers\CiviCrm;

use App\Models\Organisation;

class OrganisationTransformer
{
    /**
     * @param \App\Models\Organisation $organisation
     * @return array
     */
    public function transform(Organisation $organisation): array
    {
        return [];
    }
}
