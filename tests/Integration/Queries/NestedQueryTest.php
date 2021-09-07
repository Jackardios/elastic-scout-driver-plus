<?php declare(strict_types=1);

namespace ElasticScoutDriverPlus\Tests\Integration\Queries;

use ElasticScoutDriverPlus\Builders\NestedQueryBuilder;
use ElasticScoutDriverPlus\Builders\TermQueryBuilder;
use ElasticScoutDriverPlus\Support\Query;
use ElasticScoutDriverPlus\Tests\App\Author;
use ElasticScoutDriverPlus\Tests\App\Book;
use ElasticScoutDriverPlus\Tests\Integration\TestCase;

/**
 * @covers \ElasticScoutDriverPlus\Builders\AbstractParameterizedQueryBuilder
 * @covers \ElasticScoutDriverPlus\Builders\NestedQueryBuilder
 * @covers \ElasticScoutDriverPlus\Engine
 * @covers \ElasticScoutDriverPlus\Factories\LazyModelFactory
 * @covers \ElasticScoutDriverPlus\Support\Query
 *
 * @uses   \ElasticScoutDriverPlus\Builders\MatchQueryBuilder
 * @uses   \ElasticScoutDriverPlus\Builders\SearchRequestBuilder
 * @uses   \ElasticScoutDriverPlus\Builders\TermQueryBuilder
 * @uses   \ElasticScoutDriverPlus\Decorators\Hit
 * @uses   \ElasticScoutDriverPlus\Decorators\SearchResult
 * @uses   \ElasticScoutDriverPlus\Factories\DocumentFactory
 * @uses   \ElasticScoutDriverPlus\Factories\RoutingFactory
 * @uses   \ElasticScoutDriverPlus\QueryParameters\Collection
 * @uses   \ElasticScoutDriverPlus\QueryParameters\Shared\FieldParameter
 * @uses   \ElasticScoutDriverPlus\QueryParameters\Shared\QueryStringParameter
 * @uses   \ElasticScoutDriverPlus\QueryParameters\Shared\ValueParameter
 * @uses   \ElasticScoutDriverPlus\QueryParameters\Transformers\FlatArrayTransformer
 * @uses   \ElasticScoutDriverPlus\QueryParameters\Transformers\GroupedArrayTransformer
 * @uses   \ElasticScoutDriverPlus\QueryParameters\Validators\AllOfValidator
 * @uses   \ElasticScoutDriverPlus\Searchable
 * @uses   \ElasticScoutDriverPlus\Support\ModelScope
 * @uses   \ElasticScoutDriverPlus\query
 */
final class NestedQueryTest extends TestCase
{
    public function test_models_can_be_found_using_path_and_query(): void
    {
        // additional mixin
        factory(Book::class, rand(2, 10))->create([
            'author_id' => factory(Author::class)->create([
                'name' => 'John',
            ]),
        ]);

        $target = factory(Book::class)->create([
            'author_id' => factory(Author::class)->create([
                'name' => 'Steven',
            ]),
        ]);

        $query = Query::nested()
            ->path('author')
            ->query(
                Query::match()
                    ->field('author.name')
                    ->query('Steven')
            );

        $found = Book::searchQuery($query)->execute();

        $this->assertFoundModel($target, $found);
    }

    public function test_models_can_be_found_using_query_builder(): void
    {
        // additional mixin
        factory(Book::class)->create([
            'author_id' => factory(Author::class)->create([
                'phone_number' => '202-555-0165',
            ]),
        ]);

        $target = factory(Book::class)->create([
            'author_id' => factory(Author::class)->create([
                'phone_number' => '202-555-0139',
            ]),
        ]);

        $builder = (new NestedQueryBuilder())
            ->path('author')
            ->query(
                (new TermQueryBuilder())
                    ->field('author.phone_number')
                    ->value('202-555-0139')
            );

        $found = Book::searchQuery($builder)->execute();

        $this->assertFoundModel($target, $found);
    }
}
