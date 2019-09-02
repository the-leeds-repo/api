<?php

namespace App\Docs\Operations\Collections\Snomed;

use App\Docs\Parameters\FilterIdParameter;
use App\Docs\Parameters\PageParameter;
use App\Docs\Parameters\PerPageParameter;
use App\Docs\Schemas\Collection\Snomed\CollectionSnomedSchema;
use App\Docs\Schemas\PaginatedResourceSchema;
use App\Docs\Tags\CollectionSnomedTag;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Operation;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Response;

class IndexCollectionSnomedOperation extends Operation
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
            ->tags(CollectionSnomedTag::create())
            ->summary('List all the SNOMED collections')
            ->description(
                <<<'EOT'
**Permission:** `Open`

---

Collections are returned in ascending order of the order field.
EOT
            )
            ->noSecurity()
            ->parameters(
                PageParameter::create(),
                PerPageParameter::create(),
                FilterIdParameter::create()
            )
            ->responses(
                Response::ok()->content(
                    MediaType::json()->schema(
                        PaginatedResourceSchema::create(null, CollectionSnomedSchema::create())
                    )
                )
            );
    }
}
