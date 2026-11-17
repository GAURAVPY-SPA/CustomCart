<?php

namespace Modules\Pages\Models;

use App\Models\BaseModel;
use Modules\Sitepanel\Models\MetaModel;
use RuntimeException;
use Throwable;

/**
 * Static pages (wl_cms_pages), shared by the public pages route and Sitepanel.
 *
 * status: '1' = active, '0' = inactive, '2' = deleted (kept, never
 * listed or shown, as in the reference).
 *
 * layout: NULL = shown as its text (Description), else a designed view of
 * modules/pages (Pages::DESIGNED_VIEWS).
 *
 * Each page's address and SEO data are its own wl_meta_tags row (MetaModel),
 * named after the page as in the reference: entity_type
 * 'pages/index/<friendly_url>', entity_id = page_id. The row is the page's
 * route on the website and is kept in step with the page by save_page() and
 * bulk_delete(), as the reference kept a record's meta row (create_meta /
 * update_meta_page_url).
 */
class PagesModel extends BaseModel
{
    public const TABLE       = 'wl_cms_pages';
    public const PRIMARY_KEY = 'page_id';
    public const META_TABLE  = 'wl_meta_tags'; // each page's address and SEO row (MetaModel)

    /**
     * Layout (form value) of a page shown as its text (stored as NULL).
     */
    public const TEXT_LAYOUT = 'cms';

