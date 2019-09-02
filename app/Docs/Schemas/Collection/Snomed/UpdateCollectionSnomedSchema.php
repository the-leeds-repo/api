<?php

namespace App\Docs\Schemas\Collection\Snomed;

use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;

class UpdateCollectionSnomedSchema extends Schema
{
    /**
     * @param string|null $objectId
     * @throws \GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException
     * @return static
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->type(static::TYPE_OBJECT)
            ->required(
                'code',
                'name',
                'order',
                'category_taxonomies'
            )
            ->properties(
                Schema::string('code'),
                Schema::string('name')
                    ->nullable(),
                Schema::integer('order'),
                Schema::array('category_taxonomies')
                    ->items(
                        Schema::string()
                            ->format(Schema::FORMAT_UUID)
                    )
            );
    }
}
