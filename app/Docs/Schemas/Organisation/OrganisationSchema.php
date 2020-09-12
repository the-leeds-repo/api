<?php

namespace App\Docs\Schemas\Organisation;

use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;

class OrganisationSchema extends Schema
{
    /**
     * @param string|null $objectId
     * @return static
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->type(static::TYPE_OBJECT)
            ->properties(
                Schema::string('id')
                    ->format(Schema::TYPE_OBJECT),
                Schema::boolean('has_logo'),
                Schema::string('name'),
                Schema::string('slug'),
                Schema::string('description'),
                Schema::string('url'),
                Schema::string('email'),
                Schema::string('phone'),
                Schema::string('address_line_1')
                    ->nullable(),
                Schema::string('address_line_2')
                    ->nullable(),
                Schema::string('address_line_3')
                    ->nullable(),
                Schema::string('city')
                    ->nullable(),
                Schema::string('county')
                    ->nullable(),
                Schema::string('postcode')
                    ->nullable(),
                Schema::string('country')
                    ->nullable(),
                Schema::boolean('is_hidden'),
                Schema::boolean('civi_sync_enabled'),
                Schema::string('civi_id')
                    ->nullable(),
                Schema::string('created_at')
                    ->format(Schema::FORMAT_DATE_TIME)
                    ->nullable(),
                Schema::string('updated_at')
                    ->format(Schema::FORMAT_DATE_TIME)
                    ->nullable()
            );
    }
}
