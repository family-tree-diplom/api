<?php

namespace OpenCCK\Infrastructure\Model\DB;

use OpenCCK\Infrastructure\API\App;
use OpenCCK\Infrastructure\Model\DB;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Query\QueryBuilder;

use Drift\DBAL\Connection;
use Drift\DBAL\Result;

use React\Promise\PromiseInterface;

use Throwable;
use function React\Async\async;
use function React\Async\await;

class Query {
    /**
     * @var Connection
     */
    private Connection $connection;

    /**
     * @var QueryBuilder
     */
    private QueryBuilder $query;

    /**
     * @var AbstractPlatform
     */
    private AbstractPlatform $platform;

    /**
     * @var ?string
     */
    private ?string $exception = null;

    /**
     * @param ?DB $db
     * @throws Exception
     */
    public function __construct(DB $db = null) {
        $db = $db ?? DB::getInstance();
        $this->connection = $db->getConnection();
        $this->platform = $db->getPlatform();

        $this->query = $this->connection->createQueryBuilder();
    }

    /**
     * @param array|string|null $select
     * @return $this
     */
    public function select(array|string $select = null): Query {
        if (is_array($select)) {
            if (count($select) > 0) {
                $items = [];
                foreach ($select as $alias => $fields) {
                    if (is_string($fields)) {
                        $items[] = $fields;
                    } else {
                        foreach ($fields as $field_name) {
                            $items[] =
                                $this->platform->quoteIdentifier($alias) .
                                '.' .
                                $this->platform->quoteIdentifier($field_name) .
                                ' AS ' .
                                "`$alias.$field_name`";
                        }
                    }
                    $this->query->addSelect(implode(', ', $items));
                }
            }
        } else {
            $this->query->addSelect($select);
        }
        return $this;
    }

    /**
     * @param string $from
     * @param null $alias
     * @return $this
     */
    public function from(string $from, $alias = null): Query {
        $this->query->from($from, is_null($alias) ? null : $this->platform->quoteIdentifier($alias));
        return $this;
    }

    /**
     * @param string|array $fromAlias
     * @param string $join
     * @param string $alias
     * @param ?string $condition
     * @return $this
     */
    public function join(
        array|string $fromAlias,
        string $join = '',
        string $alias = '',
        string $condition = null
    ): Query {
        if (is_array($fromAlias)) {
            foreach ($fromAlias as $args) {
                $this->query->join($args[0], $args[1], $args[2], $args[3]);
            }
        } else {
            $this->query->join($fromAlias, $join, $alias, $condition);
        }
        return $this;
    }

    /**
     * @param string|array $fromAlias
     * @param string $join
     * @param string $alias
     * @param ?string $condition
     * @return $this
     */
    public function leftJoin(
        array|string $fromAlias,
        string $join = '',
        string $alias = '',
        string $condition = null
    ): Query {
        if (is_array($fromAlias)) {
            foreach ($fromAlias as $args) {
                $this->query->leftJoin(
                    $this->platform->quoteIdentifier($args[0]),
                    $args[1],
                    $this->platform->quoteIdentifier($args[2]),
                    $args[3]
                );
            }
        } else {
            $this->query->leftJoin(
                $this->platform->quoteIdentifier($fromAlias),
                $join,
                $this->platform->quoteIdentifier($alias),
                $condition
            );
        }
        return $this;
    }

    /**
     * @param string|array $where
     * @return $this
     */
    public function where(array|string $where): Query {
        if (is_array($where)) {
            foreach ($where as $key => $value) {
                if (is_array($value)) {
                    $array = [];
                    foreach ($value as $v) {
                        $array[] = $this->platform->quoteStringLiteral($v);
                    }
                    $this->query->andWhere(
                        $this->platform->quoteIdentifier($key) . ' IN (' . implode(',', $array) . ')'
                    );
                } else {
                    $this->query->andWhere(
                        $this->platform->quoteIdentifier($key) . ' = ' . $this->platform->quoteStringLiteral($value)
                    );
                }
            }
        } else {
            $this->query->andWhere($where);
        }
        return $this;
    }

