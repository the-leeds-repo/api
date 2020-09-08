<?php

namespace App\Docs\Schemas\Organisation;

use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;

class UpdateOrganisationSchema extends Schema
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
            ->required(
                'name',
                'slug',
                'description',
                'url',
                'email',
                'phone',
                'address_line_1',
                'address_line_2',
                'address_line_3',
                'city',
                'county',
                'postcode',
                'country',
                'is_hidden'
            )
            ->properties(
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
                Schema::string('logo_file_id')
                    ->format(Schema::FORMAT_UUID)
                    ->description('The ID of the file uploaded')
                    ->nullable()
            );
    }
}
