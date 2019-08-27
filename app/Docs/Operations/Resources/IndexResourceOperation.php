<?php

namespace App\Docs\Operations\Resources;

use App\Docs\Parameters\FilterIdParameter;
use App\Docs\Parameters\FilterParameter;
use App\Docs\Parameters\IncludeParameter;
use App\Docs\Parameters\PageParameter;
use App\Docs\Parameters\PerPageParameter;
use App\Docs\Parameters\SortParameter;
use App\Docs\Schemas\PaginationSchema;
use App\Docs\Schemas\Resource\ResourceSchema;
use App\Docs\Tags\ResourcesTag;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Operation;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Response;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;

class IndexResourceOperation extends Operation
{
    /**
     * @param string|null $objectId
     * @throws \GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException
     * @return static
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->action(static::ACTION_GET)
            ->tags(ResourcesTag::create())
            ->summary('List all the resources')
            ->description(
                <<<'EOT'
**Permission:** `Open`

---

Resources are returned in ascending order of their name.
EOT
            )
            ->noSecurity()
            ->parameters(
                PageParameter::create(),
                PerPageParameter::create(),
                FilterIdParameter::create(),
                FilterParameter::create(null, 'organisation_id')
                    ->description('Comma separated list of organisation IDs to filter by')
                    ->schema(
                        Schema::array()->items(
                            Schema::string()->format(Schema::FORMAT_UUID)
                        )
                    )
                    ->style(FilterParameter::STYLE_SIMPLE),
                FilterParameter::create(null, 'name')
                    ->description('Name to filter by')
                    ->schema(Schema::string()),
                FilterParameter::create(null, 'organisation_name')
                    ->description('Organisation name to filter by')
                    ->schema(Schema::string()),
                IncludeParameter::create(null, ['organisation']),
                SortParameter::create(null, [
                    'name',
                    'organisation_name',
                ], 'name')
            )
            ->responses(
                Response::ok()->content(
                    MediaType::json()->schema(
                        PaginationSchema::create(null, ResourceSchema::create())
                    )
                )
            );
    }
}
