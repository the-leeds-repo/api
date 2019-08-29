<?php

namespace App\Docs\Schemas\Resource;

use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;

class StoreResourceSchema extends UpdateResourceSchema
{
    /**
     * @param string|null $objectId
     * @throws \GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException
     * @return static
     */
    public static function create(string $objectId = null): BaseObject
    {
        $instance = parent::create($objectId);

        $instance = $instance->required('organisation_id', ...$instance->required);

        return $instance;
    }
}
