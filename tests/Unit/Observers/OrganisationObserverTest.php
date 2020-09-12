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
            'name' => 'Acme',
            'description' => 'Lorem ipsum',
            'url' => 'acme.example.com',
            'email' => 'hello@ecme.example.com',
            'phone' => '01130000000',
            'civi_sync_enabled' => true,
            'civi_id' => 'test-id',
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
            'name' => 'Acme',
            'description' => 'Lorem ipsum',
            'url' => 'acme.example.com',
            'email' => 'hello@ecme.example.com',
            'phone' => '01130000000',
            'civi_sync_enabled' => true,
            'civi_id' => 'test-id',
        ]);

        $civiClientMock = $this->createMock(ClientInterface::class);
        $civiClientMock->expects($this->once())
            ->method('create')
            ->with($organisation)
            ->willThrowException(new CiviException());

        $observer = new OrganisationObserver($civiClientMock);

        $observer->created($organisation);
    }
}
