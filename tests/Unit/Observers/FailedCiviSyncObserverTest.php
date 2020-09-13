<?php

namespace Tests\Unit\Observers;

use App\Models\FailedCiviSync;
use App\Models\Organisation;
use App\Observers\FailedCiviSyncObserver;
use Tests\TestCase;

class FailedCiviSyncObserverTest extends TestCase
{
    public function test_deleted_deletes_syncs_for_same_organisation()
    {
        $organisation = factory(Organisation::class)->create();

        $failedCiviSyncOne = factory(FailedCiviSync::class)->make([
            'organisation_id' => $organisation->id,
        ]);
        $failedCiviSyncTwo = factory(FailedCiviSync::class)->create([
            'organisation_id' => $organisation->id,
        ]);
        $failedCiviSyncThree = factory(FailedCiviSync::class)->create();

        $observer = new FailedCiviSyncObserver();
        $observer->deleted($failedCiviSyncOne);

        $this->assertDatabaseMissing('failed_civi_syncs', [
            'id' => $failedCiviSyncOne->id,
        ]);
        $this->assertDatabaseMissing('failed_civi_syncs', [
            'id' => $failedCiviSyncTwo->id,
        ]);
        $this->assertDatabaseHas('failed_civi_syncs', [
            'id' => $failedCiviSyncThree->id,
        ]);
    }
}
