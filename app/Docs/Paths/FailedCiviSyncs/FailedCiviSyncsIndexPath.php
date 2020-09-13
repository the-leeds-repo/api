<?php

namespace App\Docs\Paths\FailedCiviSyncs;

use App\Docs\Operations\FailedCiviSyncs\IndexFailedCiviSyncOperation;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\PathItem;

class FailedCiviSyncsIndexPath extends PathItem
{
    /**
     * @param string|null $objectId
     * @throws \GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException
     * @return static
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->route('/failed-civi-syncs/index')
            ->operations(
                IndexFailedCiviSyncOperation::create()
                    ->action(IndexFailedCiviSyncOperation::ACTION_POST)
                    ->description(
                        <<<'EOT'
This is an alias of `GET /failed-civi-syncs` which allows all the query string parameters to be passed 
as part of the request body.
EOT
                    )
            );
    }
}
