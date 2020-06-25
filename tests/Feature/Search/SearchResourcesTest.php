<?php

namespace Tests\Feature\Search;

use App\Models\Collection;
use App\Models\Resource;
use App\Models\Taxonomy;
use Illuminate\Http\Response;
use Tests\TestCase;
use Tests\UsesElasticsearch;

/**
 * @group search
 */
class SearchResourcesTest extends TestCase implements UsesElasticsearch
{
    /**
     * Setup the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->truncateTaxonomies();
        $this->truncateCollectionCategories();
        $this->truncateCollectionPersonas();
    }

    /*
     * Perform a search for resources.
     */

    public function test_guest_can_search()
    {
        $response = $this->json('POST', '/core/v1/search/resources', [
            'query' => 'test',
        ]);

        $response->assertStatus(Response::HTTP_OK);
    }

    public function test_query_matches_resource_name()
    {
        $resource = factory(Resource::class)->create();

        $response = $this->json('POST', '/core/v1/search/resources', [
            'query' => $resource->name,
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'id' => $resource->id,
        ]);
    }

    public function test_query_matches_resource_description()
    {
        $resource = factory(Resource::class)->create();

        $response = $this->json('POST', '/core/v1/search/resources', [
            'query' => $resource->description,
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'id' => $resource->id,
        ]);
    }

