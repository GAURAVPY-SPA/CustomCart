<?php

namespace Modules\Sitepanel\Controllers;

use App\Controllers\AdminController;
use Modules\Sitepanel\Config\Sitepanel as SitepanelConfig;
use Modules\Sitepanel\Libraries\AdminAccess;
use Modules\Sitepanel\Models\DashboardModel;

/**
 * Sitepanel dashboard (protected). Every figure comes from a live query;
 * anything that can't be read is shown as unavailable rather than guessed.
 * A card links to its section only for an admin who may open that section.
 *
 * Summary cards: active categories, active products, registered members
 * and orders (as their lists count them: no deleted rows, no guest
 * checkouts). Recent records box: newsletter subscribers, enquiries
 * and testimonials, each with its total and newest entries (recent_boxes()).
 * Orders graph (when Config\Sitepanel::$showDashboardOrdersGraph is on, for
 * admins who may open Manage Orders): orders
 * received per day (last 30 days), month (last 12 months) or year (up to the
 * last 10), not counting deleted orders; ?period=daily|monthly|yearly picks
 * the one shown, and the dashboard script switches between them in place.
 */
class Dashboard extends AdminController
{
    /**
     * Orders graph periods => [button label, caption].
     */
    public const PERIODS = [
        'daily'   => ['Daily', 'Last 30 days'],
        'monthly' => ['Monthly', 'Last 12 months'],
        'yearly'  => ['Yearly', 'By year'],
    ];

    protected string $menu_highlight = 'dashboard';

    public function index(): string
    {
        $model  = new DashboardModel();
        $period = (string) $this->request->getGet('period');
        $counts = $model->content_counts();
        $store  = $model->store_overview(service('siteSettings')->low_stock_threshold());

        return $this->render('dashboard/dashbord_index_view', [
            'heading_title' => 'Dashboard',
            'breadcrumbs'   => ['Dashboard' => null],
            'cards'         => array_merge($this->summary_cards($counts), $this->store_cards($store)),
            'store'         => $store + ['can_orders' => $this->can_open_section('orders'), 'can_products' => $this->can_open_section('products')],
            'currency'      => (string) (site_currency()['code'] ?? 'SAR'),
            'recent'        => $this->recent_boxes($counts, $model->recent_records()),
            // Config\Sitepanel::$showDashboardOrdersGraph off: no section and none of its queries.
            'chart'         => config(SitepanelConfig::class)->showDashboardOrdersGraph && $this->can_open_section('orders') ? $this->orders_chart($model) : null,
            'period'        => isset(self::PERIODS[$period]) ? $period : 'daily',
        ]);
    }

    /**
     * POST sitepanel/dashboard/clear_cache (header account menu): empty the
     * application cache (Config\Cache handler, writable/cache/ for the file
     * handler) and the image thumbnail cache (writable/thumb_cache/,
     * ImageUpload::clear_thumbnails(); thumbnails are made again when pages
     * show them). Sessions, logs, original uploads and documents live
     * elsewhere and are not touched; the file handler keeps the folder's
     * index.html/.htaccess and the thumb_cache folder itself is kept. Open to
     * every signed-in administrator (dashboard is AdminAccess::ALWAYS_ALLOWED).
     * Returns to the sitepanel page the admin came from.
     */
    public function clear_cache(): never
    {
        $this->require_post();

        $failed = [];

        try {
            if (! cache()->clean()) {
                $failed[] = 'application cache';
            }
        } catch (\Throwable $e) {
            log_message('error', 'Sitepanel clear cache failed: ' . $e->getMessage());
            $failed[] = 'application cache';
        }

        try {
            if (! (new \App\Libraries\ImageUpload())->clear_thumbnails()) {
                $failed[] = 'image thumbnails';
            }
        } catch (\Throwable $e) {
            log_message('error', 'Sitepanel clear thumbnail cache failed: ' . $e->getMessage());
            $failed[] = 'image thumbnails';
        }

        if ($failed === []) {
            $this->session->setFlashdata('sitepanel_success', 'Cache cleared successfully.');
        } else {
            $this->session->setFlashdata('sitepanel_error', 'The ' . implode(' and the ', $failed) . ' could not be cleared. Please try again.');
        }

        // Only return to a sitepanel page (never an outside or frontend URL).
        $previous = previous_url();
        $prefix   = site_url('sitepanel/');
        redirect_now(str_starts_with($previous, $prefix) && ! str_contains($previous, 'clear_cache') ? $previous : 'sitepanel/dashboard');
    }

