<?php

namespace App\Models;

/**
 * Website settings (table tbl_site_settings, one row per key).
 */
class SettingsModel extends BaseModel
{
    public const TABLE = 'tbl_site_settings';

    /**
     * @return array|false setting_key => setting_value
     */
    public function all()
    {
        if (! $this->db_ready()) {
            return false;
        }

        $rows = $this->all_rows($this->table(self::TABLE)->select('setting_key, setting_value')->get());

        return ($rows === false) ? false : array_column($rows, 'setting_value', 'setting_key');
    }

    /**
     * Insert or update several settings atomically.
     *
     * @param array $values   setting_key => setting_value
     * @param int   $admin_id Who made the change
     */
    public function save(array $values, $admin_id): bool
    {
        if (! $this->db_ready()) {
            return false;
        }

        $now = date('Y-m-d H:i:s');

        $this->db->transStart();

        foreach ($values as $key => $value) {
            $this->db->query(
                'INSERT INTO ' . self::TABLE . ' (setting_key, setting_value, updated_at, updated_by) VALUES (?, ?, ?, ?)
				 ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = VALUES(updated_at), updated_by = VALUES(updated_by)',
                [$key, (string) $value, $now, (int) $admin_id],
            );
        }
        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            $this->query_failed(false);

            return false;
        }

        return true;
    }
}
