<?php

namespace App\Libraries;

use Config\Uploads;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Maintenance of the get_image() thumbnail cache (writable/thumb_cache/,
 * Config\Uploads::$thumbDirectory): finds and removes copies that are no
 * longer needed. Run by `php spark cache:cleanup-thumbnails` (daily through
 * the OS scheduler); never on a page request. Sitepanel > Clear Cache still
 * empties the whole cache (ImageUpload::clear_thumbnails()).
 *
 * Every file in the cache is put in one class:
 *   expired  — no page has shown it for Config\Uploads::$thumbExpireDays
 *              (its modification time is its last use, ImageUpload::mark_used());
 *   orphan   — its original no longer exists (or its folder is not an image folder);
 *   outdated — made from an earlier version of the original (the signature in
 *              its name no longer matches), or named in the format used
 *              before signatures;
 *   invalid  — empty, not a readable image, a temporary file left by an
 *              interrupted write (older than an hour), or not a name get_image() makes;
 *   kept     — anything else, including temporary files still being written.
 *
 * Safety: only regular files whose real path is inside the validated cache
 * root (ImageUpload::thumb_root()) are removed; links are never followed or
 * removed, and index.html / .htaccess are kept. Originals live in another
 * folder and are only read (to check that they exist).
 */
class ThumbnailCache
{
    /**
     * A temporary file this old was left by an interrupted write.
     */
    public const TMP_GRACE_SECONDS = 3600;

    protected const KEEP_NAMES = ['index.html', '.htaccess'];

    protected Uploads $config;
    protected ImageUpload $images;

    public function __construct(?Uploads $config = null, ?ImageUpload $images = null)
    {
        $this->config = $config ?? config(Uploads::class);
        $this->images = $images ?? new ImageUpload($this->config);
    }

    /**
     * Scan the cache and, unless $dry_run, remove the expired, orphan,
     * outdated and invalid files and the folders left empty. With $dry_run
     * nothing is changed and the counts are what a cleanup would remove (the
     * monitoring report).
     *
     * @return array{ok: bool, root: string, scanned: int, expired: int, orphan: int, outdated: int, invalid: int,
     *               kept: int, kept_bytes: int, removed_bytes: int, total_bytes: int, failed: int, folders_removed: int}
     */
    public function scan(bool $dry_run = false, ?int $now = null): array
    {
        $now    = $now ?? time();
        $result = ['ok' => false, 'root' => '', 'scanned' => 0, 'expired' => 0, 'orphan' => 0, 'outdated' => 0, 'invalid' => 0,
            'kept' => 0, 'kept_bytes' => 0, 'removed_bytes' => 0, 'total_bytes' => 0, 'failed' => 0, 'folders_removed' => 0];

        $root = $this->images->thumb_root(); // also recreates a missing cache folder
        if ($root === null) {
            return $result;
        }
        $result['root'] = $root;
        $this->ensure_index($root);

        $expire_before = $now - $this->config->thumbExpireDays * 86400;
        $items         = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($items as $item) {
            /** @var SplFileInfo $item */
            if ($item->isLink()) {
                continue; // never followed, never removed
            }

            if ($item->isDir()) {
                if (! $dry_run && $this->is_empty_dir($item->getPathname()) && $this->inside($root, $item->getPathname()) && @rmdir($item->getPathname())) {
                    $result['folders_removed']++;
                }

                continue;
            }

            if (in_array($item->getFilename(), self::KEEP_NAMES, true)) {
                continue;
            }

            clearstatcache(true, $item->getPathname());
            $size  = (int) $item->getSize();
            $class = $this->classify($root, $item, $expire_before, $now);

            $result['scanned']++;
            $result['total_bytes'] += $size;

            if ($class === 'kept') {
                $result['kept']++;
                $result['kept_bytes'] += $size;

                continue;
            }

            if ($dry_run || $this->remove($root, $item->getPathname())) {
                $result[$class]++;
                $result['removed_bytes'] += $size;
            } else {
                $result['failed']++;
                $result['kept']++;
                $result['kept_bytes'] += $size;
            }
        }

        $result['ok'] = true;

        return $result;
    }

    /**
     * 'expired', 'orphan', 'outdated', 'invalid' or 'kept'.
     */
    protected function classify(string $root, SplFileInfo $item, int $expire_before, int $now): string
    {
        $name     = $item->getFilename();
        $relative = substr($item->getPathname(), strlen($root) + 1);
        $parts    = explode(DIRECTORY_SEPARATOR, str_replace('/', DIRECTORY_SEPARATOR, $relative));

        if (str_ends_with($name, '.tmp')) {
            return $now - (int) $item->getMTime() > self::TMP_GRACE_SECONDS ? 'invalid' : 'kept';
        }

        // get_image() only writes <folder>/<name>; anything else is not ours to keep.
        if (count($parts) !== 2) {
            return 'invalid';
        }

        $folder = $parts[0];
        $thumb  = $this->images->parse_thumb_name($name);
        if ($thumb === null) {
            // Copies named before signatures were added (thumb_<w>_<h>_<file>).
            return preg_match('/^thumbc?_\d{1,4}_\d{1,4}_[A-Za-z0-9][A-Za-z0-9._-]*$/D', $name) ? 'outdated' : 'invalid';
        }

        if (! isset($this->config->folders[$folder]) || in_array($folder, $this->config->documentFolders, true)) {
            return 'orphan';
        }

        $source = $this->images->absolute($folder, $thumb['file']);
        if ($source === '') {
            return 'orphan';
        }

        clearstatcache(true, $source);
        if ($this->images->source_signature($source) !== $thumb['signature']) {
            return 'outdated';
        }

        if ($item->getSize() === 0 || @getimagesize($item->getPathname()) === false) {
            return 'invalid';
        }

        return $item->getMTime() < $expire_before ? 'expired' : 'kept';
    }

    /**
     * Delete one cache file after checking again that it is a regular file inside the cache root.
     */
    protected function remove(string $root, string $path): bool
    {
        return is_file($path) && ! is_link($path) && $this->inside($root, $path) && @unlink($path);
    }

    protected function inside(string $root, string $path): bool
    {
        $real = realpath($path);

        return $real !== false && str_starts_with($real, $root . DIRECTORY_SEPARATOR);
    }

    protected function is_empty_dir(string $dir): bool
    {
        return ! (new FilesystemIterator($dir))->valid();
    }

    /**
     * Keep the folder's index.html, as in the other writable/ folders, when it was recreated.
     */
    protected function ensure_index(string $root): void
    {
        $index = $root . DIRECTORY_SEPARATOR . 'index.html';
        if (! is_file($index) && is_file(WRITEPATH . 'cache/index.html')) {
            @copy(WRITEPATH . 'cache/index.html', $index);
        }
    }

    /**
     * "1.5 MB" for a byte count.
     */
    public static function format_bytes(int $bytes): string
    {
        $units = ['bytes', 'KB', 'MB', 'GB'];
        $i     = 0;
        $value = (float) $bytes;
        while ($value >= 1024 && $i < count($units) - 1) {
            $value /= 1024;
            $i++;
        }

        return $i === 0 ? $bytes . ' bytes' : number_format($value, 2) . ' ' . $units[$i];
    }
}
