<?php

namespace App\Models;

use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\Database\ResultInterface;
use Config\Database;
use Throwable;

/**
 * Base model: lazy database connection and failure-safe reads
 * (the CodeIgniter 3 MY_Model).
 *
 * A model connects on first use. Methods return FALSE when the database is
 * unavailable or a query fails (the error is logged, never shown), and NULL
 * when no matching row exists.
 *
 * Not based on CodeIgniter\Model: that class is bound to one table and
 * defines find()/delete() with other meanings than the helpers below, which
 * the sitepanel modules use on many tables.
 */
class BaseModel
{
    /**
     * Total rows matched by the last paged list query (paged_rows).
     */
    public int $total_rec_found = 0;

    protected ?BaseConnection $db = null;

    private ?bool $connected = null;

    /**
     * Connect on first use and remember whether it worked.
     */
    protected function db_ready(): bool
    {
        if ($this->connected === null) {
            try {
                $this->db = Database::connect();
                $this->db->initialize();
                $this->connected = (bool) $this->db->connID;
            } catch (Throwable $e) {
                $this->connected = false;
            }

            if (! $this->connected) {
                log_message('error', $this->name() . ': database connection failed.');
            }
        }

        return $this->connected;
    }

    /**
     * Class name without namespace, for log lines.
     */
    protected function name(): string
    {
        return substr(strrchr('\\' . static::class, '\\'), 1);
    }

    /**
     * Run a write (insert, update, delete). TRUE when it failed (and was
     * logged) — including the exception a failed query throws while DBDebug
     * is on — so callers can undo related work, e.g. remove a just-uploaded
     * file, instead of the request ending in an error page.
     *
     * @param callable(): bool $write
     */
    protected function write_failed(callable $write): bool
    {
        try {
            return $this->query_failed($write() ? true : false);
        } catch (DatabaseException $e) {
            log_message('error', $this->name() . ' query failed: ' . $e->getMessage());

            return true;
        }
    }

    /**
     * @param bool|ResultInterface $query
     *
     * @return bool TRUE when the query failed (and was logged)
     */
    protected function query_failed($query): bool
    {
        if ($query !== false) {
            return false;
        }

        $error = $this->db->error();
        log_message('error', $this->name() . ' query failed: ' . $error['code'] . ' ' . $error['message']);

        return true;
    }

    /**
     * @param bool|ResultInterface $query
     *
     * @return array|false|null
     */
    protected function single_row($query)
    {
        if ($this->query_failed($query)) {
            return false;
        }

        $row = $query->getRowArray();

        return $row ?: null;
    }

    /**
     * @param bool|ResultInterface $query
     *
     * @return array|false
     */
    protected function all_rows($query)
    {
        return $this->query_failed($query) ? false : $query->getResultArray();
    }

    /**
     * Query builder for a table (after db_ready()).
     */
    protected function table(string $table): BaseBuilder
    {
        return $this->db->table($table);
    }

    /*
     * Write / lookup helpers named as in the reference project's MY_Model
     * (safe_insert, safe_update, safe_delete, find, findCount). Conditions
     * are column => value arrays bound by the query builder, never SQL strings.
     */

    /**
     * @return false|int New id
     */
    public function safe_insert(string $table, array $data)
    {
        if (! $this->db_ready() || $this->write_failed(fn () => (bool) $this->table($table)->insert($data))) {
            return false;
        }

        return (int) $this->db->insertID();
    }

    /**
     * @param array $where column => value
     */
    public function safe_update(string $table, array $data, array $where): bool
    {
        return $where && $this->db_ready()
            && ! $this->write_failed(fn () => (bool) $this->table($table)->where($where)->update($data));
    }

    /**
     * @param array $where column => value
     */
    public function safe_delete(string $table, array $where): bool
    {
        return $where && $this->db_ready()
            && ! $this->write_failed(fn () => $this->table($table)->where($where)->delete() !== false);
    }

    /**
     * @param array $where column => value
     *
     * @return array|false|null One row
     */
    public function find(string $table, array $where)
    {
        if (! $this->db_ready()) {
            return false;
        }

        return $this->single_row($this->table($table)->where($where)->limit(1)->get());
    }

    /**
     * @param array $where column => value (keys may carry an operator, e.g. 'id <>')
     *
     * @return false|int
     */
    public function findCount(string $table, array $where)
    {
        if (! $this->db_ready()) {
            return false;
        }

        $row = $this->single_row($this->table($table)->select('COUNT(*) AS n', false)->where($where)->get());

        return ($row === false) ? false : (int) $row['n'];
    }

    /**
     * @param int[] $ids
     *
     * @return array|false
     */
    protected function find_many(string $table, string $key, array $ids)
    {
        if (! $ids || ! $this->db_ready()) {
            return false;
        }

        return $this->all_rows($this->table($table)->whereIn($key, $ids)->get());
    }

    /**
     * @param int[] $ids
     */
    protected function safe_update_many(string $table, string $key, array $ids, array $data): bool
    {
        return $ids && $data && $this->db_ready()
            && ! $this->write_failed(fn () => (bool) $this->table($table)->whereIn($key, $ids)->update($data));
    }

    /**
     * @param int[] $ids
     */
    protected function safe_delete_many(string $table, string $key, array $ids): bool
    {
        return $ids && $this->db_ready()
            && ! $this->write_failed(fn () => $this->table($table)->whereIn($key, $ids)->delete() !== false);
    }

    /**
     * Save display orders in one transaction: sort_order of each row of
     * $table whose $key is an array key of $orders. FALSE when $orders is
     * empty or the database fails (nothing is changed then).
     *
     * @param array<int, int> $orders id => display order
     */
    protected function update_sort_orders_in(string $table, string $key, array $orders): bool
    {
        if (! $orders || ! $this->db_ready()) {
            return false;
        }

        return $this->write_failed(function () use ($table, $key, $orders) {
            $this->db->transStart();

            foreach ($orders as $id => $order) {
                $this->table($table)->where($key, (int) $id)->update(['sort_order' => (int) $order]);
            }
            $this->db->transComplete();

            return $this->db->transStatus();
        }) === false;
    }

    /**
     * One page of rows from $table plus the total (stored in $this->total_rec_found,
     * as the reference project's list methods do).
     *
     * @param callable|null $filter Applies where-clauses to the query builder (called for the count and the rows)
     *
     * @return array|false
     */
    protected function paged_rows(string $table, string $order, int $offset, int $limit, ?callable $filter = null)
    {
        $this->total_rec_found = 0;

        if (! $this->db_ready()) {
            return false;
        }

        $builder = $this->table($table);
        $filter && $filter($builder);
        $count = $this->single_row($builder->select('COUNT(*) AS n', false)->get());
        if ($count === false) {
            return false;
        }
        $this->total_rec_found = (int) $count['n'];

        $builder = $this->table($table);
        $filter && $filter($builder);

        return $this->all_rows($builder->orderBy($order)->limit($limit, $offset)->get());
    }
}