    public function test_query_matches_single_word_from_resource_description()
    {
        $resource = factory(Resource::class)->create([
            'description' => 'This is a resource that helps to homeless find temporary housing.',
        ]);

        $response = $this->json('POST', '/core/v1/search/resources', [
            'query' => 'homeless',
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['id' => $resource->id]);
    }

    public function test_query_matches_multiple_words_from_resource_description()
    {
        $resource = factory(Resource::class)->create([
            'description' => 'This is a resource that helps to homeless find temporary housing.',
        ]);

        $response = $this->json('POST', '/core/v1/search/resources', [
            'query' => 'temporary housing',
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['id' => $resource->id]);
    }

    public function test_filter_by_category_works()
    {
        $resource = factory(Resource::class)->create();
        $collection = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'name' => 'Self Help',
            'meta' => [],
            'order' => 1,
        ]);
        $taxonomy = Taxonomy::category()->children()->create(['name' => 'PHPUnit Taxonomy', 'order' => 1]);
        $collection->collectionTaxonomies()->create(['taxonomy_id' => $taxonomy->id]);
        $resource->resourceTaxonomies()->create(['taxonomy_id' => $taxonomy->id]);
        $resource->save();

        $response = $this->json('POST', '/core/v1/search/resources', [
            'category' => $collection->name,
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['id' => $resource->id]);
    }

    public function test_filter_by_persona_works()
    {
        $resource = factory(Resource::class)->create();
        $collection = Collection::create([
            'type' => Collection::TYPE_PERSONA,
            'name' => 'Refugees',
            'meta' => [],
            'order' => 1,
        ]);
        $taxonomy = Taxonomy::category()->children()->create(['name' => 'PHPUnit Taxonomy', 'order' => 1]);
        $collection->collectionTaxonomies()->create(['taxonomy_id' => $taxonomy->id]);
        $resource->resourceTaxonomies()->create(['taxonomy_id' => $taxonomy->id]);
        $resource->save();

        $response = $this->json('POST', '/core/v1/search/resources', [
            'persona' => $collection->name,
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['id' => $resource->id]);
    }

    public function test_filter_by_category_taxonomy_id_works()
    {
        $resource = factory(Resource::class)->create();
        $taxonomy = Taxonomy::category()->children()->create(['name' => 'PHPUnit Taxonomy', 'order' => 1]);
        $resource->resourceTaxonomies()->create(['taxonomy_id' => $taxonomy->id]);
        $resource->save();

        $response = $this->json('POST', '/core/v1/search/resources', [
            'category_taxonomy' => [
                'id' => $taxonomy->id,
            ],
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['id' => $resource->id]);
    }

    public function test_filter_by_category_taxonomy_name_works()
    {
        $resource = factory(Resource::class)->create();
        $taxonomy = Taxonomy::category()->children()->create(['name' => 'PHPUnit Taxonomy', 'order' => 1]);
        $resource->resourceTaxonomies()->create(['taxonomy_id' => $taxonomy->id]);
        $resource->save();

        $response = $this->json('POST', '/core/v1/search/resources', [
            'category_taxonomy' => [
                'name' => 'PHPUnit Taxonomy',
            ],
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['id' => $resource->id]);
    }

    public function test_query_and_filter_works()
    {
        $resource = factory(Resource::class)->create(['name' => 'Ayup Digital']);
        $collection = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'name' => 'Self Help',
            'meta' => [],
            'order' => 1,
        ]);
        $taxonomy = Taxonomy::category()->children()->create(['name' => 'Collection', 'order' => 1]);
        $collectionTaxonomy = $collection->collectionTaxonomies()->create(['taxonomy_id' => $taxonomy->id]);
        $resource->resourceTaxonomies()->create(['taxonomy_id' => $taxonomy->id]);
        $resource->save();

        $differentResource = factory(Resource::class)->create(['name' => 'Ayup Digital']);
        $differentCollection = Collection::create([
            'type' => Collection::TYPE_PERSONA,
            'name' => 'Refugees',
            'meta' => [],
            'order' => 1,
        ]);
        $differentTaxonomy = Taxonomy::category()->children()->create(['name' => 'Persona', 'order' => 2]);
        $differentCollection->collectionTaxonomies()->create(['taxonomy_id' => $differentTaxonomy->id]);
        $differentResource->resourceTaxonomies()->create(['taxonomy_id' => $differentTaxonomy->id]);
        $differentResource->save();

        $response = $this->json('POST', '/core/v1/search/resources', [
            'query' => 'Ayup Digital',
            'category' => $collectionTaxonomy->collection->name,
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['id' => $resource->id]);
        $response->assertJsonMissing(['id' => $differentResource->id]);
    }

    public function test_query_and_filter_works_when_query_does_not_match()
    {
        $resource = factory(Resource::class)->create(['name' => 'Ayup Digital']);
        $collection = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'name' => 'Self Help',
            'meta' => [],
            'order' => 1,
        ]);
        $taxonomy = Taxonomy::category()->children()->create(['name' => 'Collection', 'order' => 1]);
        $collectionTaxonomy = $collection->collectionTaxonomies()->create(['taxonomy_id' => $taxonomy->id]);
        $resource->resourceTaxonomies()->create(['taxonomy_id' => $taxonomy->id]);
        $resource->save();

        $differentResource = factory(Resource::class)->create(['name' => 'Ayup Digital']);
        $differentCollection = Collection::create([
            'type' => Collection::TYPE_PERSONA,
            'name' => 'Refugees',
            'meta' => [],
            'order' => 1,
        ]);
        $differentTaxonomy = Taxonomy::category()->children()->create(['name' => 'Persona', 'order' => 2]);
        $differentCollection->collectionTaxonomies()->create(['taxonomy_id' => $differentTaxonomy->id]);
        $differentResource->resourceTaxonomies()->create(['taxonomy_id' => $differentTaxonomy->id]);
        $differentResource->save();

        $response = $this->json('POST', '/core/v1/search/resources', [
            'query' => 'asfkjbadsflksbdafklhasdbflkbs',
            'category' => $collectionTaxonomy->collection->name,
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['id' => $resource->id]);
        $response->assertJsonMissing(['id' => $differentResource->id]);
    }

    public function test_resources_with_more_taxonomies_in_a_category_collection_are_more_relevant()
    {
        // Create 3 taxonomies
        $taxonomy1 = Taxonomy::category()->children()->create(['name' => 'Red', 'order' => 1]);
        $taxonomy2 = Taxonomy::category()->children()->create(['name' => 'Blue', 'order' => 2]);
        $taxonomy3 = Taxonomy::category()->children()->create(['name' => 'Green', 'order' => 3]);

        // Create a collection
        $collection = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'name' => 'Self Help',
            'meta' => [],
            'order' => 1,
        ]);

        // Link the taxonomies to the collection
        $collection->collectionTaxonomies()->create(['taxonomy_id' => $taxonomy1->id]);
        $collection->collectionTaxonomies()->create(['taxonomy_id' => $taxonomy2->id]);
        $collection->collectionTaxonomies()->create(['taxonomy_id' => $taxonomy3->id]);

        // Create 3 resources
        $resource1 = factory(Resource::class)->create(['name' => 'Gold Co.']);
        $resource2 = factory(Resource::class)->create(['name' => 'Silver Co.']);
        $resource3 = factory(Resource::class)->create(['name' => 'Bronze Co.']);

        // Link the resources to 1, 2 and 3 taxonomies respectively.
        $resource1->resourceTaxonomies()->create(['taxonomy_id' => $taxonomy1->id]);
        $resource1->resourceTaxonomies()->create(['taxonomy_id' => $taxonomy2->id]);
        $resource1->resourceTaxonomies()->create(['taxonomy_id' => $taxonomy3->id]);
        $resource1->save(); // Update the Elasticsearch index.

        $resource2->resourceTaxonomies()->create(['taxonomy_id' => $taxonomy1->id]);
        $resource2->resourceTaxonomies()->create(['taxonomy_id' => $taxonomy2->id]);
        $resource2->save(); // Update the Elasticsearch index.

        $resource3->resourceTaxonomies()->create(['taxonomy_id' => $taxonomy1->id]);
        $resource3->save(); // Update the Elasticsearch index.

        // Assert that when searching by collection, the resources with more taxonomies are ranked higher.
        $response = $this->json('POST', '/core/v1/search/resources', [
            'category' => $collection->name,
        ]);

        $response->assertStatus(Response::HTTP_OK);

        $content = $this->getResponseContent($response)['data'];
        $this->assertEquals($resource1->id, $content[0]['id']);
        $this->assertEquals($resource2->id, $content[1]['id']);
        $this->assertEquals($resource3->id, $content[2]['id']);
    }

