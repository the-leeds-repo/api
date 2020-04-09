<?php

namespace Tests;

use App\Models\Collection;
use App\Models\Organisation;
use App\Models\Role;
use App\Models\Service;
use App\Models\Taxonomy;
use App\Models\User;
use App\Models\UserRole;
use App\RoleManagement\RoleManagerInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\TestResponse;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    use RefreshDatabase;
    use WithFaker;

    /**
     * @var bool
     */
    protected static $testLogCleared = false;

    /**
     * @var bool
     */
    protected static $elasticsearchInitialised = false;

    /**
     * @var \Carbon\CarbonImmutable
     */
    protected $now;

    /**
     * Setup the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Clear the cache.
        $this->artisan('cache:clear');

        // Disable the API throttle middleware.
        $this->withoutMiddleware('throttle');

        $this->clearLog();
        $this->setUpElasticsearch();

        $this->now = Date::now();
    }

    /**
     * Clean up the testing environment before the next test.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        Storage::cloud()->deleteDirectory('files');

        $this->tearDownElasticsearch();

        parent::tearDown();
    }

    /**
     * Setup up the Faker instance.
     *
     * @return void
     */
    protected function setUpFaker()
    {
        $this->faker = $this->makeFaker(config('app.faker_locale'));
    }

    /**
     * Delete all the collection categories and pivot records.
     */
    protected function truncateCollectionCategories()
    {
        Collection::categories()->get()->each(function (Collection $collection) {
            $collection->collectionTaxonomies()->delete();
        });
        Collection::categories()->delete();
    }

    /**
     * Delete all the collection personas and pivot records.
     */
    protected function truncateCollectionPersonas()
    {
        Collection::personas()->get()->each(function (Collection $collection) {
            $collection->collectionTaxonomies()->delete();
        });
        Collection::personas()->delete();
    }

    /**
     * Delete all the SNOMED collections and pivot records.
     */
    protected function truncateCollectionSnomed()
    {
        Collection::snomed()->get()->each(function (Collection $collection) {
            $collection->collectionTaxonomies()->delete();
        });
        Collection::snomed()->delete();
    }

    /**
     * Delete all the category taxonomy records.
     */
    protected function truncateTaxonomies()
    {
        Taxonomy::category()->children->each->delete();
    }

    /**
     * Clears the testing log file.
     */
    protected function clearLog()
    {
        if (!static::$testLogCleared) {
            file_put_contents(config('logging.channels.testing.path'), '');
            static::$testLogCleared = true;
        }
    }

    /**
     * Sets up the Elasticsearch indices.
     */
    protected function setUpElasticsearch()
    {
        if (!$this instanceof UsesElasticsearch) {
            Service::disableSearchSyncing();
            return;
        } else {
            Service::enableSearchSyncing();
        }

        if (!static::$elasticsearchInitialised) {
            $this->artisan('tlr:reindex-elasticsearch');
            static::$elasticsearchInitialised = true;
        }
    }

    /**
     * Tears down the Elasticsearch indices.
     */
    protected function tearDownElasticsearch()
    {
        if (!$this instanceof UsesElasticsearch) {
            Service::disableSearchSyncing();
            return;
        } else {
            Service::enableSearchSyncing();
        }

        try {
            $this->artisan('scout:flush', ['model' => Service::class]);
        } catch (\Exception $exception) {
            // Do nothing.
        }
    }

    /**
     * @param \Illuminate\Foundation\Testing\TestResponse $response
     */
    protected function dumpResponse(TestResponse $response)
    {
        dump(json_decode($response->getContent(), true));
    }

    /**
     * @param \Illuminate\Foundation\Testing\TestResponse $response
     * @param string|null $key
     * @return array|string
     */
    protected function getResponseContent(TestResponse $response, string $key = null)
    {
        $content = json_decode($response->getContent(), true);

        if ($key !== null) {
            return Arr::get($content, $key);
        }

        return $content;
    }

    /**
     * Fakes events except for models.
     */
    protected function fakeEvents()
    {
        $initialDispatcher = Event::getFacadeRoot();
        Event::fake();
        Model::setEventDispatcher($initialDispatcher);
    }

    /**
     * @param \App\Models\User $user
     * @param \App\Models\Service $service
     * @return \App\Models\User
     */
    public function makeServiceWorker(User $user, Service $service): User
    {
        /** @var \App\RoleManagement\RoleManagerInterface $roleManager */
        $roleManager = app()->make(RoleManagerInterface::class, [
            'user' => $user,
        ]);

        $roleManager->updateRoles(array_merge($user->userRoles->all(), [
            new UserRole([
                'role_id' => Role::serviceWorker()->id,
                'service_id' => $service->id,
            ]),
        ]));

        return $user;
    }

    /**
     * @param \App\Models\User $user
     * @param \App\Models\Service $service
     * @return \App\Models\User
     */
    public function makeServiceAdmin(User $user, Service $service): User
    {
        /** @var \App\RoleManagement\RoleManagerInterface $roleManager */
        $roleManager = app()->make(RoleManagerInterface::class, [
            'user' => $user,
        ]);

        $roleManager->updateRoles(array_merge($user->userRoles->all(), [
            new UserRole([
                'role_id' => Role::serviceAdmin()->id,
                'service_id' => $service->id,
            ]),
        ]));

        return $user;
    }

    /**
     * @param \App\Models\User $user
     * @param \Tests\Organisation $organisation
     * @return \App\Models\User
     */
    public function makeOrganisationAdmin(User $user, Organisation $organisation): User
    {
        /** @var \App\RoleManagement\RoleManagerInterface $roleManager */
        $roleManager = app()->make(RoleManagerInterface::class, [
            'user' => $user,
        ]);

        $roleManager->updateRoles(array_merge($user->userRoles->all(), [
            new UserRole([
                'role_id' => Role::organisationAdmin()->id,
                'organisation_id' => $organisation->id,
            ]),
        ]));

        return $user;
    }

    /**
     * @param \App\Models\User $user
     * @return \App\Models\User
     */
    public function makeGlobalAdmin(User $user): User
    {
        /** @var \App\RoleManagement\RoleManagerInterface $roleManager */
        $roleManager = app()->make(RoleManagerInterface::class, [
            'user' => $user,
        ]);

        $roleManager->updateRoles([
            new UserRole([
                'role_id' => Role::globalAdmin()->id,
            ]),
        ]);

        return $user;
    }

    /**
     * @param \App\Models\User $user
     * @return \App\Models\User
     */
    public function makeSuperAdmin(User $user): User
    {
        /** @var \App\RoleManagement\RoleManagerInterface $roleManager */
        $roleManager = app()->make(RoleManagerInterface::class, [
            'user' => $user,
        ]);

        $roleManager->updateRoles([
            new UserRole([
                'role_id' => Role::superAdmin()->id,
            ]),
        ]);

        return $user;
    }
}
