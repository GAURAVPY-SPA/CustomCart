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
        $num_links  = 2;
        $segment    = 'page';
        $attributes = ' class="page-link"';
        $data_attr  = 'data-ci-pagination-page';

        $first_tag_open = $last_tag_open = $prev_tag_open = $next_tag_open = $num_tag_open = '<li class="page-item">';
        $first_tag_close = $last_tag_close = $prev_tag_close = $next_tag_close = $num_tag_close = '</li>';
        $cur_tag_open  = '<li class="page-item active" aria-current="page"><span class="page-link">';
        $cur_tag_close = '</span></li>';

        if ($total_rows === 0 || $per_page === 0) {
            return '';
        }

        $num_pages = (int) ceil($total_rows / $per_page);

        if ($num_pages === 1) {
            return '';
        }

        // rel="start|prev|next" is given to the first link of each kind only.
        $link_types = ['start' => 'start', 'prev' => 'prev', 'next' => 'next'];
        $rel        = static function (string $type) use (&$link_types): string {
            if (isset($link_types[$type])) {
                unset($link_types[$type]);

                return ' rel="' . $type . '"';
            }

            return '';
        };

        $get = service('request')->getGet();
        $get = is_array($get) ? $get : [];
        unset($get['c'], $get['m'], $get[$segment]);

        $base_url         = trim(site_url($base_uri));
        $query_string_sep = (! str_contains($base_url, '?')) ? '?' : '&amp;';

        $first_url = $base_url;
        if (! empty($get)) {
            $first_url .= $query_string_sep . http_build_query($get);
        }
        $base_url .= $query_string_sep . http_build_query(array_merge($get, [$segment => '']));

        $base_page = 1;
        $cur_page  = service('request')->getGet($segment) ?? '';
        $cur_page  = is_string($cur_page) ? $cur_page : '';

        if (! ctype_digit($cur_page) || (int) $cur_page === 0) {
            $cur_page = $base_page;
        } else {
            $cur_page = (int) $cur_page;
        }

        if ($cur_page > $num_pages) {
            $cur_page = $num_pages;
        }

        $uri_page_number = $cur_page;

        $start = (($cur_page - $num_links) > 0) ? $cur_page - ($num_links - 1) : 1;
        $end   = (($cur_page + $num_links) < $num_pages) ? $cur_page + $num_links : $num_pages;

        $output = '';

        if ($cur_page > ($num_links + 1 + ! $num_links)) {
            $attr = sprintf('%s %s="%d"', $attributes, $data_attr, 1);
            $output .= $first_tag_open . '<a href="' . $first_url . '"' . $attr . $rel('start') . '>'
                . 'First</a>' . $first_tag_close;
        }

        if ($cur_page !== 1) {
            $i    = $uri_page_number - 1;
            $attr = sprintf('%s %s="%d"', $attributes, $data_attr, ($cur_page - 1));

            if ($i === $base_page) {
                $output .= $prev_tag_open . '<a href="' . $first_url . '"' . $attr . $rel('prev') . '>'
                    . 'Previous</a>' . $prev_tag_close;
            } else {
                $output .= $prev_tag_open . '<a href="' . $base_url . $i . '"' . $attr . $rel('prev') . '>'
                    . 'Previous</a>' . $prev_tag_close;
            }
        }

        for ($loop = $start - 1; $loop <= $end; $loop++) {
            $i    = $loop;
            $attr = sprintf('%s %s="%d"', $attributes, $data_attr, $loop);

            if ($i >= $base_page) {
                if ($cur_page === $loop) {
                    $output .= $cur_tag_open . $loop . $cur_tag_close;
                } elseif ($i === $base_page) {
                    $output .= $num_tag_open . '<a href="' . $first_url . '"' . $attr . $rel('start') . '>'
                        . $loop . '</a>' . $num_tag_close;
                } else {
                    $output .= $num_tag_open . '<a href="' . $base_url . $i . '"' . $attr . '>'
                        . $loop . '</a>' . $num_tag_close;
                }
            }
        }

        if ($cur_page < $num_pages) {
            $i    = $cur_page + 1;
            $attr = sprintf('%s %s="%d"', $attributes, $data_attr, $cur_page + 1);
            $output .= $next_tag_open . '<a href="' . $base_url . $i . '"' . $attr
                . $rel('next') . '>Next</a>' . $next_tag_close;
        }

        if (($cur_page + $num_links + ! $num_links) < $num_pages) {
            $i    = $num_pages;
            $attr = sprintf('%s %s="%d"', $attributes, $data_attr, $num_pages);
            $output .= $last_tag_open . '<a href="' . $base_url . $i . '"' . $attr . '>'
                . 'Last</a>' . $last_tag_close;
        }

        $output = preg_replace('#([^:"])//+#', '\1/', $output);

        return '<nav class="sp-pagination" aria-label="Pages"><ul class="pagination mb-0">' . $output . '</ul></nav>';
    }
}
