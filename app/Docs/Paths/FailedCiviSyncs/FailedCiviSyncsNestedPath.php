<?php

namespace App\Docs\Paths\FailedCiviSyncs;

use App\Docs\Operations\FailedCiviSyncs\ShowFailedCiviSyncOperation;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Parameter;
use GoldSpecDigital\ObjectOrientedOAS\Objects\PathItem;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;

class FailedCiviSyncsNestedPath extends PathItem
{
    /**
     * @param string|null $objectId
     * @throws \GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException
     * @return static
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->route('/failed-civi-syncs/{failed_civi_sync}')
            ->parameters(
                Parameter::path()
                    ->name('failed_civi_sync')
                    ->description('The ID of the failed CiviCRM sync')
                    ->required()
                    ->schema(Schema::string())
            )
            ->operations(
                ShowFailedCiviSyncOperation::create()
            );
    }
}