    /**
     * @param array $order
     * @return $this
     */
    public function order(array $order): Query {
        if (count($order)) {
            $splice = array_splice($order, 0, 1);
            foreach ($splice as $key => $value) {
                $this->query->orderBy(addslashes($key), addslashes($value ?: 'ASC'));
            }
        }
        foreach ($order as $key => $value) {
            $this->query->addOrderBy(addslashes($key), addslashes($value ?: 'ASC'));
        }
        return $this;
    }

    /**
     * @param int $limit
     * @param int $offset
     * @return $this
     */
    public function limit(int $limit, int $offset = 0): Query {
        $this->query->setFirstResult($offset)->setMaxResults($limit);
        return $this;
    }

    /**
     * @param string|array $group
     * @return $this
     */
    public function group(array|string $group): Query {
        if (is_array($group)) {
            foreach ($group as $value) {
                $this->query->addGroupBy($value);
            }
        } else {
            $this->query->addGroupBy($group);
        }

        return $this;
    }

    /**
     * @param string|array $having
     * @return $this
     */
    public function having(array|string $having): Query {
        if (is_array($having)) {
            foreach ($having as $value) {
                $this->query->andHaving($value);
            }
        } else {
            $this->query->andHaving($having);
        }

        return $this;
    }

    /**
     * @param string $table
     * @return $this
     */
    public function insert(string $table): Query {
        $this->query->insert($table);
        return $this;
    }

    /**
     * @param array<string,string> $values
     * @return $this
     */
    public function values(array $values): Query {
        $escape = [];
        foreach ($values as $key => $value) {
            $escape[$this->platform->quoteIdentifier($key)] = !is_null($value)
                ? $this->platform->quoteStringLiteral($value)
                : 'NULL';
        }
        $this->query->values($escape);
        return $this;
    }

    /**
     * @param string $table
     * @param ?string $alias
     * @return $this
     */
    public function update(string $table, string $alias = null): Query {
        $this->query->update($table, $alias);
        return $this;
    }

    /**
     * @param array<string,string> $data
     * @return $this
     */
    public function set(array $data = []): Query {
        foreach ($data as $key => $val) {
            $this->query->set(
                $this->platform->quoteIdentifier($key),
                !is_null($val) ? $this->platform->quoteStringLiteral($val) : 'NULL'
            );
        }
        return $this;
    }

    /**
     * @param string $table
     * @return $this
     */
    public function delete(string $table): Query {
        $this->query->delete($table);
        return $this;
    }

    /**
     * @return PromiseInterface<Result>
     */
    public function asyncExecute(): PromiseInterface {
        App::getLogger()->notice($this->query->getSQL());
        return $this->connection->query($this->query);
    }

    /**
     * @return Result
     * @throws Throwable
     */
    public function execute(): Result {
        $result = await(
            $this->asyncExecute()->then(
                function (Result $result) {
                    $this->exception = null;
                    return $result;
                },
                function (Throwable $exception) {
                    App::getLogger()->error($exception->getMessage());
                    $this->exception = $exception->getMessage();
                }
            )
        );
        if (!is_null($this->exception)) {
            throw new \Exception($this->exception);
        }
        return $result;
    }

    /**
     * @return ArrayCollection
     * @psalm-return ArrayCollection<int, mixed>
     * @throws Throwable
     */
    public function fetch(): ArrayCollection {
        return new ArrayCollection($this->execute()->fetchAllRows());
    }

    /**
     * @return PromiseInterface
     * @psalm-return PromiseInterface<ArrayCollection<int, mixed>>
     * @throws Throwable
     */
    public function asyncFetch(): PromiseInterface {
        return $this->asyncExecute()->then(
            function (Result $result) {
                $this->exception = null;
                return new ArrayCollection($result->fetchAllRows());
            },
            function (Throwable $exception) {
                App::getLogger()->error($exception->getMessage());
                $this->exception = $exception->getMessage();
            }
        );
    }

    /**
     * @return QueryBuilder
     */
    public function getQuery(): QueryBuilder {
        return $this->query;
    }

    /**
     * @return ?string
     */
    public function getException(): ?string {
        return $this->exception;
    }
}
