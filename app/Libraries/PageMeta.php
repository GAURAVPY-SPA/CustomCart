<?php

namespace App\Libraries;

use Modules\Sitepanel\Models\MetaModel;

/**
 * SEO head data for website pages (service('pageMeta'); rendered by
 * seo_head()).
 *
 * The values come from the active wl_meta_tags row managed in Sitepanel >
 * Meta Tags, matched as follows:
 *  - a page by its URL slug (MetaModel::get_active_for_url(): <slug>, or the
 *    legacy pages/<slug>); the home page (/ and /home) by the 'home' row;
 *  - a record page (blog) by its entity_type + entity_id
 *    (MetaModel::get_entity_meta()), when the controller names it.
 * Each empty field falls back to the page's own default, then to the
 * website settings (meta_title / meta_description / meta_keywords).
 *
 * Rows are read once per request and never cached across requests, so a
 * change saved in the Sitepanel shows on the next page view.
 */
class PageMeta
{
    /**
     * wl_meta_tags.page_url of the home page (the site root).
     */
    public const HOME_PATH = 'home';

    /**
     * Rows read this request, lookup key => row (NULL = none).
     *
     * @var array<string, array|null>
     */
    protected array $rows = [];

    protected ?MetaModel $model = null;

    /**
     * @param array $page Optional page data:
     *                    'title', 'description', 'keywords' page defaults when the row leaves them empty;
     *                    'entity' [type, id] of a record-owned row, e.g. ['blog', 5];
     *                    'path' URL path to match instead of the current one;
     *                    'lookup' false to use no wl_meta_tags row (404, maintenance, a filtered listing);
     *                    'canonical' URL to use instead of the one built from the path.
     *
     * @return array{title: string, description: string, keywords: string, canonical: string, og_image: string, site_name: string}
     */
    public function head(array $page = []): array
    {
        $path = $this->normalize((string) ($page['path'] ?? service('request')->getPath()));
        $home = ($path === '' || $path === self::HOME_PATH);

        $row = null;
        if (($page['lookup'] ?? true) !== false) {
            $row = isset($page['entity'])
                ? $this->entity_row((string) $page['entity'][0], (int) $page['entity'][1])
                : $this->url_row($home ? self::HOME_PATH : $path);
        }

        $settings = service('siteSettings');
        $og_image = $settings->image_path('og_image');

        return [
            'title'       => $this->first($row['meta_title'] ?? '', $page['title'] ?? '', $settings->get('meta_title'), $settings->get('site_title')),
            'description' => $this->first($row['meta_description'] ?? '', $page['description'] ?? '', $settings->get('meta_description')),
            'keywords'    => $this->first($row['meta_keyword'] ?? '', $page['keywords'] ?? '', $settings->get('meta_keywords')),
            'canonical'   => (string) ($page['canonical'] ?? ($home ? base_url() : base_url($this->encode($path)))),
            'og_image'    => $og_image !== '' ? base_url($og_image) : '',
            'site_name'   => $settings->get('site_name'),
        ];
    }

    protected function url_row(string $path): ?array
    {
        return $this->remember('url:' . $path, fn () => $this->model()->get_active_for_url($path));
    }

    protected function entity_row(string $type, int $id): ?array
    {
        return $this->remember('entity:' . $type . ':' . $id, fn () => $this->model()->get_entity_meta($type, $id, true));
    }

    /**
     * @param callable(): (array|false|null) $lookup
     */
    protected function remember(string $key, callable $lookup): ?array
    {
        if (! array_key_exists($key, $this->rows)) {
            $row              = $lookup();
            $this->rows[$key] = is_array($row) ? $row : null; // false (database error): page defaults
        }

        return $this->rows[$key];
    }

    protected function model(): MetaModel
    {
        return $this->model ??= new MetaModel();
    }

    /**
     * URL path without surrounding or repeated slashes (no query string).
     */
    protected function normalize(string $path): string
    {
        return trim((string) preg_replace('#/+#', '/', $path), '/');
    }

    /**
     * Path with each segment URL-encoded, for the canonical URL.
     */
    protected function encode(string $path): string
    {
        return implode('/', array_map(static fn ($segment) => rawurlencode(rawurldecode($segment)), explode('/', $path)));
    }

    /**
     * First value that isn't empty after trimming ('' if none).
     */
    protected function first(...$values): string
    {
        foreach ($values as $value) {
            $value = trim((string) $value);
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }
}
