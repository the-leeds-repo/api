<?php

namespace App\Docs\Paths\Resources;

use App\Docs\Operations\Resources\IndexResourceOperation;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\PathItem;

class ResourcesIndexPath extends PathItem
{
    /**
     * @param string|null $objectId
     * @throws \GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException
     * @return static
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->route('/resources/index')
            ->operations(
                IndexResourceOperation::create()
                    ->action(IndexResourceOperation::ACTION_POST)
                    ->description(
                        <<<'EOT'
This is an alias of `GET /resources` which allows all the query string parameters to be passed as 
part of the request body.
EOT
                    )
            );
    }
}
