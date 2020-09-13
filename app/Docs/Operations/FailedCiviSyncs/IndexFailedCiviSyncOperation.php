<?php

namespace App\Docs\Operations\FailedCiviSyncs;

use App\Docs\Parameters\FilterIdParameter;
use App\Docs\Parameters\PageParameter;
use App\Docs\Parameters\PerPageParameter;
use App\Docs\Parameters\SortParameter;
use App\Docs\Schemas\FailedCiviSync\FailedCiviSyncSchema;
use App\Docs\Schemas\PaginatedResourceSchema;
use App\Docs\Tags\FailedCiviSyncsTag;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Operation;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Response;

class IndexFailedCiviSyncOperation extends Operation
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
            ->summary('List all the failed civi syncs')
            ->description('**Permission:** `Global Admin`')
            ->noSecurity()
            ->parameters(
                PageParameter::create(),
                PerPageParameter::create(),
                FilterIdParameter::create(),
                SortParameter::create(null, ['created_at'], '-created_at')
            )
            ->responses(
                Response::ok()->content(
                    MediaType::json()->schema(
                        PaginatedResourceSchema::create(null, FailedCiviSyncSchema::create())
                    )
                )
            );
    }
}
