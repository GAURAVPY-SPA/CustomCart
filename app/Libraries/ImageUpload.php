<?php

namespace App\Libraries;

use CodeIgniter\HTTP\Files\UploadedFile;
use Config\Database;
use Config\Uploads;
use Throwable;

/**
 * Image uploads for the sitepanel content modules and the public
 * testimonial form, stored in the root-level upload directory
 * (Config\Uploads: uploads/<folder>/, outside the web root; served by
 * App\Controllers\Uploads).
 *
 * Files get a random name, so a visitor-supplied name is never used on
 * disk. Only JPEG, PNG, GIF and WebP are accepted: the extension and the
 * MIME type detected from the file's content must match, then the saved
 * file is re-read with getimagesize() and removed if it isn't one of those
 * image types.
 *
 * The checks, their order and the messages are those of the CodeIgniter 3
 * Upload library as the sitepanel configured it (allowed_types, max_size,
 * encrypt_name, detect_mime). Messages never contain server paths.
 */
class ImageUpload
{
    /**
     * Upload error messages (CodeIgniter 3 upload_lang.php).
     */
    protected const ERRORS = [
        UPLOAD_ERR_INI_SIZE   => 'The uploaded file exceeds the maximum allowed size in your PHP configuration file.',
        UPLOAD_ERR_FORM_SIZE  => 'The uploaded file exceeds the maximum size allowed by the submission form.',
        UPLOAD_ERR_PARTIAL    => 'The file was only partially uploaded.',
        UPLOAD_ERR_NO_FILE    => 'You did not select a file to upload.',
        UPLOAD_ERR_NO_TMP_DIR => 'The temporary folder is missing.',
        UPLOAD_ERR_CANT_WRITE => 'The file could not be written to disk.',
        UPLOAD_ERR_EXTENSION  => 'The file upload was stopped by extension.',
    ];

    /**
     * Stored image names the thumbnail cache handles (saved uploads are
     * <32 hex>.<ext>); others are shown from their original.
     */
    protected const THUMB_SOURCE_NAME = '/^[A-Za-z0-9][A-Za-z0-9._-]*\.(?:jpe?g|png|gif|webp)$/iD';

    protected Uploads $config;

    public function __construct(?Uploads $config = null)
    {
        $this->config = $config ?? config(Uploads::class);
    }

    /**
     * Whether a file was chosen for this field (successfully or not).
     */
    public function has_file(string $field): bool
    {
        return isset($_FILES[$field]['error']) && ! is_array($_FILES[$field]['error'])
            && $_FILES[$field]['error'] !== UPLOAD_ERR_NO_FILE;
    }

