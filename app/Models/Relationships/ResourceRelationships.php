<?php

namespace App\Models\Relationships;

use App\Models\ResourceTaxonomy;
use App\Models\Taxonomy;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait ResourceRelationships
{
    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function resourceTaxonomies(): HasMany
    {
        return $this->hasMany(ResourceTaxonomy::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function taxonomies(): BelongsToMany
    {
        return $this->belongsToMany(Taxonomy::class, (new ResourceTaxonomy())->getTable());
    }
}
