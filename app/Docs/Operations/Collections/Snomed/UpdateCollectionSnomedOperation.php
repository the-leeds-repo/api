<?php

namespace App\Docs\Operations\Collections\Snomed;

use App\Docs\Schemas\Collection\Snomed\CollectionSnomedSchema;
use App\Docs\Schemas\Collection\Snomed\UpdateCollectionSnomedSchema;
use App\Docs\Schemas\SingleResourceSchema;
use App\Docs\Tags\CollectionSnomedTag;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Operation;
use GoldSpecDigital\ObjectOrientedOAS\Objects\RequestBody;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Response;

class UpdateCollectionSnomedOperation extends Operation
{
    /**
     * @param string|null $objectId
     * @throws \GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException
     * @return static
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->action(static::ACTION_PUT)
            ->tags(CollectionSnomedTag::create())
            ->summary('Update a specific SNOMED collection')
            ->description('**Permission:** `Global Admin`')
            ->requestBody(
                RequestBody::create()
                    ->required()
                    ->content(
                        MediaType::json()->schema(
                            UpdateCollectionSnomedSchema::create()
                        )
                    )
            )
            ->responses(
                Response::ok()->content(
                    MediaType::json()->schema(
                        SingleResourceSchema::create(null, CollectionSnomedSchema::create())
                    )
                )
            );
    }
}
