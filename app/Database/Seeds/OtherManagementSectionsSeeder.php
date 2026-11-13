<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Sitepanel menu / permission rows (tbl_admin_sections) of the Other
 * Management modules migrated from the reference project. Each module's
 * controller is matched against section_controller (AdminAccess), and
 * actual_privilege lists the privileges a sub-admin can be given there
 * (1=View, 2=Add, 3=Edit, 4=Activate, 5=Deactivate, 6=Delete).
 *
 * Safe to run again: a section whose controller is already stored is left
 * exactly as it is (title, order, privileges, status), so existing rows and
 * the sub-admin grants pointing at them never change.
 */
class OtherManagementSectionsSeeder extends Seeder
{
    public const HEADING = 'Other Management';

    /**
     * controller => [title, display order (reference order), privileges]
     */
    protected array $sections = [
        'staticpages'     => ['Manage Static Pages', 1, '1,2,3,4,5,6'],
        'enquiry'         => ['Manage Contact Enquiries', 3, '1,3,6'],
        'product_enquiry' => ['Manage Product Enquiry', 4, '1,3,6'],
        'coupons'         => ['Manage Discount Coupons', 5, '1,2,3,4,5,6'],
        'banners'         => ['Manage Banners', 6, '1,2,3,4,5,6'],
        'header_images'   => ['Manage Header Images', 7, '1,2,3,4,5,6'],
        'faq'             => ['Manage FAQs', 9, '1,2,3,4,5,6'],
        'subadmin'        => ['Manage Subadmins', 11, '1,2,3,4,5,6'],
        'location'        => ['Manage Location', 12, '1,2,3,4,5,6'],
        'gallery'         => ['Manage Images Gallery', 13, '1,2,3,4,5,6'],
        'video'           => ['Manage Video Gallery', 14, '1,2,3,4,5,6'],
        'team'            => ['Manage Our Team', 15, '1,2,3,4,5,6'],
    ];

    public function run(): void
    {
        $sections = $this->db->table('tbl_admin_sections');
        $heading  = $sections->select('id')->where(['parent_id' => 0, 'section_title' => self::HEADING])->get()->getRowArray();

        if (! $heading) {
            $this->db->table('tbl_admin_sections')->insert([
                'parent_id' => 0, 'section_title' => self::HEADING, 'section_controller' => null,
                'disp_order' => 2, 'actual_privilege' => null, 'status' => '1',
            ]);
            $heading = ['id' => $this->db->insertID()];
        }

        $stored = array_column($this->db->table('tbl_admin_sections')->select('section_controller')
            ->whereIn('section_controller', array_keys($this->sections))->get()->getResultArray(), 'section_controller');

        foreach ($this->sections as $controller => [$title, $order, $privileges]) {
            if (in_array($controller, $stored, true)) {
                continue;
            }
            $this->db->table('tbl_admin_sections')->insert([
                'parent_id'          => (int) $heading['id'],
                'section_title'      => $title,
                'section_controller' => $controller,
                'disp_order'         => $order,
                'actual_privilege'   => $privileges,
                'status'             => '1',
            ]);
        }
    }
}
