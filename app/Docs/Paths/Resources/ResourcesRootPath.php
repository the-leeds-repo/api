<?php

namespace App\Docs\Paths\Resources;

use App\Docs\Operations\Resources\IndexResourceOperation;
use App\Docs\Operations\Resources\StoreResourceOperation;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\PathItem;

class ResourcesRootPath extends PathItem
{
    /**
     * @param string|null $objectId
     * @throws \GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException
     * @return static
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->route('/resources')
            ->operations(
                IndexResourceOperation::create(),
                StoreResourceOperation::create()
            );
    }
}
