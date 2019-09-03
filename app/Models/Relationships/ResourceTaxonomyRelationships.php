<?php

namespace App\Models\Relationships;

use App\Models\Resource;
use App\Models\Taxonomy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait ResourceTaxonomyRelationships
{
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function resource(): BelongsTo
    {
        return $this->belongsTo(Resource::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function taxonomy(): BelongsTo
    {
        return $this->belongsTo(Taxonomy::class);
    }
}
