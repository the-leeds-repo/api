<?php

namespace App\Docs\Operations\Collections\Snomed;

use App\Docs\Schemas\Collection\Snomed\CollectionSnomedSchema;
use App\Docs\Schemas\Collection\Snomed\StoreCollectionSnomedSchema;
use App\Docs\Schemas\SingleResourceSchema;
use App\Docs\Tags\CollectionSnomedTag;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Operation;
use GoldSpecDigital\ObjectOrientedOAS\Objects\RequestBody;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Response;

class StoreCollectionSnomedOperation extends Operation
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
            ->tags(CollectionSnomedTag::create())
            ->summary('Create a SNOMED collection')
            ->description('**Permission:** `Super Admin`')
            ->requestBody(
                RequestBody::create()
                    ->required()
                    ->content(
                        MediaType::json()->schema(
                            StoreCollectionSnomedSchema::create()
                        )
                    )
            )
            ->responses(
                Response::created()->content(
                    MediaType::json()->schema(
                        SingleResourceSchema::create(null, CollectionSnomedSchema::create())
                    )
                )
            );
    }
}
