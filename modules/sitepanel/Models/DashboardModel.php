<?php

namespace Modules\Sitepanel\Models;

use App\Models\BaseModel;

/**
 * Read-only figures for the sitepanel dashboard.
 */
class DashboardModel extends BaseModel
{
    /**
     * Summary-card counts, each as the listing pages count them.
     *
     * @return array categories / products => [total, active] (not deleted),
     *               members => [total, active]
     *               (registered: no guest checkouts, not deleted), newsletter
     *               => [total, active], orders => [total, pending] (not
     *               deleted), enquiries => [total, unreplied], testimonials
     *               => [total, active, pending]; an entry is FALSE when it
     *               could not be read
     */
    public function content_counts(): array
    {
        $active = "COALESCE(SUM(status = '1'), 0) AS active";

        return [
            'categories' => $this->counts('wl_categories', $active, ['status <>' => '2']),
            'products'   => $this->counts('wl_products', $active, ['status <>' => '2']),
            // Members list: status 1 / 0 (CustomerModel::get_customers()); 3 = guest checkout, 2 = deleted.
            'members'    => $this->counts('wl_customers', $active, "status IN ('0', '1')"),
            'newsletter' => $this->counts('wl_newsletters', $active),
            'orders'     => $this->counts('wl_order', "COALESCE(SUM(order_status = 'Pending'), 0) AS pending", ['order_status <>' => 'Deleted']),
            // As Manage Enquiry lists them (Enquiry::$scope); product enquiries have their own list.
            'enquiries'  => $this->counts('wl_enquiry', "COALESCE(SUM(reply_status = 'N'), 0) AS unreplied", "type IN ('1', '2')"),
            // As Manage Product Enquiry lists them (ProductEnquiry::$scope: type 3).
            'product_enquiries' => $this->counts('wl_enquiry', "COALESCE(SUM(reply_status = 'N'), 0) AS unreplied", "type = '3'"),
            // Pending = a public submission not yet activated (as on the testimonials list).
            'testimonials' => $this->counts('wl_testimonial', "COALESCE(SUM(status = '1'), 0) AS active, "
                . "COALESCE(SUM(status = '0' AND COALESCE(submitted_ip, '') <> ''), 0) AS pending"),
        ];
    }

    /**
     * Newest rows of the dashboard's Recent records box, only the columns shown.
     *
     * @return array newsletter, enquiries, product_enquiries, testimonials => list of rows,
     *               or FALSE when that list could not be read
     */
    public function recent_records(int $limit = 3): array
    {
        return [
            'newsletter'   => $this->recent('wl_newsletters', 'subscriber_id AS id, subscriber_name, subscriber_email, status, subscribe_date AS added', 'subscriber_id', $limit),
            'enquiries'    => $this->recent('wl_enquiry', 'id, first_name, last_name, email, reply_status, receive_date AS added', 'id', $limit, "type IN ('1', '2')"),
            'product_enquiries' => $this->recent('wl_enquiry', 'id, first_name, last_name, email, product_service, reply_status, receive_date AS added', 'id', $limit, "type = '3'"),
            'testimonials' => $this->recent('wl_testimonial', 'testimonial_id AS id, poster_name, status, submitted_ip, posted_date AS added', 'testimonial_id', $limit),
        ];
    }

    /**
     * @param array|string $where Conditions (an array, or SQL written here, never user input)
     *
     * @return array|false
     */
    protected function recent(string $table, string $select, string $key, int $limit, array|string $where = [])
    {
        if (! $this->db_ready()) {
            return false;
        }

        $builder = $this->table($table)->select($select, false);
        $where && $builder->where($where);

        return $this->all_rows($builder->orderBy($key, 'DESC')->limit($limit)->get());
    }

    /**
     * Groups of the orders graph: period => DATE_FORMAT() of the group key.
     */
    public const ORDER_GROUPS = ['daily' => '%Y-%m-%d', 'monthly' => '%Y-%m', 'yearly' => '%Y'];

    /**
     * Orders received since $from (not deleted, as the Orders list counts
     * them), counted per day, month or year. Groups without orders are left
     * out (the caller fills them with 0).
     *
     * @param string $period One of ORDER_GROUPS
     * @param string $from   'Y-m-d H:i:s'
     *
     * @return array<string, int>|false group key ('2026-11-03', '2026-11', '2026') => orders
     */
    public function order_counts(string $period, string $from)
    {
        if (! isset(self::ORDER_GROUPS[$period]) || ! $this->db_ready()) {
            return false;
        }

        $rows = $this->all_rows($this->table('wl_order')
            ->select("DATE_FORMAT(order_received_date, '" . self::ORDER_GROUPS[$period] . "') AS period_key, COUNT(*) AS orders", false)
            ->where('order_status <>', 'Deleted')
            ->where('order_received_date >=', $from)
            ->groupBy('period_key')
            ->get());

        return is_array($rows) ? array_map('intval', array_column($rows, 'orders', 'period_key')) : false;
    }

