<?php

/**
 * Sitepanel list helpers (CodeIgniter 3: modules/sitepanel/helpers/admin_helper.php).
 */
if (! function_exists('admin_pagination')) {
    /**
     * Page links for a sitepanel list (named as in the reference project's
     * admin_helper). Pages are ?page=N on $base_uri; other query parameters
     * (search, filters) are kept.
     *
     * The markup is exactly what CodeIgniter 3's Pagination library produced
     * for the sitepanel's settings (page_query_string, use_page_numbers,
     * reuse_query_string, num_links 2, Bootstrap tags, rel links), so the
     * existing CSS and tests apply unchanged. CodeIgniter 4's Pager builds
     * different markup, so the algorithm is kept here.
     *
     * @param string $base_uri   e.g. 'sitepanel/banners'
     * @param int    $record_per Rows per page
     *
     * @return string HTML ('' for a single page)
     */
    function admin_pagination(string $base_uri, $total_rows, $record_per): string
    {
        $total_rows = (int) $total_rows;
        $per_page   = (int) $record_per;
        $num_links  = 2; // page number links on each side of the current page

        if ($total_rows === 0 || $per_page === 0) {
            return '';
        }

        $num_pages = (int) ceil($total_rows / $per_page);

        if ($num_pages === 1) {
            return '';
        }

        // Other query parameters are kept; page 1 is the URL without ?page=.
        $get = service('request')->getGet();
        $get = is_array($get) ? $get : [];
        unset($get['c'], $get['m'], $get['page']);

        $base_url  = trim(site_url($base_uri));
        $separator = str_contains($base_url, '?') ? '&amp;' : '?';
        $first_url = $base_url . ($get ? $separator . http_build_query($get) : '');
        $page_url  = $base_url . $separator . http_build_query(array_merge($get, ['page' => ''])); // + page number

        $cur_page = service('request')->getGet('page');
        $cur_page = (is_string($cur_page) && ctype_digit($cur_page) && (int) $cur_page !== 0) ? (int) $cur_page : 1;
        $cur_page = min($cur_page, $num_pages);

        $link = static function (int $page, string $label, string $rel = '') use ($first_url, $page_url): string {
            $href = $page === 1 ? $first_url : $page_url . $page;
            $rel  = $rel === '' ? '' : ' rel="' . $rel . '"';

            return '<li class="page-item"><a href="' . $href . '" class="page-link" data-ci-pagination-page="' . $page . '"'
                . $rel . '>' . $label . '</a></li>';
        };

        $output = '';

        // rel="start" is on "First", or on the page 1 link when "First" isn't shown.
        if ($cur_page > $num_links + 1) {
            $output .= $link(1, 'First', 'start');
        }

        if ($cur_page !== 1) {
            $output .= $link($cur_page - 1, 'Previous', 'prev');
        }

        for ($page = max(1, $cur_page - $num_links); $page <= min($cur_page + $num_links, $num_pages); $page++) {
            if ($page === $cur_page) {
                $output .= '<li class="page-item active" aria-current="page"><span class="page-link">' . $page . '</span></li>';
            } else {
                $output .= $link($page, (string) $page, $page === 1 ? 'start' : '');
            }
        }

        if ($cur_page < $num_pages) {
            $output .= $link($cur_page + 1, 'Next', 'next');
        }

        if ($cur_page + $num_links < $num_pages) {
            $output .= $link($num_pages, 'Last');
        }

        $output = preg_replace('#([^:"])//+#', '\1/', $output);

        return '<nav class="sp-pagination" aria-label="Pages"><ul class="pagination mb-0">' . $output . '</ul></nav>';
    }
}
