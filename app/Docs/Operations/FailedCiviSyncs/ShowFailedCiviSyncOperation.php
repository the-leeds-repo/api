<?php

namespace App\Docs\Operations\FailedCiviSyncs;

use App\Docs\Schemas\FailedCiviSync\FailedCiviSyncSchema;
use App\Docs\Schemas\SingleResourceSchema;
use App\Docs\Tags\FailedCiviSyncsTag;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Operation;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Response;

class ShowFailedCiviSyncOperation extends Operation
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
            ->tags(FailedCiviSyncsTag::create())
            ->summary('Get a specific failed CiviCRM sync')
            ->description('**Permission:** `Global Admin`')
            ->responses(
                Response::ok()->content(
                    MediaType::json()->schema(
                        SingleResourceSchema::create(null, FailedCiviSyncSchema::create())
                    )
                )
            );
    }
}
