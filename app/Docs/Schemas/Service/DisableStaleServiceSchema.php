<?php

namespace App\Docs\Schemas\Service;

use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;

class DisableStaleServiceSchema extends Schema
{
    /**
     * @param string|null $objectId
     * @throws \GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException
     * @return static
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->type(static::TYPE_OBJECT)
            ->required('last_modified_at')
            ->properties(
                Schema::string('last_modified_at')
                    ->format(Schema::FORMAT_DATE)
                    ->description('The date to specify when services last updated before should be disabled')
            );
    }
}
