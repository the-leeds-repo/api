<?php

namespace App\Docs\Paths\Collections\Snomed;

use App\Docs\Operations\Collections\Snomed\IndexCollectionSnomedOperation;
use App\Docs\Operations\Collections\Snomed\StoreCollectionSnomedOperation;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\PathItem;

class CollectionSnomedRootPath extends PathItem
{
    /**
     * @param string|null $objectId
     * @throws \GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException
     * @return static
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->route('/collections/snomed')
            ->operations(
                IndexCollectionSnomedOperation::create(),
                StoreCollectionSnomedOperation::create()
            );
    }
}
