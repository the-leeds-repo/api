<?php

namespace App\Docs\Operations\Resources;

use App\Docs\Schemas\Resource\StoreResourceSchema;
use App\Docs\Schemas\ResourceSchema;
use App\Docs\Tags\ResourcesTag;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Operation;
use GoldSpecDigital\ObjectOrientedOAS\Objects\RequestBody;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Response;

class StoreResourceOperation extends Operation
{
    /**
     * @param string|null $objectId
     * @throws \GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException
     * @return static
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->action(static::ACTION_POST)
            ->tags(ResourcesTag::create())
            ->summary('Create a resource')
            ->description('**Permission:** `Organisation Admin`')
            ->requestBody(
                RequestBody::create()
                    ->required()
                    ->content(
                        MediaType::json()->schema(StoreResourceSchema::create())
                    )
            )
            ->responses(
                Response::created()->content(
                    MediaType::json()->schema(
                        ResourceSchema::create(null, \App\Docs\Schemas\Resource\ResourceSchema::create())
                    )
                )
            );
    }
}
