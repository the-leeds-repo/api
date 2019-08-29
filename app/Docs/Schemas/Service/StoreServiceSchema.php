<?php

namespace App\Docs\Schemas\Service;

use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;

class StoreServiceSchema extends UpdateServiceSchema
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
