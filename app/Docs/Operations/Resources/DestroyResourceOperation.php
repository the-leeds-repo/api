<?php

namespace App\Docs\Operations\Resources;

use App\Docs\Responses\ResourceDeletedResponse;
use App\Docs\Tags\ResourcesTag;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Operation;

class DestroyResourceOperation extends Operation
{
    /**
     * @param string|null $objectId
     * @throws \GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException
     * @return static
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->action(static::ACTION_DELETE)
            ->tags(ResourcesTag::create())
            ->summary('Delete a specific resource')
            ->description('**Permission:** `Global Admin`')
            ->responses(ResourceDeletedResponse::create());
    }
}
