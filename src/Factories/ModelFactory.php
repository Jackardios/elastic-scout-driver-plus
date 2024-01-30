<?php declare(strict_types=1);

namespace Elastic\ScoutDriverPlus\Factories;

use Elastic\Adapter\Indices\IndexManager;
use Elastic\Adapter\Search\SearchResult;
use Elastic\ScoutDriverPlus\Builders\DatabaseQueryBuilder;
use Illuminate\Database\Eloquent\Collection;

class ModelFactory
{
    /**
     * @var array<string, DatabaseQueryBuilder> $databaseQueryBuilders
     */
    private array $databaseQueryBuilders;

    /**
     * @var array<string, callable(Collection): Collection> $eloquentCollectionCallbacks
     */
    private array $eloquentCollectionCallbacks = [];

    private ?SearchResult $baseSearchResult = null;

    /**
     * @param array<string, DatabaseQueryBuilder> $databaseQueryBuilders ['my_index' => new DatabaseQueryBuilder($model), ...]
     */
    public function __construct(array $databaseQueryBuilders)
    {
        $this->databaseQueryBuilders = $databaseQueryBuilders;
    }

    public function withSearchResult(SearchResult $baseSearchResult): self
    {
        $this->baseSearchResult = $baseSearchResult;

        return $this;
    }

    /**
     * @param array<string, callable(Collection): void> $eloquentCollectionCallbacks
     * @return $this
     */
    public function withEloquentCollectionCallbacks(array $eloquentCollectionCallbacks): self
    {
        $this->eloquentCollectionCallbacks = $eloquentCollectionCallbacks;

        return $this;
    }

    public function makeFromIndexNameAndDocumentIds(string $indexName, array $documentIds): Collection
    {
        $databaseQueryBuilder = $this->resolveDatabaseQueryBuilder($indexName);

        if (empty($documentIds) || is_null($databaseQueryBuilder)) {
            return new Collection();
        }

        $collection = $databaseQueryBuilder->buildQuery($documentIds, $this->baseSearchResult)->get();
        $collectionCallback = $this->eloquentCollectionCallbacks[$indexName] ?? null;

        return $collectionCallback ? call_user_func($collectionCallback, $collection) : $collection;
    }


    private function resolveDatabaseQueryBuilder(string $indexName): ?DatabaseQueryBuilder
    {
        if (isset($this->databaseQueryBuilders[$indexName])) {
            return $this->databaseQueryBuilders[$indexName];
        }

        /** @var IndexManager $indexManager */
        $indexManager = app(IndexManager::class);

        foreach ($indexManager->getAliases($indexName) as $aliasName) {
            /** @var string $aliasName */
            if (isset($this->databaseQueryBuilders[$aliasName])) {
                return $this->databaseQueryBuilders[$aliasName];
            }
        }

        return null;
    }
}