    /**
     * Orders received from $from (included) to $to (excluded), not deleted,
     * for the graph's "vs previous period" comparison.
     *
     * @param string $from 'Y-m-d H:i:s'
     * @param string $to   'Y-m-d H:i:s'
     *
     * @return false|int
     */
    public function order_total(string $from, string $to)
    {
        return $this->findCount('wl_order', ['order_status <>' => 'Deleted', 'order_received_date >=' => $from, 'order_received_date <' => $to]);
    }

    /**
     * Year of the first order (not deleted), NULL when there is none.
     *
     * @return false|int|null
     */
    public function first_order_year()
    {
        if (! $this->db_ready()) {
            return false;
        }

        $row = $this->single_row($this->table('wl_order')
            ->select('YEAR(MIN(order_received_date)) AS first_year', false)
            ->where('order_status <>', 'Deleted')
            ->get());

        return is_array($row) ? ($row['first_year'] === null ? null : (int) $row['first_year']) : false;
    }

    /**
     * @param string       $extra Extra aggregate columns
     * @param array|string $where Conditions (an array, or SQL written here, never user input)
     *
     * @return array|false total + the extra columns (counts as int)
     */
    protected function counts(string $table, string $extra, array|string $where = [])
    {
        if (! $this->db_ready()) {
            return false;
        }

        $builder = $this->table($table)->select('COUNT(*) AS total, ' . $extra, false);
        $where && $builder->where($where);
        $row = $this->single_row($builder->get());
        if (! is_array($row)) {
            return false;
        }

        foreach ($row as $key => $value) {
            $row[$key] = (int) $value;
        }

        return $row;
    }

    /**
     * Online store figures of the dashboard's Store overview (read only):
     *   revenue        paid orders' grand totals (not deleted / cancelled / rejected) => [total, orders]
     *   statuses       order_status => count (not deleted)
     *   recent_orders  newest orders
     *   recent_products newest products (not deleted) with their default photo
     *   low_stock      stock rows at or under their threshold (the row's own, else $threshold), in stock
     *   low_products   products with such a row
     * An entry is FALSE when it could not be read.
     */
    public function store_overview(int $threshold, int $limit = 5): array
    {
        if (! $this->db_ready()) {
            return array_fill_keys(['revenue', 'statuses', 'recent_orders', 'recent_products', 'low_stock', 'low_products'], false);
        }

        $low_where = "s.product_quantity > 0 AND s.product_quantity <= IF(s.inventory > 0, s.inventory, ?) AND p.status <> '2'";
        $revenue   = $this->single_row($this->db->query(
            "SELECT COALESCE(SUM(grand_total), 0) AS total, COUNT(*) AS orders FROM wl_order
              WHERE payment_status = 'Paid' AND order_status NOT IN ('Deleted', 'Canceled', 'Rejected')"
        ));
        $statuses = $this->all_rows($this->db->query(
            "SELECT order_status, COUNT(*) AS n FROM wl_order WHERE order_status <> 'Deleted' GROUP BY order_status"
        ));
        $low_products = $this->single_row($this->db->query(
            'SELECT COUNT(DISTINCT s.product_id) AS n FROM wl_product_stock s JOIN wl_products p ON p.products_id = s.product_id WHERE ' . $low_where,
            [$threshold]
        ));

        return [
            'revenue'  => is_array($revenue) ? ['total' => (float) $revenue['total'], 'orders' => (int) $revenue['orders']] : false,
            'statuses' => is_array($statuses) ? array_column($statuses, 'n', 'order_status') : false,
            'recent_orders' => $this->all_rows($this->db->query(
                "SELECT order_id, invoice_number, first_name, last_name, grand_total, currency_code, order_status, payment_status, order_received_date
                   FROM wl_order WHERE order_status <> 'Deleted' ORDER BY order_received_date DESC, order_id DESC LIMIT " . (int) $limit
            )),
            'recent_products' => $this->all_rows($this->db->query(
                "SELECT p.products_id, p.product_name, p.product_code, p.display_price, p.product_quantity, p.status,
                        (SELECT m.media FROM wl_products_media m WHERE m.products_id = p.products_id ORDER BY m.is_default = 'Y' DESC, m.sort_order, m.id LIMIT 1) AS media
                   FROM wl_products p WHERE p.status <> '2' ORDER BY p.product_added_date DESC, p.products_id DESC LIMIT " . (int) $limit
            )),
            'low_stock' => $this->all_rows($this->db->query(
                'SELECT s.product_id, p.product_name, s.product_quantity, IF(s.inventory > 0, s.inventory, ?) AS threshold,
                        c.color_name, z.size_name
                   FROM wl_product_stock s
                   JOIN wl_products p ON p.products_id = s.product_id
                   LEFT JOIN wl_colors c ON c.color_id = s.product_color
                   LEFT JOIN wl_sizes z ON z.size_id = s.product_size
                  WHERE ' . $low_where . ' ORDER BY s.product_quantity ASC, p.product_name LIMIT ' . (int) $limit,
                [$threshold, $threshold]
            )),
            'low_products' => is_array($low_products) ? (int) $low_products['n'] : false,
        ];
    }
}