    /**
     * SEO data of a page for the fields left empty (the reference's
     * create_meta(): from the record): title and keywords = the page title,
     * description = the start of its text.
     *
     * @param array $meta meta_title, meta_description, meta_keyword ('' / NULL = empty)
     */
    public static function meta_for(string $name, string $html, array $meta = []): array
    {
        $text = trim((string) preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
        $pick = static fn ($key, $default) => trim((string) ($meta[$key] ?? '')) !== '' ? trim((string) $meta[$key]) : $default;

        return [
            'meta_title'       => $pick('meta_title', $name),
            'meta_description' => $pick('meta_description', mb_substr($text, 0, 160)),
            'meta_keyword'     => $pick('meta_keyword', $name),
        ];
    }

    /**
     * Layout of a page from its layout column (TEXT_LAYOUT when empty).
     */
    public static function layout_of(?string $layout): string
    {
        return (string) $layout !== '' ? (string) $layout : self::TEXT_LAYOUT;
    }

    /**
     * One page matching column => value conditions, e.g.
     * ['page_id' => 3] or ['friendly_url' => 'aboutus', 'status' => '1'].
     *
     * @return array|false|null
     */
    public function get_cms_page(array $page)
    {
        return $page ? $this->find(self::TABLE, $page) : null;
    }

    /**
     * A page that isn't deleted.
     *
     * @param int|string $id
     *
     * @return array|false|null
     */
    public function get_cms_page_by_id($id)
    {
        return $this->find(self::TABLE, [self::PRIMARY_KEY => (int) $id, 'status <>' => '2']);
    }

    /**
     * Pages by name (deleted ones left out), optionally matching a page-name
     * keyword and a status; the total is left in $this->total_rec_found.
     *
     * @param string $status '1' | '0' | '' (all)
     *
     * @return array|false
     */
    public function get_all_cms_page(int $offset = 0, int $limit = 20, string $keyword = '', string $status = '')
    {
        $filter = static function ($db) use ($keyword, $status) {
            $db->where('p.status <>', '2');
            $keyword !== '' && $db->like('p.page_name', $keyword);
            $status !== '' && $db->where('p.status', $status);
        };

        $this->total_rec_found = 0;
        if (! $this->db_ready()) {
            return false;
        }

        $count = $this->table(self::TABLE . ' AS p');
        $filter($count);
        $count = $this->single_row($count->select('COUNT(*) AS n', false)->get());
        if ($count === false) {
            return false;
        }
        $this->total_rec_found = (int) $count['n'];

        $rows = $this->table(self::TABLE . ' AS p');
        $filter($rows);

        return $this->all_rows($rows->orderBy('p.page_name', 'ASC')->orderBy('p.page_id', 'ASC')->limit($limit, $offset)->get());
    }

    /**
     * Whether a page that isn't deleted already uses this friendly URL.
     *
     * @return bool|null NULL when it could not be checked
     */
    public function friendly_url_exists(string $url, int $except_id = 0): ?bool
    {
        $where = ['friendly_url' => $url, 'status <>' => '2'];
        $except_id > 0 && $where['page_id !='] = $except_id;
        $count = $this->findCount(self::TABLE, $where);

        return $count === false ? null : $count > 0;
    }

    /**
     * @param list<int> $ids
     *
     * @return array|false
     */
    public function get_cms_pages_by_ids(array $ids)
    {
        if (! $ids || ! $this->db_ready()) {
            return false;
        }

        return $this->all_rows($this->table(self::TABLE)->where('status <>', '2')->whereIn(self::PRIMARY_KEY, $ids)->get());
    }

    /**
     * @param list<int> $ids
     */
    public function bulk_update_status(array $ids, string $status): bool
    {
        return $this->safe_update_many(self::TABLE, self::PRIMARY_KEY, $ids, ['status' => $status, 'page_updated_date' => date('Y-m-d H:i:s')]);
    }

    /**
     * Delete = status '2' (as the table defines), so a page is never lost by
     * mistake; the pages' wl_meta_tags rows (their addresses) are removed
     * with them, in one transaction, as the reference removed a record's
     * meta row.
     *
     * @param list<int> $ids
     */
    public function bulk_delete(array $ids): bool
    {
        if (! $ids || ! $this->db_ready()) {
            return false;
        }

        return $this->in_transaction(function () use ($ids) {
            $this->table(self::TABLE)->whereIn(self::PRIMARY_KEY, $ids)->update(['status' => '2', 'page_updated_date' => date('Y-m-d H:i:s')]);
            $this->table(self::META_TABLE)->like('entity_type', MetaModel::PAGE_TYPE_PREFIX, 'after')->whereIn('entity_id', $ids)->delete();
        });
    }

    /**
     * The page's wl_meta_tags row (address, layout and SEO data).
     *
     * @return array|false|null
     */
    public function get_page_meta(array $page)
    {
        if (! $this->db_ready()) {
            return false;
        }

        return $this->single_row($this->table(self::META_TABLE)
            ->where('entity_id', (int) $page[self::PRIMARY_KEY])
            ->like('entity_type', MetaModel::PAGE_TYPE_PREFIX, 'after')
            ->limit(1)
            ->get());
    }

    /**
     * Whether a page address is free for this page: no other page uses it,
     * and no other wl_meta_tags row (another page or module, or SEO data
     * of an address) holds it.
     *
     * @return bool|null NULL when it could not be checked
     */
    public function url_available(string $url, int $page_id = 0): ?bool
    {
        $other_page = $this->friendly_url_exists($url, $page_id);
        $row        = $this->find(self::META_TABLE, ['page_url' => $url]);
        if ($other_page === null || $row === false) {
            return null;
        }

        return ! $other_page && ($row === null
            || ($page_id > 0 && (int) $row['entity_id'] === $page_id && str_starts_with((string) $row['entity_type'], MetaModel::PAGE_TYPE_PREFIX)));
    }

    /**
     * Add or update a page and its wl_meta_tags row in one transaction: the
     * row follows the page's address (a changed address moves it and
     * renames it, pages/index/<friendly_url>) and SEO data.
     *
     * @param array      $data wl_cms_pages columns (friendly_url and layout included)
     * @param array      $meta meta_title, meta_description, meta_keyword
     * @param array|null $page The stored page (NULL = add)
     *
     * @return false|int page_id
     */
    public function save_page(array $data, array $meta, ?array $page)
    {
        if (! $this->db_ready()) {
            return false;
        }
        $page_id = $page ? (int) $page[self::PRIMARY_KEY] : 0;

        $saved = $this->in_transaction(function () use ($data, $meta, &$page_id) {
            if ($page_id > 0) {
                $this->table(self::TABLE)->where(self::PRIMARY_KEY, $page_id)->update($data);
            } else {
                $this->table(self::TABLE)->insert($data);
                $page_id = (int) $this->db->insertID();
            }

            $url = (string) $data['friendly_url'];
            $own = $this->table(self::META_TABLE)->where('entity_id', $page_id)->like('entity_type', MetaModel::PAGE_TYPE_PREFIX, 'after')->get()->getRowArray();
            $at  = $this->table(self::META_TABLE)->where('page_url', $url)->get()->getRowArray();

            if ($at && (! $own || (int) $at['meta_id'] !== (int) $own['meta_id'])) {
                throw new RuntimeException('The page address "' . $url . '" is used by another page.');
            }

            $row = $meta + ['page_url' => $url, 'entity_type' => MetaModel::page_type($url), 'entity_id' => $page_id];
            if ($own) {
                $this->table(self::META_TABLE)->where('meta_id', (int) $own['meta_id'])->update($row);
            } else {
                $this->table(self::META_TABLE)->insert($row + ['is_fixed' => 'N', 'status' => '1']);
            }
        });

        return $saved ? $page_id : false;
    }

    /**
     * Run $work in a transaction; FALSE (logged, rolled back) when any query fails.
     */
    protected function in_transaction(callable $work): bool
    {
        try {
            $this->db->transException(true)->transStart();
            $work();
            $this->db->transComplete();

            return true;
        } catch (Throwable $e) {
            $this->db->transRollback();
            log_message('error', $this->name() . ' transaction failed: ' . $e->getMessage());

            return false;
        } finally {
            $this->db->transException(false);
        }
    }
}