    public function test_resources_with_more_taxonomies_in_a_persona_collection_are_more_relevant()
    {
        // Create 3 taxonomies
        $taxonomy1 = Taxonomy::category()->children()->create(['name' => 'Red', 'order' => 1]);
        $taxonomy2 = Taxonomy::category()->children()->create(['name' => 'Blue', 'order' => 2]);
        $taxonomy3 = Taxonomy::category()->children()->create(['name' => 'Green', 'order' => 3]);

        // Create a collection
        $collection = Collection::create([
            'type' => Collection::TYPE_PERSONA,
            'name' => 'Self Help',
            'meta' => [],
            'order' => 1,
        ]);

        // Link the taxonomies to the collection
        $collection->collectionTaxonomies()->create(['taxonomy_id' => $taxonomy1->id]);
        $collection->collectionTaxonomies()->create(['taxonomy_id' => $taxonomy2->id]);
        $collection->collectionTaxonomies()->create(['taxonomy_id' => $taxonomy3->id]);

        // Create 3 resources
        $resource1 = factory(Resource::class)->create(['name' => 'Gold Co.']);
        $resource2 = factory(Resource::class)->create(['name' => 'Silver Co.']);
        $resource3 = factory(Resource::class)->create(['name' => 'Bronze Co.']);

        // Link the resources to 1, 2 and 3 taxonomies respectively.
        $resource1->resourceTaxonomies()->create(['taxonomy_id' => $taxonomy1->id]);
        $resource1->resourceTaxonomies()->create(['taxonomy_id' => $taxonomy2->id]);
        $resource1->resourceTaxonomies()->create(['taxonomy_id' => $taxonomy3->id]);
        $resource1->save(); // Update the Elasticsearch index.

        $resource2->resourceTaxonomies()->create(['taxonomy_id' => $taxonomy1->id]);
        $resource2->resourceTaxonomies()->create(['taxonomy_id' => $taxonomy2->id]);
        $resource2->save(); // Update the Elasticsearch index.

        $resource3->resourceTaxonomies()->create(['taxonomy_id' => $taxonomy1->id]);
        $resource3->save(); // Update the Elasticsearch index.

        // Assert that when searching by collection, the resources with more taxonomies are ranked higher.
        $response = $this->json('POST', '/core/v1/search/resources', [
            'persona' => $collection->name,
        ]);

        $response->assertStatus(Response::HTTP_OK);

        $content = $this->getResponseContent($response)['data'];
        $this->assertEquals($resource1->id, $content[0]['id']);
        $this->assertEquals($resource2->id, $content[1]['id']);
        $this->assertEquals($resource3->id, $content[2]['id']);
    }
}
