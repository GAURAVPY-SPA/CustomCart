<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Events & Activities: documents and registrations (html/events-details.htm,
 * "Event Documents" and the #eventRegModal form).
 *
 *   wl_event_documents      files of one event (Sitepanel > Manage Events &
 *                           Activities, the event's form), shown on its page;
 *                           files in uploads/event_documents/. Removed with the event.
 *   wl_event_registrations  Register Now / Join Waitlist of the website (guests and
 *                           signed-in members), Sitepanel > Manage Event Registrations.
 *                           Kept when the event or the member is deleted: event_id /
 *                           customers_id become NULL, the copied title and contact stay.
 *   wl_events.registration_sessions  the form's "Preferred Session" choices, one per line
 *                           (empty = the design's Morning Session, Afternoon Session, Full Day).
 */
class CreateEventDocumentsAndRegistrations extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('wl_events', [
            'registration_sessions' => ['type' => 'TEXT', 'null' => true, 'after' => 'registration_url', 'comment' => 'Preferred Session choices of the registration form, one per line'],
        ]);

        $this->forge->addField([
            'document_id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'event_id'            => ['type' => 'INT', 'unsigned' => true],
            'document_title'      => ['type' => 'VARCHAR', 'constraint' => 150, 'comment' => 'Name shown on the event page'],
            'document_file'       => ['type' => 'VARCHAR', 'constraint' => 200, 'comment' => 'File name in uploads/event_documents/'],
            'file_type'           => ['type' => 'VARCHAR', 'constraint' => 10, 'comment' => 'pdf | doc | docx'],
            'file_size'           => ['type' => 'INT', 'unsigned' => true, 'default' => 0, 'comment' => 'Bytes'],
            'sort_order'          => ['type' => 'INT', 'default' => 0],
            'status'              => ['type' => 'ENUM', 'constraint' => ['0', '1'], 'default' => '1', 'comment' => '1=Active,0=Inactive'],
            'document_date_added' => ['type' => 'DATETIME'],
            'document_date_updated' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('document_id');
        $this->forge->addKey(['event_id', 'status', 'sort_order'], false, false, 'idx_event_documents_event');
        $this->forge->addUniqueKey('document_file', 'uq_event_document_file');
        $this->forge->addForeignKey('event_id', 'wl_events', 'event_id', 'CASCADE', 'CASCADE', 'fk_event_document_event');
        $this->forge->createTable('wl_event_documents', true, ['ENGINE' => 'InnoDB', 'COLLATE' => 'utf8mb4_unicode_ci']);

        $this->forge->addField([
            'registration_id'     => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'event_id'            => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'comment' => 'NULL once the event is deleted'],
            'event_title'         => ['type' => 'VARCHAR', 'constraint' => 200, 'comment' => 'Title at the time of registration'],
            'customers_id'        => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'comment' => 'wl_customers of a signed-in member; NULL = guest (or member deleted)'],
            'registration_type'   => ['type' => 'ENUM', 'constraint' => ['guest', 'member'], 'default' => 'guest'],
            'registration_mode'   => ['type' => 'ENUM', 'constraint' => ['register', 'waitlist'], 'default' => 'register', 'comment' => 'Register Now or Join Waitlist'],
            'full_name'           => ['type' => 'VARCHAR', 'constraint' => 100],
            'email'               => ['type' => 'VARCHAR', 'constraint' => 120],
            'mobile'              => ['type' => 'VARCHAR', 'constraint' => 32],
            'preferred_session'   => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'participants'        => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 1],
            'message'             => ['type' => 'TEXT', 'null' => true],
            'registration_status' => ['type' => 'ENUM', 'constraint' => ['pending', 'confirmed', 'cancelled'], 'default' => 'pending'],
            'request_token'       => ['type' => 'CHAR', 'constraint' => 32, 'comment' => 'Form of one page view (double submits)'],
            'ip_address'          => ['type' => 'VARCHAR', 'constraint' => 45, 'default' => ''],
            'registration_date'   => ['type' => 'DATETIME'],
            'registration_updated' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('registration_id');
        $this->forge->addUniqueKey('request_token', 'uq_event_registration_request');
        $this->forge->addKey(['event_id', 'registration_status'], false, false, 'idx_event_registration_event');
        $this->forge->addKey(['event_id', 'email'], false, false, 'idx_event_registration_email');
        $this->forge->addKey('customers_id', false, false, 'idx_event_registration_customer');
        $this->forge->addKey('registration_date', false, false, 'idx_event_registration_date');
        $this->forge->addForeignKey('event_id', 'wl_events', 'event_id', 'CASCADE', 'SET NULL', 'fk_event_registration_event');
        $this->forge->addForeignKey('customers_id', 'wl_customers', 'customers_id', 'CASCADE', 'SET NULL', 'fk_event_registration_customer');
        $this->forge->createTable('wl_event_registrations', true, ['ENGINE' => 'InnoDB', 'COLLATE' => 'utf8mb4_unicode_ci']);
    }

    public function down(): void
    {
        $this->forge->dropTable('wl_event_registrations', true);
        $this->forge->dropTable('wl_event_documents', true);
        $this->forge->dropColumn('wl_events', 'registration_sessions');
    }
}
