<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Volunteer accounts, on the store's existing account table (wl_customers,
 * CustomerAuth) rather than a second sign-in system:
 *
 *   wl_customers.account_type   'user' (store customer, the default for every
 *                               existing row) or 'volunteer'. An email is one
 *                               account of one type (user_name stays unique);
 *                               CustomerAuth only lets an account into the
 *                               pages of its own type.
 *   wl_volunteers               the volunteer's registration details (design:
 *                               html/volunteer-training-registration.htm),
 *                               one row per volunteer account, deleted with it.
 *                               application_status is set in Sitepanel > Manage
 *                               Volunteers (new registrations are 'pending').
 *
 * Interests and availability are comma-separated keys of VolunteerModel's
 * option lists. Photos in uploads/volunteers/.
 */
class AddVolunteerAccounts extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('wl_customers', [
            'account_type' => ['type' => 'ENUM', 'constraint' => ['user', 'volunteer'], 'null' => false, 'default' => 'user', 'after' => 'user_name', 'comment' => 'user = store customer, volunteer = volunteer account'],
        ]);
        $this->db->query('ALTER TABLE wl_customers ADD INDEX idx_customers_type_status (account_type, status)');

        $this->forge->addField([
            'volunteer_id'       => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'customers_id'       => ['type' => 'INT', 'unsigned' => true, 'comment' => 'The volunteer account (wl_customers)'],
            'age_group'          => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'gender'             => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'occupation'         => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'interests'          => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'comment' => 'Comma-separated VolunteerModel::INTERESTS keys'],
            'training_session'   => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true, 'comment' => 'Config\Volunteer::$trainingSessions key'],
            'experience'         => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'availability'       => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'comment' => 'Comma-separated VolunteerModel::AVAILABILITY keys'],
            'reason'             => ['type' => 'TEXT', 'null' => true],
            'skills'             => ['type' => 'TEXT', 'null' => true],
            'photo'              => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true, 'comment' => 'File name in uploads/volunteers/'],
            'application_status' => ['type' => 'ENUM', 'constraint' => ['pending', 'approved', 'rejected'], 'default' => 'pending'],
            'date_added'         => ['type' => 'DATETIME'],
            'date_updated'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('volunteer_id');
        $this->forge->addUniqueKey('customers_id', 'uq_volunteer_customer');
        $this->forge->addKey('application_status', false, false, 'idx_volunteer_application');
        $this->forge->addForeignKey('customers_id', 'wl_customers', 'customers_id', 'CASCADE', 'CASCADE', 'fk_volunteer_customer');
        $this->forge->createTable('wl_volunteers', true, ['ENGINE' => 'InnoDB', 'COLLATE' => 'utf8mb4_unicode_ci']);
    }

    public function down(): void
    {
        $this->forge->dropTable('wl_volunteers', true);
        $this->db->query('ALTER TABLE wl_customers DROP INDEX idx_customers_type_status');
        $this->forge->dropColumn('wl_customers', 'account_type');
    }
}
