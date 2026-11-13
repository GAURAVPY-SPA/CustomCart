<?php

namespace App\Controllers;

use App\Libraries\DocumentUpload;
use App\Libraries\ImageUpload;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Uploads as UploadsConfig;

/**
 * Serves the uploaded images (GET /uploads/<folder>/<file>) from the
 * root-level upload directory, which is outside the web root, and the
 * documents of Config\Uploads::$documentFolders (a PDF opens in the browser,
 * DOC / DOCX are downloaded; ?download=1 downloads any of them).
 *
 * Only a known folder and a plain file name are accepted (the route
 * segments can't contain "/"), the file must exist, and the type is
 * detected from its content and must be one of the accepted image types;
 * anything else is a plain 404. Nothing is ever executed or listed.
 *
 * A plain Controller (not BaseController): no settings query, session or
 * maintenance page for image requests.
 */
class Uploads extends Controller
{
    public function serve(string $folder = '', string $file = ''): ResponseInterface
    {
        $config   = config(UploadsConfig::class);
        $document = in_array($folder, $config->documentFolders, true);
        $path     = (new ImageUpload($config))->absolute($folder, $file);
        $mime     = $path !== '' ? (string) (new \finfo(FILEINFO_MIME_TYPE))->file($path) : '';
        $ext      = strtolower(pathinfo($file, PATHINFO_EXTENSION));

        if ($document) {
            // The detected type and the content must match the extension's document type.
            if ($mime === '' || ! in_array($mime, $config->documentMimes[$ext] ?? [], true) || ! DocumentUpload::content_matches($path, $ext)) {
                return $this->not_found();
            }
            $mime = ['pdf' => 'application/pdf', 'doc' => 'application/msword', 'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'][$ext];
        } elseif ($mime === '' || ! in_array($mime, array_merge(...array_values($config->mimes)), true)) {
            return $this->not_found();
        }

        $mtime = (int) filemtime($path);
        $etag  = '"' . md5($folder . '/' . $file . '/' . $mtime . '/' . filesize($path)) . '"';

        // setCache() replaces the framework's default no-store header.
        $this->response
            ->setCache([
                'public',
                'max-age'       => $config->cacheSeconds,
                'etag'          => $etag,
                'last-modified' => gmdate('D, d M Y H:i:s', $mtime) . ' GMT',
            ])
            ->setHeader('X-Content-Type-Options', 'nosniff');

        if ($document) {
            // A PDF opens in the browser's viewer (which a sandbox CSP would block); Word files are always downloaded.
            $download = $ext !== 'pdf' || $this->request->getGet('download') === '1';
            $this->response->setHeader('Content-Disposition', ($download ? 'attachment' : 'inline') . '; filename="' . $file . '"');
        } else {
            $this->response->setHeader('Content-Security-Policy', "default-src 'none'; sandbox");
        }

        if (trim($this->request->getHeaderLine('If-None-Match')) === $etag) {
            return $this->response->setStatusCode(304);
        }

        return $this->response
            ->setHeader('Content-Type', $mime) // no charset: binary file
            ->setHeader('Content-Length', (string) filesize($path))
            ->setBody((string) file_get_contents($path));
    }

    /**
     * A resized copy from the thumbnail cache (GET
     * /thumb_cache/<folder>/<file>, made by get_image() in writable/thumb_cache/), with
     * the same checks and headers as serve(). A copy removed by Clear Cache
     * is a 404 until a page asks for it again through get_image().
     */
    public function thumb(string $folder = '', string $file = ''): ResponseInterface
    {
        $config = config(UploadsConfig::class);
        $path   = (new ImageUpload($config))->thumb_absolute($folder, $file);
        $mime   = $path !== '' ? (string) (new \finfo(FILEINFO_MIME_TYPE))->file($path) : '';

        if ($mime === '' || ! in_array($mime, array_merge(...array_values($config->mimes)), true)) {
            return $this->not_found();
        }

        // The name holds the original's version, so name + size identify the content; the
        // modification time is the last use (ImageUpload::mark_used()) and is left out.
        $mtime = (int) filemtime($path);
        $etag  = '"' . md5('thumb/' . $folder . '/' . $file . '/' . filesize($path)) . '"';

        $this->response
            ->setCache([
                'public',
                'max-age'       => $config->cacheSeconds,
                'etag'          => $etag,
                'last-modified' => gmdate('D, d M Y H:i:s', $mtime) . ' GMT',
            ])
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setHeader('Content-Security-Policy', "default-src 'none'; sandbox");

        if (trim($this->request->getHeaderLine('If-None-Match')) === $etag) {
            return $this->response->setStatusCode(304);
        }

        return $this->response
            ->setHeader('Content-Type', $mime)
            ->setHeader('Content-Length', (string) filesize($path))
            ->setBody((string) file_get_contents($path));
    }

    /**
     * Old image URLs (/uploaded_files/<folder>/<file>, before the uploads
     * moved out of public/): permanent redirect to the new URL.
     */
    public function legacy(string $folder = '', string $file = ''): ResponseInterface
    {
        $path = (new ImageUpload())->path($folder, $file);

        return $path === '' ? $this->not_found() : $this->response->redirect(base_url($path), 'auto', 301);
    }

    protected function not_found(): ResponseInterface
    {
        return $this->response->setStatusCode(404)
            ->setContentType('text/plain')
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setBody('Not found');
    }
}
