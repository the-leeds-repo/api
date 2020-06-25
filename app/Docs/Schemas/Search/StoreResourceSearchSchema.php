<?php

namespace App\Docs\Schemas\Search;

use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;

class StoreResourceSearchSchema extends Schema
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
                Schema::integer('page'),
                Schema::integer('per_page')
                    ->default(config('tlr.pagination_results')),
                Schema::string('query'),
                Schema::string('category'),
                Schema::string('persona'),
                Schema::object('category_taxonomy')
                    ->properties(
                        Schema::string('id')
                            ->format(Schema::FORMAT_UUID),
                        Schema::string('name')
                    )
            );
    }
}
