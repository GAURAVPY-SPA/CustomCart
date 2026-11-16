<?php

namespace App\Libraries;

use CodeIgniter\HTTP\Files\UploadedFile;
use Config\Uploads;
use Throwable;
use ZipArchive;

/**
 * Document uploads (PDF, DOC, DOCX) for the document folders of
 * Config\Uploads::$documentFolders, e.g. uploads/event_documents/ (outside the
 * web root; served by App\Controllers\Uploads, PDFs inline, Word files as a
 * download).
 *
 * The folder rules are those of ImageUpload (random file names, folders of
 * Config\Uploads::$folders, a file kept while a row still names it); the
 * checks differ: the extension must be one of $documentMimes and the MIME type
 * detected from the content must match it, the size must be within
 * $documentMaxKb, and the saved file is checked again (a PDF starts with
 * "%PDF-", a DOCX holds word/document.xml, a DOC is an OLE compound file).
 */
class DocumentUpload extends ImageUpload
{
    public function __construct(?Uploads $config = null)
    {
        parent::__construct($config);
    }

    /**
     * Whether the folder is a document folder.
     */
    public function is_document_folder(string $folder): bool
    {
        return in_array($folder, $this->config->documentFolders, true) && isset($this->config->folders[$folder]);
    }

    /**
     * Save one uploaded document.
     *
     * @param UploadedFile|null $file   From $request->getFile() / getFiles()
     * @param string            $folder One of Config\Uploads::$documentFolders
     * @param string            $label  Name used in messages (e.g. the document title)
     *
     * @return array ['file' => saved name, 'type' => extension, 'size' => bytes] or ['error' => message]
     */
    public function save_document($file, string $folder, string $label): array
    {
        if (! $this->is_document_folder($folder)) {
            return ['error' => $label . ': upload folder is not allowed.'];
        }

        $checked = $this->check_document($file);
        if (is_string($checked)) {
            return ['error' => $label . ': ' . $checked];
        }

        $dir   = $this->folder_path($folder);
        $ready = $this->prepare_folder($dir);
        if ($ready !== null) {
            return ['error' => $label . ': ' . $ready];
        }

        $ext  = strtolower($checked->getClientExtension());
        $size = (int) $checked->getSize();
        $name = bin2hex(random_bytes(16)) . '.' . $ext;

        try {
            $checked->move($dir, $name);
        } catch (Throwable $e) {
            log_message('error', 'DocumentUpload: could not move the upload: ' . $e->getMessage());

            return ['error' => $label . ': A problem was encountered while attempting to move the uploaded file to the final destination.'];
        }

        if (! self::content_matches($dir . $name, $ext)) {
            @unlink($dir . $name);

            return ['error' => $label . ': the file is not a valid ' . strtoupper($ext) . ' document.'];
        }

        return ['file' => $name, 'type' => $ext, 'size' => $size];
    }

    /**
     * The upload, or why it is refused (upload error, file type, file size).
     *
     * @param UploadedFile|null $file
     *
     * @return string|UploadedFile
     */
    protected function check_document($file)
    {
        if (! $file instanceof UploadedFile) {
            return self::ERRORS[UPLOAD_ERR_NO_FILE];
        }
        if (! $file->isValid()) {
            return self::ERRORS[$file->getError()] ?? self::ERRORS[UPLOAD_ERR_NO_FILE];
        }

        $ext = strtolower($file->getClientExtension());
        if (! isset($this->config->documentMimes[$ext]) || ! in_array($file->getMimeType(), $this->config->documentMimes[$ext], true)) {
            return 'Only PDF, DOC and DOCX documents can be uploaded.';
        }

        if ($file->getSize() / 1024 > $this->config->documentMaxKb) {
            return 'The document is larger than the permitted ' . round($this->config->documentMaxKb / 1024) . ' MB.';
        }

        return $file;
    }

    /**
     * Whether a stored file's content is the document type of its extension.
     */
    public static function content_matches(string $path, string $ext): bool
    {
        $head = (string) @file_get_contents($path, false, null, 0, 8);

        switch ($ext) {
            case 'pdf':
                return str_starts_with($head, '%PDF-');

            case 'doc':
                return $head === "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1"; // OLE compound file

            case 'docx':
                if (! str_starts_with($head, "PK\x03\x04") || ! class_exists(ZipArchive::class)) {
                    return false;
                }
                $zip = new ZipArchive();
                if ($zip->open($path) !== true) {
                    return false;
                }
                $ok = $zip->locateName('word/document.xml') !== false;
                $zip->close();

                return $ok;
        }

        return false;
    }
}
