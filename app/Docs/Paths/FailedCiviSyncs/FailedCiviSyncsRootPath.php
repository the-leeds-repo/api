<?php

namespace App\Docs\Paths\FailedCiviSyncs;

use App\Docs\Operations\FailedCiviSyncs\IndexFailedCiviSyncOperation;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\PathItem;

class FailedCiviSyncsRootPath extends PathItem
{
    /**
     * @param string|null $objectId
     * @throws \GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException
     * @return static
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->route('/failed-civi-syncs')
            ->operations(
                IndexFailedCiviSyncOperation::create()
                    ->action(IndexFailedCiviSyncOperation::ACTION_GET)
            );
    }
}
