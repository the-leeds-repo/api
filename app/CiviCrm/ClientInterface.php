<?php

namespace App\CiviCrm;

use App\Models\Organisation;

interface ClientInterface
{
    /**
     * @param \App\Models\Organisation $organisation
     * @return string the ID of the created CiviCRM contact
     */
    public function create(Organisation $organisation): string;

    /**
     * @param \App\Models\Organisation $organisation
     */
    public function update(Organisation $organisation): void;

    /**
     * @param \App\Models\Organisation $organisation
     */
    public function delete(Organisation $organisation): void;
}
