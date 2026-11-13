<?php

namespace App\Commands;

use App\Libraries\ThumbnailCache;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Uploads;

/**
 * Removes thumbnails get_image() no longer needs from writable/thumb_cache/
 * (App\Libraries\ThumbnailCache): unused for Config\Uploads::$thumbExpireDays,
 * orphans of deleted originals, copies of earlier versions of an original,
 * and invalid or half-written files. Kept thumbnails are untouched; removed
 * ones are made again when a page shows them. Original uploads are only read.
 *
 * Meant to run daily (Windows Task Scheduler, or cron on Linux); see
 * docs/THUMBNAIL_CACHE.md. --dry-run only reports (cache size and what would
 * be removed).
 *
 *   php spark cache:cleanup-thumbnails
 *   php spark cache:cleanup-thumbnails --dry-run
 */
class CleanupThumbnails extends BaseCommand
{
    protected $group       = 'Cache';
    protected $name        = 'cache:cleanup-thumbnails';
    protected $description = 'Removes expired, orphan, outdated and invalid image thumbnails from writable/thumb_cache.';
    protected $usage       = 'cache:cleanup-thumbnails [--dry-run]';
    protected $options     = [
        '--dry-run' => 'Only report the cache size and what would be removed; delete nothing.',
    ];

    public function run(array $params)
    {
        $dry_run = array_key_exists('dry-run', $params) || CLI::getOption('dry-run') !== null;
        $days    = config(Uploads::class)->thumbExpireDays;

        CLI::write($dry_run ? 'Thumbnail Cache Report (dry run, nothing is deleted)' : 'Thumbnail Cache Cleanup Started', 'yellow');
        CLI::newLine();

        $r = (new ThumbnailCache())->scan($dry_run);

        if (! $r['ok']) {
            CLI::error('The thumbnail cache folder could not be used safely; nothing was removed. See the application log.');

            return EXIT_ERROR;
        }

        $verb = $dry_run ? 'to remove' : 'removed';
        $rows = [
            'Cache folder'                             => $r['root'],
            'Total thumbnails scanned'                 => $r['scanned'] . ' (' . ThumbnailCache::format_bytes($r['total_bytes']) . ')',
            "Expired thumbnails {$verb} ({$days}+ days unused)" => $r['expired'],
            "Orphan thumbnails {$verb}"                => $r['orphan'],
            "Outdated thumbnails {$verb}"              => $r['outdated'],
            "Invalid thumbnails {$verb}"               => $r['invalid'],
            'Remaining thumbnails'                     => $r['kept'] . ' (' . ThumbnailCache::format_bytes($r['kept_bytes']) . ')',
            $dry_run ? 'Space to reclaim' : 'Space reclaimed' => ThumbnailCache::format_bytes($r['removed_bytes']),
        ];
        if (! $dry_run) {
            $rows['Empty folders removed'] = $r['folders_removed'];
        }

        $width = max(array_map('strlen', array_keys($rows)));
        foreach ($rows as $label => $value) {
            CLI::write(str_pad($label . ':', $width + 2) . $value);
        }

        if ($r['failed'] > 0) {
            CLI::newLine();
            CLI::error($r['failed'] . ' file(s) could not be removed (in use or no permission); they will be tried again next time.');
        }

        CLI::newLine();
        CLI::write($dry_run ? 'Thumbnail Cache Report Completed.' : 'Thumbnail Cache Cleanup Completed.', 'green');

        return $r['failed'] > 0 ? EXIT_ERROR : EXIT_SUCCESS;
    }
}