    /**
     * The three series of the orders graph: period => [caption, total,
     * points => list<[label, title, count]>], or FALSE when the orders could
     * not be read. Every day / month / year of the range is a point (0 when
     * there was no order).
     *
     * @return array|false
     */
    protected function orders_chart(DashboardModel $model)
    {
        $today = strtotime('today');
        $first = $model->first_order_year();
        if ($first === false) {
            return false;
        }

        $year   = (int) date('Y', $today);
        $start  = max($year - 9, min($first ?? $year, $year));
        // period => [first point, step, group key, axis label, tooltip label, start of the previous
        // equal period (NULL: no comparison — the yearly view already spans every year shown)]
        $ranges = [
            'daily'   => [strtotime('-29 days', $today), '+1 day', 'Y-m-d', 'j M', 'D j M Y', strtotime('-59 days', $today)],
            'monthly' => [strtotime(date('Y-m-01', $today) . ' -11 months'), '+1 month', 'Y-m', 'M y', 'F Y', strtotime(date('Y-m-01', $today) . ' -23 months')],
            'yearly'  => [mktime(0, 0, 0, 1, 1, $start), '+1 year', 'Y', 'Y', 'Y', null],
        ];

        $chart = [];
        foreach ($ranges as $period => [$from, $step, $key, $label, $title, $previous_from]) {
            $counts = $model->order_counts($period, date('Y-m-d H:i:s', $from));
            if ($counts === false) {
                return false;
            }

            $points = [];
            for ($at = $from; $at <= $today; $at = strtotime($step, $at)) {
                $points[] = ['label' => date($label, $at), 'title' => date($title, $at), 'count' => $counts[date($key, $at)] ?? 0];
            }
            $total = array_sum(array_column($points, 'count'));

            // % change against the equal period just before; only when that period had orders.
            $change = null;
            if ($previous_from !== null) {
                $previous = $model->order_total(date('Y-m-d H:i:s', $previous_from), date('Y-m-d H:i:s', $from));
                if ($previous) {
                    $change = ['percent' => round(($total - $previous) / $previous * 100, 1), 'previous' => $previous,
                        'against' => $period === 'daily' ? 'previous 30 days' : 'previous 12 months'];
                }
            }

            $chart[$period] = [
                'caption' => self::PERIODS[$period][1] . ($period === 'yearly' ? ' (' . ($start === $year ? $year : $start . '–' . $year) . ')' : ''),
                'total'   => $total,
                'change'  => $change,
                'points'  => $points,
            ];
        }

        return $chart;
    }

    /**
     * @param array $counts DashboardModel::content_counts()
     *
     * @return list<array> stat-card view models
     */
    protected function summary_cards(array $counts): array
    {
        $none     = static fn ($c) => $c['total'] === 0;
        // Cards showing the active count: "All active", or "Active · 3 inactive".
        $active   = static fn ($c) => $none($c) ? 'None added yet' : ($c['total'] === $c['active'] ? 'All active' : 'Active · ' . number_format($c['total'] - $c['active']) . ' inactive');

        return [
            $this->count_card('category', 'folder', 'Categories', $counts['categories'], $active, 'active'),
            $this->count_card('products', 'box', 'Products', $counts['products'], $active, 'active'),
            $this->count_card('members', 'users', 'Members', $counts['members'], static fn ($c) => $none($c) ? 'None registered yet' : number_format($c['active']) . ' active · ' . number_format($c['total'] - $c['active']) . ' inactive'),
            $this->count_card('orders', 'cart', 'Orders', $counts['orders'], static fn ($c) => $none($c) ? 'None received yet' : ($c['pending'] ? number_format($c['pending']) . ' pending' : 'None pending')),
        ];
    }

    /**
     * Revenue (paid orders) and Low stock cards of the Store overview.
     *
     * @param array $store DashboardModel::store_overview()
     *
     * @return list<array> stat-card view models
     */
    protected function store_cards(array $store): array
    {
        $code  = (string) (site_currency()['code'] ?? 'SAR');
        $cards = [];

        if (! $this->can_open_section('orders')) {
            $cards[] = $this->card('chart', 'Revenue', '—', 'No access to this section') + ['key' => 'revenue'];
        } elseif ($store['revenue'] === false) {
            $cards[] = $this->card('chart', 'Revenue', 'Unavailable', 'Could not be read', 'warn') + ['key' => 'revenue'];
        } else {
            $paid    = $store['revenue']['orders'];
            $cards[] = $this->card('chart', 'Revenue', $code . ' ' . number_format($store['revenue']['total'], 2),
                $paid ? 'From ' . number_format($paid) . ' paid ' . ($paid === 1 ? 'order' : 'orders') : 'No paid orders yet')
                + ['url' => site_url('sitepanel/orders'), 'key' => 'revenue'];
        }

        if (! $this->can_open_section('products')) {
            $cards[] = $this->card('alert', 'Low Stock', '—', 'No access to this section') + ['key' => 'lowstock'];
        } elseif ($store['low_products'] === false) {
            $cards[] = $this->card('alert', 'Low Stock', 'Unavailable', 'Could not be read', 'warn') + ['key' => 'lowstock'];
        } else {
            $n       = $store['low_products'];
            $cards[] = $this->card('alert', 'Low Stock', number_format($n), $n ? ($n === 1 ? 'Product' : 'Products') . ' to restock soon' : 'All stock levels healthy')
                + ['url' => site_url('sitepanel/products'), 'key' => 'lowstock'];
        }

        return $cards;
    }

