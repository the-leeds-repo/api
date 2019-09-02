<?php

namespace App\Docs\Operations\Collections\Snomed;

use App\Docs\Schemas\Collection\Snomed\CollectionSnomedSchema;
use App\Docs\Schemas\SingleResourceSchema;
use App\Docs\Tags\CollectionSnomedTag;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Operation;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Response;

class ShowCollectionSnomedOperation extends Operation
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
            ->tags(CollectionSnomedTag::create())
            ->summary('Get a specific SNOMED collection')
            ->description('**Permission:** `Open`')
            ->noSecurity()
            ->responses(
                Response::ok()->content(
                    MediaType::json()->schema(
                        SingleResourceSchema::create(null, CollectionSnomedSchema::create())
                    )
                )
            );
    }
}
