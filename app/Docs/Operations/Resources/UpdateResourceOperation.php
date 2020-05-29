<?php

namespace App\Docs\Operations\Resources;

use App\Docs\Responses\UpdateRequestReceivedResponse;
use App\Docs\Schemas\Resource\UpdateResourceSchema;
use App\Docs\Tags\ResourcesTag;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Operation;
use GoldSpecDigital\ObjectOrientedOAS\Objects\RequestBody;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;

class UpdateResourceOperation extends Operation
{
    /**
     * @param string|null $objectId
     * @throws \GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException
     * @return static
     */
    public static function create(string $objectId = null): BaseObject
    {
        $updateResourceSchema = UpdateResourceSchema::create();
        $updateResourceSchema = $updateResourceSchema->properties(
            Schema::boolean('preview')
                ->default(false)
                ->description('When enabled, only a preview of the update request will be generated'),
            ...$updateResourceSchema->properties
        );

        return parent::create($objectId)
            ->action(static::ACTION_PUT)
            ->tags(ResourcesTag::create())
            ->summary('Update a specific resource')
            ->description('**Permission:** `Organisation Admin`')
            ->requestBody(
                RequestBody::create()
                    ->required()
                    ->content(
                        MediaType::json()->schema($updateResourceSchema)
                    )
            )
            ->responses(
                UpdateRequestReceivedResponse::create(null, UpdateResourceSchema::create())
            );
    }
}
