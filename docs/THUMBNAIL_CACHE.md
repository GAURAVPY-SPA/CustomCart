# Thumbnail cache

`get_image()` (app/Helpers/app_helper.php) shows uploaded images as resized
copies kept in `writable/thumb_cache/<folder>/`, served by the
`/thumb_cache/<folder>/<file>` route (App\Controllers\Uploads::thumb()).
Originals in `uploads/` are only ever read.

## How copies are named, reused and refreshed

`thumb_<width>_<height>_<sig>_<original>` (`thumbc_` for centre-cropped copies).

- `<sig>` identifies the version of the original (its modification time and
  size). When an original changes, the next page view makes a new copy under
  a new name and URL, so browsers never keep showing the old image. The old
  copy is then *outdated* and is removed by the cleanup.
- Each size is its own file.
- A page that shows a copy records the use by setting the copy's modification
  time, at most once a day per file (`Config\Uploads::$thumbTouchSeconds`).
  The cleanup uses it as "last used". Filesystem access times are not used.
- Copies are written to a temporary name and renamed, so concurrent requests
  never see half-written files.
- Deleting an original through the Sitepanel (`ImageUpload::delete()`)
  removes its copies straight away.

## Cleanup command

```
php spark cache:cleanup-thumbnails            # remove what is no longer needed
php spark cache:cleanup-thumbnails --dry-run  # report only: cache size and what would go
```

It removes:

| Class    | Meaning |
|----------|---------|
| expired  | not shown by any page for 30 days (`Config\Uploads::$thumbExpireDays`) |
| orphan   | its original no longer exists, or its folder is not an image upload folder |
| outdated | made from an earlier version of the original, or named in the older format without `<sig>` |
| invalid  | empty, not a readable image, a temporary file older than an hour, or not a name `get_image()` makes |

Everything else is kept, including temporary files still being written.
Folders left empty are removed. The `thumb_cache` folder itself and its
`index.html` are always kept, and the folder is recreated if missing.

Safety: the command refuses to run unless the cache folder is a folder named
`thumb_cache` that is neither the upload folder, inside it, nor a parent of
it. It only deletes regular files whose real path is inside that folder,
never follows or removes links, and does not touch sessions, logs, the
application cache or uploads.

Exit code 0 means success. 1 means the folder could not be used safely, or
some files could not be removed; those are retried on the next run.

There is no size limit: a size cap could delete copies that pages still use.
Use `--dry-run` to watch the cache size.

Sitepanel > Clear Cache is unchanged. It empties the whole thumbnail cache
(and the application cache); copies are made again as pages are viewed.

## Daily run on Windows (WAMP): Task Scheduler

This task is not created automatically; set it up once as follows.

1. Open **Task Scheduler**, then **Create Task…** (not "Basic Task").
2. **General** tab:
   - Name: `CustomCart thumbnail cache cleanup`
   - Select *Run whether user is logged on or not*.
   - Use an account that can write to `D:\wamp64\www\customcart\writable\thumb_cache`
     (your own Windows account on a local WAMP install).
3. **Triggers** tab: **New…**, *Daily*, e.g. 03:00, *Enabled*.
4. **Actions** tab: **New…**, *Start a program*:
   - Program/script: `D:\wamp64\bin\php\php8.5.12.1\php.exe`
   - Add arguments: `spark cache:cleanup-thumbnails`
   - Start in: `D:\wamp64\www\customcart`

   *Start in* is required: `spark` is found relative to it.
5. **Settings** tab: tick *Run task as soon as possible after a scheduled start
   is missed*, and *Stop the task if it runs longer than* 1 hour.
6. Click **OK** and enter the account password.

To test it, right-click the task, choose **Run**, then check *Last Run Result*
(`0x0` = success, `0x1` = see `writable/logs/`).

The same task from an administrator Command Prompt:

```
schtasks /Create /TN "CustomCart thumbnail cache cleanup" /SC DAILY /ST 03:00 /RL LIMITED ^
  /TR "cmd /c cd /d D:\wamp64\www\customcart && D:\wamp64\bin\php\php8.5.12.1\php.exe spark cache:cleanup-thumbnails"
```

If WAMP's PHP is upgraded, update the `php.exe` path (any PHP 8.1+ CLI works).

## Linux hosting: cron

```
0 3 * * * cd /path/to/customcart && /usr/bin/php spark cache:cleanup-thumbnails >> writable/logs/thumb-cleanup.log 2>&1
```

Run it as the web server user (e.g. `www-data`) so it can remove the files
that user created.
