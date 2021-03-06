<?php

namespace App\Docs\Operations\Resources;

use App\Docs\Parameters\IncludeParameter;
use App\Docs\Schemas\Resource\ResourceSchema;
use App\Docs\Schemas\SingleResourceSchema;
use App\Docs\Tags\ResourcesTag;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Operation;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Response;

class ShowResourceOperation extends Operation
{
    /**
     * @param string|null $objectId
     * @throws \GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException
     * @return static
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->action(static::ACTION_GET)
            ->tags(ResourcesTag::create())
            ->summary('Get a specific resource')
            ->description('**Permission:** `Open`')
            ->noSecurity()
            ->parameters(
                IncludeParameter::create(null, ['organisation'])
            )
            ->responses(
                Response::ok()->content(
                    MediaType::json()->schema(
                        SingleResourceSchema::create(null, ResourceSchema::create())
                    )
                )
            );
    }
}
