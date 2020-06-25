<?php

namespace App\Docs\Paths\Search;

use App\Docs\Operations\Search\Resources\StoreResourcesSearchOperation;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\PathItem;

class SearchResourcesPath extends PathItem
{
    /**
     * @param string|null $objectId
     * @throws \GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException
     * @return static
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->route('/search/resources')
            ->operations(
                StoreResourcesSearchOperation::create()
            );
    }
}
