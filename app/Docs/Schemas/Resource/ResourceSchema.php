<?php

namespace App\Docs\Schemas\Resource;

use App\Docs\Schemas\Taxonomy\Category\TaxonomyCategorySchema;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;

class ResourceSchema extends Schema
{
    /**
     * @param string|null $objectId
     * @return static
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->type(static::TYPE_OBJECT)
            ->properties(
                Schema::string('id')
                    ->format(static::FORMAT_UUID),
                Schema::string('organisation_id')
                    ->format(static::FORMAT_UUID),
                Schema::string('name'),
                Schema::string('slug'),
                Schema::string('description'),
                Schema::string('url'),
                Schema::string('license')
                    ->nullable(),
                Schema::string('author')
                    ->nullable(),
                Schema::array('category_taxonomies')
                    ->items(TaxonomyCategorySchema::create()),
                Schema::string('published_at')
                    ->format(Schema::FORMAT_DATE)
                    ->nullable(),
                Schema::string('last_modified_at')
                    ->format(Schema::FORMAT_DATE)
                    ->nullable(),
                Schema::string('created_at')
                    ->format(Schema::FORMAT_DATE_TIME)
                    ->nullable(),
                Schema::string('updated_at')
                    ->format(Schema::FORMAT_DATE_TIME)
                    ->nullable()
            );
    }
}
