<?php

namespace Tests\Unit\Observers;

use App\Civi\CiviException;
use App\Civi\ClientInterface;
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
}
