<?php

namespace App\Search;

use App\Contracts\ResourceSearch;
use App\Models\Collection as CollectionModel;

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
