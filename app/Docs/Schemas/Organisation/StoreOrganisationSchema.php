<?php

namespace App\Docs\Schemas\Organisation;

use GoldSpecDigital\ObjectOrientedOAS\Objects\BaseObject;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;

class StoreOrganisationSchema extends UpdateOrganisationSchema
{
    /**
     * @param string|null $objectId
     * @throws \GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException
     * @return static
     */
    public static function create(string $objectId = null): BaseObject
    {
        $instance = parent::create($objectId);

        $requiredFields = array_filter($instance->required, function (string $requiredField): bool {
            return $requiredField !== 'civi_id';
        });

        $properties = array_filter($instance->properties, function (Schema $schema): bool {
            return $schema->objectId !== 'civi_id';
        });

        return $instance
            ->required(...$requiredFields)
            ->properties(...$properties);
    }
}
