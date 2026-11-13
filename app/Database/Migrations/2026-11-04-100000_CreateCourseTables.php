<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Sitepanel > Manage Learning Courses (wl_courses), shown on the website's
 * /learning-courses page (module courses; design: html/learning-courses.htm),
 * and Sitepanel > Manage Course Enquiries (wl_course_enquiries), one row each
 * time a signed-in member presses a course's Register button. The reference
 * project has no courses module; the columns follow the design's course card:
 *
 *   tag             course_category (+ category_muted: the grey "Waitlist" style)
 *   title           course_title (also the image's alt text)
 *   first line      course_schedule · course_duration · course_language
 *   second line     course_mode · course_venue
 *   Register        registration_url, the course's Google Form
 *
 * course_description is kept for the Sitepanel and enquiries (the card has
 * no text block). Images in uploads/courses/.
 *
 * Enquiries keep a copy of the course title and of the member's name, email
 * and mobile at the time of the click, so they stay readable after the
 * course or the member is edited or deleted (both keys are then set to NULL,
 * never cascaded). request_token is the id of one page view's Register form:
 * the same click sent twice is one enquiry (uq_course_enquiry_request).
 */
class CreateCourseTables extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'course_id'           => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'course_title'        => ['type' => 'VARCHAR', 'constraint' => 200],
            'course_category'     => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true, 'comment' => 'Card tag, e.g. Theory Class, Online, Waitlist'],
            'category_muted'      => ['type' => 'ENUM', 'constraint' => ['0', '1'], 'default' => '0', 'comment' => '1 = grey tag (e.g. Waitlist)'],
            'course_description'  => ['type' => 'TEXT', 'null' => true, 'comment' => 'Plain text'],
            'course_image'        => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true, 'comment' => 'File name in uploads/courses/'],
            'course_schedule'     => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true, 'comment' => 'Date / schedule, e.g. 12-13 Oct 2026, Every first Saturday'],
            'course_duration'     => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true, 'comment' => 'e.g. 2 days'],
            'course_language'     => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true, 'comment' => 'e.g. Mandarin, English, Bilingual'],
            'course_mode'         => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true, 'comment' => 'On-site, Online or Hybrid'],
            'course_venue'        => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true, 'comment' => 'e.g. YSD Centre, Zoom'],
            'registration_url'    => ['type' => 'VARCHAR', 'constraint' => 500, 'comment' => 'Google Form address (CoursesModel::registration_url_valid())'],
            'sort_order'          => ['type' => 'INT', 'default' => 0],
            'status'              => ['type' => 'ENUM', 'constraint' => ['0', '1'], 'default' => '1', 'comment' => '1=Active,0=Inactive'],
            'course_date_added'   => ['type' => 'DATETIME'],
            'course_date_updated' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('course_id');
        $this->forge->addKey(['status', 'sort_order'], false, false, 'idx_courses_status_order');
        $this->forge->createTable('wl_courses', true, ['ENGINE' => 'InnoDB', 'COLLATE' => 'utf8mb4_unicode_ci']);

        $this->forge->addField([
            'enquiry_id'       => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'course_id'        => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'comment' => 'NULL once the course is deleted'],
            'course_title'     => ['type' => 'VARCHAR', 'constraint' => 200, 'comment' => 'Title at the time of the enquiry'],
            'customers_id'     => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'comment' => 'wl_customers; NULL once the member is deleted'],
            'customer_name'    => ['type' => 'VARCHAR', 'constraint' => 101],
            'customer_email'   => ['type' => 'VARCHAR', 'constraint' => 120],
            'customer_mobile'  => ['type' => 'VARCHAR', 'constraint' => 32, 'null' => true],
            'enquiry_source'   => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'register', 'comment' => 'register = Register button of /learning-courses'],
            'registration_url' => ['type' => 'VARCHAR', 'constraint' => 500, 'comment' => 'Google Form the member was sent to'],
            'request_token'    => ['type' => 'CHAR', 'constraint' => 32, 'comment' => 'Register form of one page view (duplicate clicks)'],
            'ip_address'       => ['type' => 'VARCHAR', 'constraint' => 45, 'default' => ''],
            'enquiry_date'     => ['type' => 'DATETIME'],
        ]);
        $this->forge->addPrimaryKey('enquiry_id');
        $this->forge->addUniqueKey(['request_token', 'course_id'], 'uq_course_enquiry_request');
        $this->forge->addKey(['course_id', 'enquiry_date'], false, false, 'idx_course_enquiry_course');
        $this->forge->addKey(['customers_id', 'course_id', 'enquiry_date'], false, false, 'idx_course_enquiry_customer');
        $this->forge->addKey('enquiry_date', false, false, 'idx_course_enquiry_date');
        $this->forge->addForeignKey('course_id', 'wl_courses', 'course_id', 'CASCADE', 'SET NULL', 'fk_course_enquiry_course');
        $this->forge->addForeignKey('customers_id', 'wl_customers', 'customers_id', 'CASCADE', 'SET NULL', 'fk_course_enquiry_customer');
        $this->forge->createTable('wl_course_enquiries', true, ['ENGINE' => 'InnoDB', 'COLLATE' => 'utf8mb4_unicode_ci']);
    }

    public function down(): void
    {
        $this->forge->dropTable('wl_course_enquiries', true);
        $this->forge->dropTable('wl_courses', true);
    }
}