    /**
     * The Recent records box: per section its total, a summary line and its
     * newest entries, each linking to its page in the Sitepanel.
     *
     * @param array $counts DashboardModel::content_counts()
     * @param array $rows   DashboardModel::recent_records()
     *
     * @return list<array> [icon, label, url, total, meta, empty, items => list<[title, sub, url]>]
     *                     (access FALSE: no access; items FALSE: could not be read)
     */
    protected function recent_boxes(array $counts, array $rows): array
    {
        $date  = static fn ($value) => $value ? date('j M Y', strtotime((string) $value)) : '';
        $state = static fn ($row) => $row['status'] === '1' ? 'Active' : 'Inactive';
        $boxes = [
            'newsletter' => ['mail', 'Newsletter subscribers', 'newsletter', 'No subscribers yet',
                static fn ($c) => number_format($c['active']) . ' active · ' . number_format($c['total'] - $c['active']) . ' inactive',
                static fn ($r) => [(string) $r['subscriber_name'] !== '' ? $r['subscriber_name'] : $r['subscriber_email'], $r['subscriber_email'] . ' · ' . $date($r['added']), 'sitepanel/newsletter/edit/' . $r['id']]],
            'enquiries' => ['inbox', 'Enquiries', 'enquiry', 'No enquiries yet',
                static fn ($c) => $c['unreplied'] ? number_format($c['unreplied']) . ' not replied' : 'All replied',
                static fn ($r) => [trim($r['first_name'] . ' ' . $r['last_name']) ?: $r['email'], ($r['reply_status'] === 'Y' ? 'Replied' : 'Not replied') . ' · ' . $date($r['added']), 'sitepanel/enquiry/view/' . $r['id']]],
            'product_enquiries' => ['box', 'Product Enquiry', 'product_enquiry', 'No product enquiries yet',
                static fn ($c) => $c['unreplied'] ? number_format($c['unreplied']) . ' not replied' : 'All replied',
                static fn ($r) => [trim($r['first_name'] . ' ' . $r['last_name']) ?: $r['email'],
                    implode(' · ', array_filter([(string) $r['product_service'], $r['reply_status'] === 'Y' ? 'Replied' : 'Not replied', $date($r['added'])])),
                    'sitepanel/product_enquiry/view/' . $r['id']]],
            'testimonials' => ['message', 'Testimonials', 'testimonial', 'No testimonials yet',
                static fn ($c) => number_format($c['active']) . ' active · ' . number_format($c['pending']) . ' pending review',
                static fn ($r) => [$r['poster_name'], ($r['status'] === '1' ? 'Active' : (($r['submitted_ip'] ?? '') !== '' ? 'Pending review' : 'Inactive')) . ' · ' . $date($r['added']), 'sitepanel/testimonial/edit/' . $r['id']]],
        ];

        $out = [];
        foreach ($boxes as $key => [$icon, $label, $section, $empty, $meta, $item]) {
            $box = ['icon' => $icon, 'label' => $label, 'url' => site_url('sitepanel/' . $section), 'empty' => $empty, 'access' => $this->can_open_section($section)];
            if ($box['access']) {
                $c     = $counts[$key];
                $list  = $rows[$key];
                $box  += [
                    'total' => $c === false ? null : $c['total'],
                    'meta'  => $c === false || $c['total'] === 0 ? '' : $meta($c),
                    'items' => $list === false ? false : array_map(static function ($r) use ($item) {
                        [$title, $sub, $url] = $item($r);

                        return ['title' => (string) $title, 'sub' => $sub, 'url' => site_url($url)];
                    }, $list),
                ];
            }
            $out[] = $box;
        }

        return $out;
    }

    /**
     * @param string     $section Sitepanel controller of the listing page
     * @param array|bool $c       Counts, or FALSE when they could not be read
     * @param callable   $meta    Counts => meta line
     * @param string     $value   The count shown: 'total' or 'active'
     */
    protected function count_card(string $section, string $icon, string $label, $c, callable $meta, string $value = 'total'): array
    {
        // 'key' picks the card's gradient (Config\Sitepanel::$themeStatGradients).
        if (! $this->can_open_section($section)) {
            return $this->card($icon, $label, '—', 'No access to this section') + ['key' => $section];
        }
        if ($c === false) {
            return $this->card($icon, $label, 'Unavailable', 'Could not be read', 'warn') + ['key' => $section];
        }

        return $this->card($icon, $label, number_format($c[$value]), $meta($c)) + ['url' => site_url('sitepanel/' . $section), 'key' => $section];
    }

    protected function card(string $icon, string $label, string $value, string $meta, string $tone = 'neutral'): array
    {
        return compact('icon', 'label', 'value', 'meta', 'tone');
    }
}
