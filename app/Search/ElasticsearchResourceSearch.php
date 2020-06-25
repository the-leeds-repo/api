<?php

namespace App\Search;

use App\Contracts\ResourceSearch;
use App\Http\Resources\ResourceResource;
use App\Models\Collection as CollectionModel;
use App\Models\Resource;
use App\Models\SearchHistory;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

class ElasticsearchResourceSearch implements ResourceSearch
{
    /**
     * @var array
     */
    protected $query;

    /**
     * ElasticsearchResourceSearch constructor.
     */
    public function __construct()
    {
        $this->query = [
            'from' => 0,
            'size' => config('tlr.pagination_results'),
            'query' => [
                'bool' => [
                    'filter' => [
                        'bool' => [
                            'must' => [
                                [
                                    'term' => [
                                        // TODO: Delete
                                        'status' => Service::STATUS_ACTIVE,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'must' => [
                        'bool' => [
                            'should' => [
                                //
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @inheritDoc
     */
    public function applyQuery(string $term): ResourceSearch
    {
        $should = &$this->query['query']['bool']['must']['bool']['should'];

        $should[] = $this->match('name', $term, 2);
        $should[] = $this->matchPhrase('description', $term, 1);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function applyCategory(string $category): ResourceSearch
    {
        $categoryModel = CollectionModel::query()
            ->with('taxonomies')
            ->categories()
            ->where('name', $category)
            ->firstOrFail();

        $should = &$this->query['query']['bool']['must']['bool']['should'];

        foreach ($categoryModel->taxonomies as $taxonomy) {
            $should[] = [
                'nested' => [
                    'path' => 'taxonomy_categories',
                    'query' => [
                        'bool' => [
                            'must' => [
                                [
                                    'term' => [
                                        'taxonomy_categories.id' => $taxonomy->id,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ];
        }

        $this->query['query']['bool']['filter']['bool']['must'][] = [
            'term' => [
                'collection_categories' => $category,
            ],
        ];

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function applyPersona(string $persona): ResourceSearch
    {
        $categoryModel = CollectionModel::query()
            ->with('taxonomies')
            ->personas()
            ->where('name', $persona)
            ->firstOrFail();

        $should = &$this->query['query']['bool']['must']['bool']['should'];

        foreach ($categoryModel->taxonomies as $taxonomy) {
            $should[] = [
                'nested' => [
                    'path' => 'taxonomy_categories',
                    'query' => [
                        'bool' => [
                            'must' => [
                                [
                                    'term' => [
                                        'taxonomy_categories.id' => $taxonomy->id,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ];
        }

        $this->query['query']['bool']['filter']['bool']['must'][] = [
            'term' => [
                'collection_personas' => $persona,
            ],
        ];

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function applyCategoryTaxonomyId(string $id): ResourceSearch
    {
        $this->query['query']['bool']['filter']['bool']['must'][] = [
            'nested' => [
                'path' => 'taxonomy_categories',
                'query' => [
                    'bool' => [
                        'must' => [
                            [
                                'term' => [
                                    'taxonomy_categories.id' => $id,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function applyCategoryTaxonomyName(string $name): ResourceSearch
    {
        $this->query['query']['bool']['filter']['bool']['must'][] = [
            'nested' => [
                'path' => 'taxonomy_categories',
                'query' => [
                    'bool' => [
                        'must' => [
                            [
                                'match' => [
                                    'taxonomy_categories.name' => $name,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getQuery(): array
    {
        return $this->query;
    }

    /**
     * @inheritDoc
     */
    public function paginate(
        int $page = null,
        int $perPage = null
    ): AnonymousResourceCollection {
        $page = page($page);
        $perPage = per_page($perPage);

        $this->query['from'] = ($page - 1) * $perPage;
        $this->query['size'] = $perPage;

        $response = Resource::searchRaw($this->query);
        $this->logMetrics($response);

        return $this->toResource($response, true, $page);
    }

    /**
     * @inheritDoc
     */
    public function get(int $perPage = null): AnonymousResourceCollection
    {
        $this->query['size'] = per_page($perPage);

        $response = Resource::searchRaw($this->query);
        $this->logMetrics($response);

        return $this->toResource($response, false);
    }

    /**
     * @param array $response
     * @param bool $paginate
     * @param int|null $page
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    protected function toResource(
        array $response,
        bool $paginate = true,
        int $page = null
    ): AnonymousResourceCollection {
        // Extract the hits from the array.
        $hits = $response['hits']['hits'];

        // Get all of the ID's for the resources from the hits.
        $resourceIds = collect($hits)->map->_id->toArray();

        // Implode the resource ID's so we can sort by them in database.
        $resourceIdsImploded = implode("','", $resourceIds);
        $resourceIdsImploded = "'$resourceIdsImploded'";

        // Create the query to get the resources, and keep ordering from Elasticsearch.
        $resources = Resource::query()
            ->whereIn('id', $resourceIds)
            ->orderByRaw("FIELD(id,$resourceIdsImploded)")
            ->get();

        // If paginated, then create a new pagination instance.
        if ($paginate) {
            $resources = new LengthAwarePaginator(
                $resources,
                $response['hits']['total'],
                config('tlr.pagination_results'),
                $page,
                ['path' => Paginator::resolveCurrentPath()]
            );
        }

        return ResourceResource::collection($resources);
    }

    /**
     * @param array $response
     * @return \App\Search\ElasticsearchResourceSearch
     */
    protected function logMetrics(array $response): ResourceSearch
    {
        SearchHistory::create([
            'query' => $this->query,
            'count' => $response['hits']['total'],
        ]);

        return $this;
    }

    /**
     * @param string $field
     * @param string $term
     * @param int $boost
     * @return array
     */
    protected function match(string $field, string $term, int $boost = 1): array
    {
        return [
            'match' => [
                $field => [
                    'query' => $term,
                    'boost' => $boost,
                ],
            ],
        ];
    }

    /**
     * @param string $field
     * @param string $term
     * @param int $boost
     * @return array
     */
    protected function matchPhrase(
        string $field,
        string $term,
        int $boost = 1
    ): array {
        return [
            'match_phrase' => [
                $field => [
                    'query' => $term,
                    'boost' => $boost,
                ],
            ],
        ];
    }
}
