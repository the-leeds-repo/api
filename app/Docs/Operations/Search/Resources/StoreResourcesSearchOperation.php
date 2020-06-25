<?php

namespace App\Docs\Operations\Search\Resources;

use App\Docs\Parameters\PageParameter;
use App\Docs\Parameters\PerPageParameter;
use App\Docs\Schemas\PaginatedResourceSchema;
use App\Docs\Schemas\Resource\ResourceSchema;
use App\Docs\Schemas\Search\StoreResourceSearchSchema;
use App\Docs\Tags\SearchTag;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Operation;
use GoldSpecDigital\ObjectOrientedOAS\Objects\RequestBody;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Response;

class StoreResourcesSearchOperation extends Operation
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
            ->tags(SearchTag::create())
            ->summary('Perform a search for resources')
            ->description('**Permission:** `Open`')
            ->noSecurity()
            ->parameters(
                PageParameter::create(),
                PerPageParameter::create()
            )
            ->requestBody(
                RequestBody::create()->content(
                    MediaType::json()->schema(StoreResourceSearchSchema::create())
                )
            )
            ->responses(
                Response::ok()->content(
                    MediaType::json()->schema(
                        PaginatedResourceSchema::create(null, ResourceSchema::create())
                    )
                )
            );
    }
}
