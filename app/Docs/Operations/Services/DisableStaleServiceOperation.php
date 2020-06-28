<?php

namespace App\Docs\Operations\Services;

use App\Docs\Schemas\Service\DisableStaleServiceSchema;
use App\Docs\Tags\ServicesTag;
use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Operation;
use GoldSpecDigital\ObjectOrientedOAS\Objects\RequestBody;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Response;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;

class DisableStaleServiceOperation extends Operation
{
    /**
     * @param string|null $objectId
     * @throws \GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException
     * @return static
     */
    public static function create(string $objectId = null): BaseObject
    {
        return parent::create($objectId)
            ->action(static::ACTION_PUT)
            ->tags(ServicesTag::create())
            ->summary('Disables stale services which haven\'t been updated')
            ->description('**Permission:** `Super Admin`')
            ->noSecurity()
            ->requestBody(
                RequestBody::create()->content(
                    MediaType::json()->schema(DisableStaleServiceSchema::create())
                )
            )
            ->responses(
                Response::ok()->content(
                    MediaType::json()->schema(
                        Schema::object()->properties(
                            Schema::string('message')->example('Stale services disabled')
                        )
                    )
                )
            );
    }
}
