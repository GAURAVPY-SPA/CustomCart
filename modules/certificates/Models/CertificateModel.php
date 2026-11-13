<?php

namespace Modules\Certificates\Models;

use App\Libraries\DocumentUpload;
use App\Libraries\ImageUpload;
use App\Models\BaseModel;

/**
 * Certificates and brochures (wl_certificates) — Sitepanel > Manage
 * Certificates & Brochures and the website's /certificates and /brochure
 * pages. cert_type: TYPE_CERTIFICATE (an image, optional PDF) or
 * TYPE_BROCHURE (a PDF).
 */
class CertificateModel extends BaseModel
{
    public const TABLE       = 'wl_certificates';
    public const PRIMARY_KEY = 'certificate_id';

    public const TYPE_CERTIFICATE = 'certificate';
    public const TYPE_BROCHURE    = 'brochure';

    public const TYPES = [self::TYPE_CERTIFICATE => 'Certificate', self::TYPE_BROCHURE => 'Brochure'];

    /**
     * Upload folders (Config\Uploads): images and PDFs (a document folder).
     */
    public const IMAGE_FOLDER = 'certificates';
    public const FILE_FOLDER  = 'brochures';

    protected const ORDER = 'sort_order ASC, certificate_id DESC';

    /**
     * Sitepanel list; the total is left in $this->total_rec_found.
     *
     * @return array|false
     */
    public function get_items(int $offset, int $per_page, string $keyword = '', string $status = '', string $type = '')
    {
        return $this->paged_rows(self::TABLE, self::ORDER, $offset, $per_page, static function ($db) use ($keyword, $status, $type) {
            $keyword !== '' && $db->like('title', $keyword);
            $status !== '' && $db->where('status', $status);
            $type !== '' && $db->where('cert_type', $type);
        });
    }

    /**
     * Active items of one type for the website, in display order.
     *
     * @return array|false
     */
    public function get_active(string $type)
    {
        if (! $this->db_ready()) {
            return false;
        }

        return $this->all_rows($this->table(self::TABLE)
            ->select('certificate_id, title, description, cert_image, cert_file')
            ->where(['status' => '1', 'cert_type' => $type])
            ->orderBy(self::ORDER, '', false)
            ->get());
    }

    /**
     * @param int|string $id
     *
     * @return array|false|null
     */
    public function get_item($id)
    {
        return $this->find(self::TABLE, [self::PRIMARY_KEY => (int) $id]);
    }

    /**
     * @param list<int> $ids
     *
     * @return array|false
     */
    public function get_items_by_ids(array $ids)
    {
        return $this->find_many(self::TABLE, self::PRIMARY_KEY, $ids);
    }

    /**
     * @return false|int
     */
    public function next_sort_order()
    {
        if (! $this->db_ready()) {
            return false;
        }
        $row = $this->single_row($this->table(self::TABLE)->selectMax('sort_order', 'max_order')->get());

        return is_array($row) ? (int) $row['max_order'] + 1 : false;
    }

    /**
     * @param array<int, int> $orders certificate_id => display order
     */
    public function update_sort_orders(array $orders): bool
    {
        if (! $orders || ! $this->db_ready()) {
            return false;
        }

        return $this->write_failed(function () use ($orders) {
            $this->db->transStart();
            foreach ($orders as $id => $order) {
                $this->table(self::TABLE)->where(self::PRIMARY_KEY, (int) $id)->update(['sort_order' => (int) $order]);
            }
            $this->db->transComplete();

            return $this->db->transStatus();
        }) === false;
    }

    /**
     * @param list<int> $ids
     */
    public function bulk_update_status(array $ids, string $status): bool
    {
        return $this->safe_update_many(self::TABLE, self::PRIMARY_KEY, $ids, ['status' => $status]);
    }

    /**
     * Delete the rows, then their image and PDF files.
     *
     * @param list<int> $ids
     */
    public function bulk_delete(array $ids): bool
    {
        $rows = $this->get_items_by_ids($ids);
        if (! is_array($rows) || ! $this->safe_delete_many(self::TABLE, self::PRIMARY_KEY, $ids)) {
            return false;
        }
        foreach ($rows as $row) {
            self::delete_files($row);
        }

        return true;
    }

    /**
     * Remove a row's image and PDF from the upload folders.
     */
    public static function delete_files(array $row): void
    {
        (new ImageUpload())->delete(self::IMAGE_FOLDER, $row['cert_image']);
        (new DocumentUpload())->delete(self::FILE_FOLDER, $row['cert_file']);
    }
}
