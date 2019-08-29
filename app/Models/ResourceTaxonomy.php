<?php

namespace App\Models;

use App\Models\Mutators\ResourceTaxonomyMutators;
use App\Models\Relationships\ResourceTaxonomyRelationships;
use App\Models\Scopes\ResourceTaxonomyScopes;

class ResourceTaxonomy extends Model
{
    use ResourceTaxonomyMutators;
    use ResourceTaxonomyRelationships;
    use ResourceTaxonomyScopes;
}
