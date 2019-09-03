<?php

namespace App\Docs\Schemas;

use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;

class SingleResourceSchema extends Schema
{
    /**
     * @param string|null $objectId
     * @param \GoldSpecDigital\ObjectOrientedOAS\Objects\Schema|null $schema
     * @return static
     */
    public static function create(string $objectId = null, Schema $schema = null): BaseObject
    {
        return parent::create($objectId)
            ->type(static::TYPE_OBJECT)
            ->properties(
                Schema::object('data')
                    ->properties(...$schema->properties)
            );
    }
}
