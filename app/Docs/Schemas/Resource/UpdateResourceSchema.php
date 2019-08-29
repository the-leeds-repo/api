<?php

namespace App\Docs\Schemas\Resource;

use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;

class UpdateResourceSchema extends Schema
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
                'license',
                'author',
                'category_taxonomies',
                'published_at',
                'last_modified_at'
            )
            ->properties(
                Schema::string('organisation_id')
                    ->format(Schema::FORMAT_UUID),
                Schema::string('name'),
                Schema::string('slug'),
                Schema::string('description'),
                Schema::string('url'),
                Schema::string('license')
                    ->nullable(),
                Schema::string('author')
                    ->nullable(),
                Schema::array('category_taxonomies')
                    ->items(
                        Schema::string()
                            ->format(Schema::FORMAT_UUID)
                    ),
                Schema::string('published_at')
                    ->format(Schema::FORMAT_DATE)
                    ->nullable(),
                Schema::string('last_modified_at')
                    ->format(Schema::FORMAT_DATE)
                    ->nullable()
            );
    }
}
