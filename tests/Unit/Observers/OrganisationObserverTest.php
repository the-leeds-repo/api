<?php

namespace Tests\Unit\Observers;

use App\CiviCrm\CiviException;
use App\CiviCrm\ClientInterface;
use App\Models\FailedCiviSync;
use App\Models\Organisation;
use App\Observers\OrganisationObserver;
use Tests\TestCase;

class OrganisationObserverTest extends TestCase
{
    public function test_created_creates_contact_on_civi()
    {
        $organisation = new Organisation([
            'civi_sync_enabled' => true,
        ]);

        $civiClientMock = $this->createMock(ClientInterface::class);
        $civiClientMock->expects($this->once())
            ->method('create')
            ->with($organisation);

        $observer = new OrganisationObserver($civiClientMock);

        $observer->created($organisation);
    }

    public function test_created_does_not_create_contact_on_civi()
    {
        $organisation = new Organisation([
            'civi_sync_enabled' => false,
        ]);

        $civiClientMock = $this->createMock(ClientInterface::class);
        $civiClientMock->expects($this->never())
            ->method('create');

        $observer = new OrganisationObserver($civiClientMock);

        $observer->created($organisation);
    }

    public function test_created_handles_civi_exception()
    {
        $organisation = new Organisation([
            'civi_sync_enabled' => true,
        ]);

        $civiClientMock = $this->createMock(ClientInterface::class);
        $civiClientMock->expects($this->once())
            ->method('create')
            ->with($organisation)
            ->willThrowException(new CiviException());

        $observer = new OrganisationObserver($civiClientMock);

        $observer->created($organisation);
    }

    public function test_updated_updates_contact_on_civi()
    {
        $organisation = new Organisation([
            'civi_sync_enabled' => true,
        ]);

        $civiClientMock = $this->createMock(ClientInterface::class);
        $civiClientMock->expects($this->once())
            ->method('update')
            ->with($organisation);

        $observer = new OrganisationObserver($civiClientMock);

        $observer->updated($organisation);
    }

    public function test_updated_does_not_create_contact_on_civi()
    {
        $organisation = new Organisation([
            'civi_sync_enabled' => false,
        ]);

        $civiClientMock = $this->createMock(ClientInterface::class);
        $civiClientMock->expects($this->never())
            ->method('update');

        $observer = new OrganisationObserver($civiClientMock);

        $observer->updated($organisation);
    }

    public function test_updated_handles_civi_exception()
    {
        $organisation = new Organisation([
            'civi_sync_enabled' => true,
        ]);

        $civiClientMock = $this->createMock(ClientInterface::class);
        $civiClientMock->expects($this->once())
            ->method('update')
            ->with($organisation)
            ->willThrowException(new CiviException());

        $observer = new OrganisationObserver($civiClientMock);

        $observer->updated($organisation);
    }

    public function test_deleting_updates_contact_on_civi()
    {
        $organisation = new Organisation([
            'civi_sync_enabled' => true,
        ]);

        $civiClientMock = $this->createMock(ClientInterface::class);
        $civiClientMock->expects($this->once())
            ->method('delete')
            ->with($organisation);

        $observer = new OrganisationObserver($civiClientMock);

        $observer->deleting($organisation);
    }

    public function test_deleting_does_not_create_contact_on_civi()
    {
        $organisation = new Organisation([
            'civi_sync_enabled' => false,
        ]);

        $civiClientMock = $this->createMock(ClientInterface::class);
        $civiClientMock->expects($this->never())
            ->method('delete');

        $observer = new OrganisationObserver($civiClientMock);

        $observer->deleting($organisation);
    }

    public function test_deleting_handles_civi_exception()
    {
        $organisation = new Organisation([
            'civi_sync_enabled' => true,
        ]);

        $civiClientMock = $this->createMock(ClientInterface::class);
        $civiClientMock->expects($this->once())
            ->method('delete')
            ->with($organisation)
            ->willThrowException(new CiviException());

        $observer = new OrganisationObserver($civiClientMock);

        $observer->deleting($organisation);
    }

    public function test_deleting_deletes_failed_civi_syncs()
    {
        $organisation = factory(Organisation::class)->create([
            'civi_sync_enabled' => true,
        ]);

        $failedCiviSync = factory(FailedCiviSync::class)->create([
            'organisation_id' => $organisation->id,
        ]);

        $civiClientMock = $this->createMock(ClientInterface::class);

        $observer = new OrganisationObserver($civiClientMock);

        $observer->deleting($organisation);

        $this->assertDatabaseMissing('failed_civi_syncs', [
            'id' => $failedCiviSync->id,
        ]);
    }
}