    /**
     * Save the uploaded image.
     *
     * @param string     $field    Form field name
     * @param string     $folder   One of Config\Uploads::$folders
     * @param string     $label    Field label for messages
     * @param array|null $max_size [width, height] limit in pixels
     *
     * @return array ['file' => saved name] or ['error' => message]
     */
    public function save(string $field, string $folder, string $label, ?array $max_size = null): array
    {
        if (! isset($this->config->folders[$folder])) {
            return ['error' => $label . ': upload folder is not allowed.'];
        }

        $error = $this->check(service('request')->getFile($field));
        if (is_string($error)) {
            return ['error' => $label . ': ' . $error];
        }

        /** @var UploadedFile $file */
        $file  = $error;
        $dir   = $this->folder_path($folder);
        $ready = $this->prepare_folder($dir);
        if ($ready !== null) {
            return ['error' => $label . ': ' . $ready];
        }

        $name = bin2hex(random_bytes(16)) . '.' . strtolower($file->getClientExtension());

        try {
            $file->move($dir, $name);
        } catch (Throwable $e) {
            log_message('error', 'ImageUpload: could not move the upload: ' . $e->getMessage());

            return ['error' => $label . ': A problem was encountered while attempting to move the uploaded file to the final destination.'];
        }

        $path = $dir . $name;
        $info = @getimagesize($path);

        if ($info === false || ! in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP], true)) {
            @unlink($path);

            return ['error' => $label . ': the file is not a valid JPG, PNG, GIF or WebP image.'];
        }

        if ($max_size && ($info[0] > $max_size[0] || $info[1] > $max_size[1])) {
            @unlink($path);

            return ['error' => $label . ': the image must be at most ' . $max_size[0] . ' × ' . $max_size[1] . ' pixels.'];
        }

        return ['file' => $name];
    }

    /**
     * The upload, or the reason it is refused (CodeIgniter 3 order:
     * upload error, file type, file size).
     *
     * @param UploadedFile|null $file
     *
     * @return string|UploadedFile
     */
    protected function check($file)
    {
        if (! $file instanceof UploadedFile) {
            return self::ERRORS[UPLOAD_ERR_NO_FILE];
        }

        if (! $file->isValid()) {
            return self::ERRORS[$file->getError()] ?? self::ERRORS[UPLOAD_ERR_NO_FILE];
        }

        $ext = strtolower($file->getClientExtension());
        if (! isset($this->config->mimes[$ext])
            // Images get an extra check, as in CodeIgniter 3.
            || (in_array($ext, ['gif', 'jpg', 'jpeg', 'png'], true) && @getimagesize($file->getTempName()) === false)
            || ! in_array($file->getMimeType(), $this->config->mimes[$ext], true)) {
            return 'The filetype you are attempting to upload is not allowed.';
        }

        if ($file->getSize() / 1024 > $this->config->maxKb) {
            return 'The file you are attempting to upload is larger than the permitted size.';
        }

        return $file;
    }

    /**
     * Create the folder when it is missing; the reason it can't be used, or null.
     */
    protected function prepare_folder(string $dir): ?string
    {
        if (! is_dir($dir) && ! @mkdir($dir, 0755, true) && ! is_dir($dir)) {
            log_message('error', 'ImageUpload: could not create the upload folder ' . $dir);

            return 'The upload path does not appear to be valid.';
        }

        if (! is_really_writable($dir)) {
            log_message('error', 'ImageUpload: the upload folder is not writable: ' . $dir);

            return 'The upload destination folder does not appear to be writable.';
        }

        return null;
    }

    /**
     * Remove a stored image after its record was changed or deleted: a
     * no-op for empty, unknown or missing files, and the file is kept while
     * another record still names it (or when that can't be checked). Its
     * cached thumbnails go with it.
     */
    public function delete(?string $folder, ?string $file): void
    {
        $path = $this->absolute((string) $folder, $file);
        if ($path === '' || $this->is_referenced((string) $folder, basename((string) $file))) {
            return;
        }

        if (@unlink($path)) {
            $this->delete_thumbnails((string) $folder, $file);
        }
    }

    /**
     * Relative path of a stored image (e.g. 'uploads/blog/<file>', which is
     * also its URL path: base_url($path)), or '' when unset or missing.
     */
    public function path(string $folder, ?string $file): string
    {
        return $this->absolute($folder, $file) !== ''
            ? $this->config->urlPrefix . $folder . '/' . basename((string) $file)
            : '';
    }

    /**
     * Filesystem path of a stored image, or '' when the folder is unknown,
     * the name unsafe or the file missing.
     */
    public function absolute(string $folder, ?string $file): string
    {
        $file = basename((string) $file);

        if (! isset($this->config->folders[$folder]) || $file === '' || $file[0] === '.') {
            return '';
        }

        $path = $this->folder_path($folder) . $file;

        return is_file($path) ? $path : '';
    }

    protected function folder_path(string $folder): string
    {
        return rtrim($this->config->directory, '\\/') . DIRECTORY_SEPARATOR . $folder . DIRECTORY_SEPARATOR;
    }

    /* -----------------------------------------------------------------
     * Thumbnail cache (get_image(); the reference's thumbnail_helper.php)
     * ----------------------------------------------------------------- */

    /**
     * Relative path (= URL path) of a resized copy of a stored image in the
     * thumbnail cache, e.g. 'thumb_cache/products/thumb_285_250_<sig>_<file>'
     * (stored in writable/thumb_cache/). <sig> is source_signature() of the
     * original, so a changed original (new size or modification time) gets a
     * new copy under a new name and URL (browsers never keep the old one);
     * the old copy is removed by ThumbnailCache::scan(). Made on first
     * request; reusing it records the use (mark_used()). The original is
     * only read.
     *
     *   R (default): fits within width × height keeping its proportions and is
     *      never enlarged (the reference's resize with maintain_ratio, capped
     *      at the original size).
     *   C: fills width × height, cropped from the centre (the reference's crop),
     *      never enlarged.
     *
     * '' when the image is unset, missing or a document; the original's path
     * when the copy can't be made (logged), so the page still shows it.
     */
    public function thumbnail(string $folder, ?string $file, int $width, int $height, string $option = 'R'): string
    {
        $source = $this->absolute($folder, $file);
        if ($source === '' || in_array($folder, $this->config->documentFolders, true)) {
            return '';
        }

        // A name thumb_absolute() would not serve (unusual characters or type), or a
        // GIF the GD handler can't resize faithfully: show the original.
        if (! preg_match(self::THUMB_SOURCE_NAME, basename((string) $file)) || $this->gif_needs_original($source)) {
            return $this->path($folder, $file);
        }

        $width  = max(1, min($width, $this->config->thumbMaxSide));
        $height = max(1, min($height, $this->config->thumbMaxSide));
        $crop   = strtoupper($option) === 'C';
        $name   = ($crop ? 'thumbc_' : 'thumb_') . $width . '_' . $height . '_' . $this->source_signature($source) . '_' . basename((string) $file);
        $dir    = $this->thumb_folder_path($folder);

        // Reuse a non-empty copy of this version of the original; otherwise make it.
        if (is_file($dir . $name) && filesize($dir . $name) > 0) {
            $this->mark_used($dir . $name);
        } elseif (! $this->make_thumbnail($source, $dir, $name, $width, $height, $crop)) {
            return $this->path($folder, $file);
        }

        return $this->config->thumbUrlPrefix . $folder . '/' . $name;
    }

    /**
     * 8 hex characters identifying this version of an original: its
     * modification time and size. Part of each thumbnail's name.
     */
    public function source_signature(string $source): string
    {
        return hash('crc32b', (int) filemtime($source) . ':' . (int) filesize($source));
    }

    /**
     * Record that a cached thumbnail was shown: its modification time is the
     * last use (ThumbnailCache expires unused copies by it), updated at most
     * once per Config\Uploads::$thumbTouchSeconds, so most requests only read.
     */
    protected function mark_used(string $path): void
    {
        if (time() - (int) filemtime($path) >= $this->config->thumbTouchSeconds) {
            @touch($path);
        }
    }

    /**
     * The parts of a thumbnail name get_image() makes, or NULL:
     * ['crop' => bool, 'width' => int, 'height' => int, 'signature' => string, 'file' => original name].
     */
    public function parse_thumb_name(string $name): ?array
    {
        if (! preg_match('/^(thumbc?)_(\d{1,4})_(\d{1,4})_([0-9a-f]{8})_(.+)$/D', $name, $m) || ! preg_match(self::THUMB_SOURCE_NAME, $m[5])) {
            return null;
        }

        return ['crop' => $m[1] === 'thumbc', 'width' => (int) $m[2], 'height' => (int) $m[3], 'signature' => $m[4], 'file' => $m[5]];
    }

    /**
     * Write the resized copy with the GD image handler (the reference used
     * CodeIgniter 3's image_lib with gd2). It is saved in the original's
     * format (PNG / WebP keep their transparency) to a temporary name and
     * then renamed, so a request never sees a half-written file and two
     * requests making the same copy at once don't break each other.
     */
    protected function make_thumbnail(string $source, string $dir, string $name, int $width, int $height, bool $crop): bool
    {
        if (! is_dir($dir) && ! @mkdir($dir, 0755, true) && ! is_dir($dir)) {
            log_message('error', 'ImageUpload: could not create the thumbnail folder ' . $dir);

            return false;
        }

        $info = @getimagesize($source);
        if ($info === false || $info[0] < 1 || $info[1] < 1
            || ! in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP], true)) {
            log_message('error', 'ImageUpload: not a readable JPG, PNG, GIF or WebP image, no thumbnail made: ' . basename($source));

            return false;
        }

        $tmp = $dir . $name . '.' . bin2hex(random_bytes(4)) . '.tmp';

        try {
            $image = service('image', 'gd', null, false)->withFile($source);

            if ($crop) {
                // The largest centred area of the box's shape the original holds, at most the box size.
                $scale = min(1, $info[0] / $width, $info[1] / $height);
                $image->fit(max(1, (int) round($width * $scale)), max(1, (int) round($height * $scale)), 'center');
            } else {
                $scale = min(1, $width / $info[0], $height / $info[1]);
                $image->resize(max(1, (int) round($info[0] * $scale)), max(1, (int) round($info[1] * $scale)), false);
            }

            $image->save($tmp, 90);

            if (! @rename($tmp, $dir . $name)) {
                @unlink($tmp);

                return is_file($dir . $name); // made by another request meanwhile
            }

            return true;
        } catch (Throwable $e) {
            @unlink($tmp);
            log_message('error', 'ImageUpload: thumbnail of ' . basename($source) . ' could not be made: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Whether a GIF is animated or transparent: the GD handler would keep only
     * its first frame and lose its transparency, so it is shown as uploaded.
     */
    protected function gif_needs_original(string $source): bool
    {
        if (strtolower(pathinfo($source, PATHINFO_EXTENSION)) !== 'gif') {
            return false;
        }

        $data = (string) file_get_contents($source);

        // More than one graphic control block = several frames; its flag bit 0 = a transparent colour.
        return preg_match_all('/\x21\xF9\x04([\x00-\xFF])/', $data, $blocks) > 1
            || (isset($blocks[1][0]) && (ord($blocks[1][0]) & 1) === 1);
    }

    /**
     * Filesystem path of a cached thumbnail to serve (App\Controllers\Uploads::thumb()),
     * or '' unless the folder is an image folder, the name is one get_image()
     * makes and the file exists.
     */
    public function thumb_absolute(string $folder, string $name): string
    {
        if (! isset($this->config->folders[$folder]) || in_array($folder, $this->config->documentFolders, true)
            || $this->parse_thumb_name($name) === null) {
            return '';
        }

        $path = $this->thumb_folder_path($folder) . $name;

        return is_file($path) ? $path : '';
    }

    /**
     * Empty the thumbnail cache (Sitepanel > Clear Cache): every generated
     * file and folder inside it; the cache folder itself and its index.html
     * (as in the other writable/ folders) are kept. Only a folder named
     * thumb_cache that is not the upload directory is ever touched, so
     * originals can't be removed. TRUE when nothing else is left.
     */
    public function clear_thumbnails(): bool
    {
        if (! is_dir(rtrim($this->config->thumbDirectory, '\\/'))) {
            return true; // nothing cached yet; get_image() creates it again
        }

        $real = $this->thumb_root();
        if ($real === null) {
            return false;
        }

        helper('filesystem');
        delete_files($real, true, true, true); // keeps index.html / .htaccess

        foreach (new \FilesystemIterator($real) as $entry) {
            if (! in_array($entry->getFilename(), ['index.html', '.htaccess'], true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Real path of the thumbnail cache folder, created when missing, or NULL
     * when it can't be used safely: only a folder named thumb_cache that is
     * not (inside) the upload directory is ever emptied or cleaned, so
     * originals can never be removed through it.
     */
    public function thumb_root(): ?string
    {
        $dir = rtrim($this->config->thumbDirectory, '\\/');
        if (! is_dir($dir) && ! @mkdir($dir, 0755, true) && ! is_dir($dir)) {
            log_message('error', 'ImageUpload: could not create the thumbnail cache folder ' . $dir);

            return null;
        }

        $real    = realpath($dir);
        $uploads = realpath(rtrim($this->config->directory, '\\/'));
        if ($real === false || basename($real) !== 'thumb_cache'
            || ($uploads !== false && ($real === $uploads || str_starts_with($real, $uploads . DIRECTORY_SEPARATOR)
                || str_starts_with($uploads, $real . DIRECTORY_SEPARATOR)))) {
            log_message('error', 'ImageUpload: the thumbnail cache was not used for removals, its folder is not a separate thumb_cache folder: ' . $dir);

            return null;
        }

        return $real;
    }

    /**
     * Remove the cached thumbnails of one original (all sizes and versions),
     * e.g. after the original was deleted. Only names get_image() makes for
     * exactly that file are removed.
     */
    public function delete_thumbnails(string $folder, ?string $file): void
    {
        $file = basename((string) $file);
        if (! isset($this->config->folders[$folder]) || ! preg_match(self::THUMB_SOURCE_NAME, $file)) {
            return; // the name check also keeps glob() wildcards out of the pattern
        }

        foreach (glob($this->thumb_folder_path($folder) . 'thumb*_' . $file) ?: [] as $path) {
            $parts = $this->parse_thumb_name(basename($path));
            if ($parts !== null && $parts['file'] === $file && is_file($path) && ! is_link($path)) {
                @unlink($path);
            }
        }
    }

    protected function thumb_folder_path(string $folder): string
    {
        return rtrim($this->config->thumbDirectory, '\\/') . DIRECTORY_SEPARATOR . $folder . DIRECTORY_SEPARATOR;
    }

    /**
     * Whether a row of the folder's columns (Config\Uploads::$folders) still
     * names the file. A failed check counts as referenced, so nothing is lost.
     */
    protected function is_referenced(string $folder, string $file): bool
    {
        try {
            $db = Database::connect();

            foreach ($this->config->folders[$folder] ?? [] as [$table, $column]) {
                if ($db->table($table)->where($column, $file)->countAllResults() > 0) {
                    return true;
                }
            }
        } catch (Throwable $e) {
            log_message('error', 'ImageUpload: could not check whether ' . $folder . '/' . $file . ' is still used, so it was kept: ' . $e->getMessage());

            return true;
        }

        return false;
    }
}
