<?php

namespace App\Docs\Paths\Resources;

use App\Docs\Operations\Resources\DestroyResourceOperation;
use App\Docs\Operations\Resources\ShowResourceOperation;
use App\Docs\Operations\Resources\UpdateResourceOperation;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Parameter;
use GoldSpecDigital\ObjectOrientedOAS\Objects\PathItem;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;

class ResourcesNestedPath extends PathItem
{
    /**
     * @param string|null $objectId
     * @throws \GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException
     * @return static
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->route('/resources/{resource}')
            ->parameters(
                Parameter::path()
                    ->name('resource')
                    ->description('The ID or slug of the resource')
                    ->required()
                    ->schema(Schema::string())
            )
            ->operations(
                ShowResourceOperation::create(),
                UpdateResourceOperation::create(),
                DestroyResourceOperation::create()
            );
    }
}
