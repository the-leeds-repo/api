<?php

namespace App\Docs\Operations\FailedCiviSyncs;

use App\Docs\Schemas\Organisation\OrganisationSchema;
use App\Docs\Schemas\SingleResourceSchema;
use App\Docs\Tags\FailedCiviSyncsTag;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Operation;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Response;

class RetryFailedCiviSyncOperation extends Operation
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
            ->tags(FailedCiviSyncsTag::create())
            ->summary('Retry a specific failed CiviCRM sync')
            ->description('**Permission:** `Global Admin`')
            ->responses(
                Response::ok()->content(
                    MediaType::json()->schema(
                        SingleResourceSchema::create(null, OrganisationSchema::create())
                    )
                )
            );
    }
}
