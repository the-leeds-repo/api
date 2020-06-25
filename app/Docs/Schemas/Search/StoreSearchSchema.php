<?php

namespace App\Docs\Schemas\Search;

use App\Contracts\ServiceSearch;
use App\Models\Service;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;

class StoreSearchSchema extends Schema
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
            ->properties(
                Schema::integer('page'),
                Schema::integer('per_page')
                    ->default(config('tlr.pagination_results')),
                Schema::string('query'),
                Schema::string('category'),
                Schema::string('persona'),
                Schema::object('category_taxonomy')
                    ->properties(
                        Schema::string('id')
                            ->format(Schema::FORMAT_UUID),
                        Schema::string('name')
                    ),
                Schema::string('wait_time')
                    ->enum(
                        Service::WAIT_TIME_ONE_WEEK,
                        Service::WAIT_TIME_TWO_WEEKS,
                        Service::WAIT_TIME_THREE_WEEKS,
                        Service::WAIT_TIME_MONTH,
                        Service::WAIT_TIME_LONGER
                    ),
                Schema::boolean('is_free'),
                Schema::string('order')
                    ->enum(ServiceSearch::ORDER_RELEVANCE, ServiceSearch::ORDER_DISTANCE)
                    ->default('relevance'),
                Schema::object('location')
                    ->required('lat', 'lon')
                    ->properties(
                        Schema::number('lat')
                            ->format(Schema::FORMAT_FLOAT),
                        Schema::number('lon')
                            ->format(Schema::FORMAT_FLOAT)
                    ),
                Schema::integer('distance')
                    ->default(config('tlr.search_distance'))
            );
    }
}
