<?php

namespace App\Docs\Paths\Collections\Snomed;

use App\Docs\Operations\Collections\Snomed\DestroyCollectionSnomedOperation;
use App\Docs\Operations\Collections\Snomed\ShowCollectionSnomedOperation;
use App\Docs\Operations\Collections\Snomed\UpdateCollectionSnomedOperation;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Parameter;
use GoldSpecDigital\ObjectOrientedOAS\Objects\PathItem;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;

class CollectionSnomedNestedPath extends PathItem
{
    /**
     * @param string|null $objectId
     * @throws \GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException
     * @return static
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->route('/collections/snomed/{snomed}')
            ->parameters(
                Parameter::path()
                    ->name('snomed')
                    ->description('The ID of the SNOMED collection')
                    ->required()
                    ->schema(Schema::string()->format(Schema::FORMAT_UUID))
            )
            ->operations(
                ShowCollectionSnomedOperation::create(),
                UpdateCollectionSnomedOperation::create(),
                DestroyCollectionSnomedOperation::create()
            );
    }
}
