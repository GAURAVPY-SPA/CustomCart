<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Sitepanel > Manage Location becomes Country -> State -> City.
 *
 * Before: wl_countries and wl_state, whose rows were cities (region_name)
 * directly under a country. After:
 *   wl_states  (state_id, country_id, state_name, status) — unique per country
 *   wl_cities  (city_id, state_id, city_name, status)     — unique per state
 * Deleting a country deletes its states, deleting a state its cities
 * (foreign key cascade).
 *
 * Data: Saudi Arabia gets its 13 regions (the list the address form used,
 * Config\Shop::$regions) and every existing city is moved under its region.
 * A city that can't be placed (or a city of another country) goes under an
 * "Other" state of its country, so nothing is lost. The old wl_state table
 * is then dropped; down() rebuilds it from wl_cities.
 */
class LocationStateCityHierarchy extends Migration
{
    /**
     * Saudi region => its cities (the cities seeded by KareemtexStoreSetup).
     *
     * @var array<string, list<string>>
     */
    protected array $saudi = [
        'Eastern Province'          => ['Dammam', 'Khobar', 'Dhahran', 'Jubail', 'Qatif', 'Al Ahsa', 'Hafr Al Batin', 'Ras Tanura', 'Abqaiq'],
        'Riyadh Province'           => ['Riyadh', 'Al Kharj'],
        'Makkah Province'           => ['Jeddah', 'Makkah', 'Taif', 'Rabigh'],
        'Madinah Province'          => ['Madinah', 'Yanbu'],
        'Qassim Province'           => ['Buraidah', 'Unaizah'],
        'Asir Province'             => ['Abha', 'Khamis Mushait'],
        'Tabuk Province'            => ['Tabuk'],
        'Hail Province'             => ['Hail'],
        'Northern Borders Province' => ['Arar'],
        'Jazan Province'            => ['Jazan'],
        'Najran Province'           => ['Najran'],
        'Al Bahah Province'         => ['Al Bahah'],
        'Al Jawf Province'          => ['Sakaka'],
    ];

    public function up(): void
    {
        $this->forge->addField([
            'state_id'   => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'country_id' => ['type' => 'INT', 'unsigned' => true],
            'state_name' => ['type' => 'VARCHAR', 'constraint' => 150],
            'status'     => ['type' => 'ENUM', 'constraint' => ['0', '1'], 'default' => '1', 'comment' => '1=Active,0=Inactive'],
        ]);
        $this->forge->addPrimaryKey('state_id');
        $this->forge->addUniqueKey(['country_id', 'state_name'], 'uq_states_country_name');
        $this->forge->addKey('status', false, false, 'idx_states_status');
        $this->forge->addForeignKey('country_id', 'wl_countries', 'country_id', 'CASCADE', 'CASCADE', 'fk_states_country');
        $this->forge->createTable('wl_states', true, ['ENGINE' => 'InnoDB', 'COLLATE' => 'utf8mb4_unicode_ci']);

        $this->forge->addField([
            'city_id'   => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'state_id'  => ['type' => 'INT', 'unsigned' => true],
            'city_name' => ['type' => 'VARCHAR', 'constraint' => 150],
            'status'    => ['type' => 'ENUM', 'constraint' => ['0', '1'], 'default' => '1', 'comment' => '1=Active,0=Inactive'],
        ]);
        $this->forge->addPrimaryKey('city_id');
        $this->forge->addUniqueKey(['state_id', 'city_name'], 'uq_cities_state_name');
        $this->forge->addKey('status', false, false, 'idx_cities_status');
        $this->forge->addForeignKey('state_id', 'wl_states', 'state_id', 'CASCADE', 'CASCADE', 'fk_cities_state');
        $this->forge->createTable('wl_cities', true, ['ENGINE' => 'InnoDB', 'COLLATE' => 'utf8mb4_unicode_ci']);

        // Saudi regions.
        $sa = $this->db->table('wl_countries')->where('name', 'Saudi Arabia')->get()->getRowArray();
        if ($sa) {
            foreach (array_keys($this->saudi) as $region) {
                $this->state_id((int) $sa['country_id'], $region);
            }
        }

        // Existing cities (old wl_state rows) under their state.
        if (! $this->db->tableExists('wl_state')) {
            return;
        }
        $city_region = [];
        foreach ($this->saudi as $region => $cities) {
            foreach ($cities as $city) {
                $city_region[mb_strtolower($city)] = $region;
            }
        }
        foreach ($this->db->table('wl_state')->get()->getResultArray() as $row) {
            $country_id = (int) $row['country_id'];
            $region     = ($sa && $country_id === (int) $sa['country_id']) ? ($city_region[mb_strtolower($row['region_name'])] ?? 'Other') : 'Other';
            $state_id   = $this->state_id($country_id, $region);
            $exists     = $this->db->table('wl_cities')->where(['state_id' => $state_id, 'city_name' => $row['region_name']])->countAllResults();
            $exists || $this->db->table('wl_cities')->insert(['state_id' => $state_id, 'city_name' => mb_substr($row['region_name'], 0, 150), 'status' => $row['status']]);
        }
        $this->forge->dropTable('wl_state', true);
    }

    public function down(): void
    {
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'country_id'  => ['type' => 'INT', 'unsigned' => true],
            'region_name' => ['type' => 'VARCHAR', 'constraint' => 255, 'comment' => 'City name'],
            'status'      => ['type' => 'ENUM', 'constraint' => ['0', '1'], 'default' => '1', 'comment' => '1=Active,0=Inactive'],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['country_id', 'region_name'], 'uq_state_country_name');
        $this->forge->addForeignKey('country_id', 'wl_countries', 'country_id', 'CASCADE', 'CASCADE', 'fk_state_country');
        $this->forge->createTable('wl_state', true, ['ENGINE' => 'InnoDB', 'COLLATE' => 'utf8mb4_unicode_ci']);

        if ($this->db->tableExists('wl_cities')) {
            $rows = $this->db->table('wl_cities c')->select('s.country_id, c.city_name, c.status')
                ->join('wl_states s', 's.state_id = c.state_id')->get()->getResultArray();
            foreach ($rows as $row) {
                $where = ['country_id' => $row['country_id'], 'region_name' => $row['city_name']];
                $this->db->table('wl_state')->where($where)->countAllResults() === 0
                    && $this->db->table('wl_state')->insert($where + ['status' => $row['status']]);
            }
        }
        $this->forge->dropTable('wl_cities', true);
        $this->forge->dropTable('wl_states', true);
    }

    /**
     * The id of a country's state, created (active) when missing.
     */
    protected function state_id(int $country_id, string $name): int
    {
        $row = $this->db->table('wl_states')->where(['country_id' => $country_id, 'state_name' => $name])->get()->getRowArray();
        if ($row) {
            return (int) $row['state_id'];
        }
        $this->db->table('wl_states')->insert(['country_id' => $country_id, 'state_name' => $name, 'status' => '1']);

        return (int) $this->db->insertID();
    }
}
