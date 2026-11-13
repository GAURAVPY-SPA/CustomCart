<?php

namespace Modules\Certificates\Controllers;

use App\Controllers\PublicController;
use App\Libraries\DocumentUpload;
use Modules\Certificates\Models\CertificateModel;

/**
 * Certificates and Brochures (design: html/certificate.htm,
 * html/brochure.htm), routed through wl_meta_tags (MetaModel::route_for()):
 *
 *   /certificates   index      row entity_type certificates/index
 *   /brochure       brochure   row entity_type certificates/brochure
 *
 * The active rows of Sitepanel > Manage Certificates & Brochures: certificates
 * as image cards opening the lightbox (customcart.js), brochures as PDF cards
 * with their size and a download link (the uploads route). A certificate
 * without an image file, or a brochure without its PDF, is left out.
 */
class Certificates extends PublicController
{
    protected CertificateModel $cert_model;

    protected function init(): void
    {
        $this->cert_model = new CertificateModel();
    }

    public function index(): string
    {
        $rows = $this->cert_model->get_active(CertificateModel::TYPE_CERTIFICATE);
        $rows === false && log_message('error', 'Certificates::index could not load the certificates.');

        $files = new DocumentUpload();
        $items = [];
        foreach (is_array($rows) ? $rows : [] as $row) {
            $path = $files->path(CertificateModel::IMAGE_FOLDER, $row['cert_image']);
            if ($path !== '') {
                $items[] = $row + [
                    'thumb_url' => get_image(CertificateModel::IMAGE_FOLDER, $row['cert_image'], 600, 800, 'C'),
                    'full_url'  => base_url($path),
                    'pdf_url'   => ($pdf = $files->path(CertificateModel::FILE_FOLDER, $row['cert_file'])) !== '' ? base_url($pdf) : '',
                ];
            }
        }

        return $this->render('Modules\Certificates\Views\certificates', [
            'page_meta'   => ['title' => 'Certificates | ' . $this->site_settings->get('site_name'), 'description' => 'Quality and compliance certificates of CustomCart.'],
            'items'       => $items,
            'unavailable' => $rows === false,
        ]);
    }

    public function brochure(): string
    {
        $rows = $this->cert_model->get_active(CertificateModel::TYPE_BROCHURE);
        $rows === false && log_message('error', 'Certificates::brochure could not load the brochures.');

        $files = new DocumentUpload();
        $items = [];
        foreach (is_array($rows) ? $rows : [] as $row) {
            $absolute = $files->absolute(CertificateModel::FILE_FOLDER, $row['cert_file']);
            if ($absolute !== '') {
                $items[] = $row + [
                    'url'  => base_url($files->path(CertificateModel::FILE_FOLDER, $row['cert_file'])),
                    'type' => strtoupper(pathinfo((string) $row['cert_file'], PATHINFO_EXTENSION)),
                    'size' => self::file_size((int) @filesize($absolute)),
                ];
            }
        }

        return $this->render('Modules\Certificates\Views\brochure', [
            'page_meta'   => ['title' => 'Brochures | ' . $this->site_settings->get('site_name'), 'description' => 'Download the CustomCart company profile and product catalogues.'],
            'items'       => $items,
            'unavailable' => $rows === false,
        ]);
    }

    /**
     * "4.2 MB", "850 KB".
     */
    public static function file_size(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1) . ' MB';
        }

        return max(1, (int) round($bytes / 1024)) . ' KB';
    }
}
