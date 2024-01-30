<?php declare(strict_types=1);

namespace Elastic\ScoutDriverPlus\Builders;

use Closure;
use Elastic\Adapter\Search\SearchResult;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class DatabaseQueryBuilder
{
    private Model $model;
    private ?array $relations;
    private ?Closure $callback;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    public function with(array $relations): self
    {
        $this->relations = $relations;
        return $this;
    }

    /**
     * @param Closure(Builder, SearchResult|null): void $callback
     * @return $this
     */
    public function callback(Closure $callback): self
    {
        $this->callback = $callback;
        return $this;
    }

    public function buildQuery(array $ids, ?SearchResult $baseSearchResult = null): Builder
    {
        $query = in_array(SoftDeletes::class, class_uses_recursive($this->model), true)
            ? $this->model->withTrashed()
            : $this->model->newQuery();

        if (isset($this->relations)) {
            $query->with($this->relations);
        }

        $query->whereIn($this->model->getScoutKeyName(), $ids);

        if (isset($this->callback)) {
            call_user_func($this->callback, $query, $baseSearchResult);
        }

        return $query;
    }
}
