<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Donation orders of the website's Donations page (html/donations.htm), kept
 * apart from enquiries: Sitepanel > Manage Donation Orders.
 *
 *   wl_donations  one row per donation: amount (with the store currency at
 *                 the time), programme, donor details, anonymous, payment
 *                 method and payment status (no payment provider is
 *                 connected: the team follows up and marks it paid).
 *
 * Donations were saved as wl_enquiry rows (type 2, product_service
 * "Donation: <programme>"); those rows are moved here and removed from
 * Manage Enquiry. The Sitepanel section (Other Management) is added.
 * down() moves them back.
 */
class CreateDonationsTable extends Migration
{
    protected const SECTION = 'donation_orders';

    public function up(): void
    {
        $this->forge->addField([
            'donation_id'      => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'donation_amount'  => ['type' => 'DECIMAL', 'constraint' => '12,2'],
            'currency_code'    => ['type' => 'VARCHAR', 'constraint' => 3, 'default' => 'SGD', 'comment' => 'Store currency at the time'],
            'programme'        => ['type' => 'VARCHAR', 'constraint' => 50, 'comment' => 'Pages::DONATION_PROGRAMMES key'],
            'programme_title'  => ['type' => 'VARCHAR', 'constraint' => 100, 'comment' => 'Programme name at the time'],
            'full_name'        => ['type' => 'VARCHAR', 'constraint' => 61],
            'email'            => ['type' => 'VARCHAR', 'constraint' => 80],
            'mobile'           => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'country'          => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'address'          => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'postal_code'      => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => true],
            'is_anonymous'     => ['type' => 'ENUM', 'constraint' => ['0', '1'], 'default' => '0', 'comment' => '1=Donor asked to stay anonymous'],
            'payment_method'   => ['type' => 'VARCHAR', 'constraint' => 20, 'comment' => 'Pages::DONATION_PAYMENTS key'],
            'payment_status'   => ['type' => 'ENUM', 'constraint' => ['pending', 'paid', 'failed', 'cancelled'], 'default' => 'pending'],
            'ip_address'       => ['type' => 'VARCHAR', 'constraint' => 45, 'default' => ''],
            'donation_date'    => ['type' => 'DATETIME'],
            'donation_updated' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('donation_id');
        $this->forge->addKey('donation_date', false, false, 'idx_donation_date');
        $this->forge->addKey(['payment_status', 'donation_date'], false, false, 'idx_donation_status');
        $this->forge->addKey(['ip_address', 'donation_date'], false, false, 'idx_donation_ip');
        $this->forge->createTable('wl_donations', true, ['ENGINE' => 'InnoDB', 'COLLATE' => 'utf8mb4_unicode_ci']);

        $this->move_enquiry_donations();
        $this->add_section();
    }

    public function down(): void
    {
        // Back to wl_enquiry rows, as the donation form saved them before.
        foreach ($this->db->table('wl_donations')->orderBy('donation_id')->get()->getResultArray() as $row) {
            [$first, $last] = array_pad(preg_split('/\s+/u', $row['full_name'], 2), 2, '');
            $amount         = $row['currency_code'] . ' ' . $row['donation_amount'];
            $address        = trim((string) $row['address'] . ($row['postal_code'] !== null ? ', ' . $row['postal_code'] : ''), ', ');
            $this->db->table('wl_enquiry')->insert([
                'type'            => '2',
                'product_service' => 'Donation: ' . $row['programme_title'],
                'budget'          => mb_substr($amount, 0, 40),
                'first_name'      => mb_substr($first, 0, 30),
                'last_name'       => mb_substr($last, 0, 30),
                'email'           => $row['email'],
                'mobile_number'   => $row['mobile'],
                'country'         => $row['country'],
                'address'         => $address !== '' ? mb_substr($address, 0, 220) : null,
                'message'         => implode("\n", array_filter([
                    'Donation of ' . $amount . ' for ' . $row['programme_title'] . '.',
                    'Payment method: ' . $row['payment_method'] . ' (payment status: ' . $row['payment_status'] . ').',
                    $row['is_anonymous'] === '1' ? 'The donor asked for the donation to be anonymous.' : '',
                ])),
                'ip_address'      => $row['ip_address'],
                'reply_status'    => 'N',
                'receive_date'    => $row['donation_date'],
            ]);
        }
        $this->forge->dropTable('wl_donations', true);
        $this->db->table('tbl_admin_sections')->where('section_controller', self::SECTION)->delete();
    }

    /**
     * Donations saved as enquiries: amount from budget ("S$100.00"), payment
     * method and anonymous from the message the form wrote.
     */
    protected function move_enquiry_donations(): void
    {
        $programmes = array_flip(\Modules\Pages\Controllers\Pages::DONATION_PROGRAMMES);
        $payments   = array_flip(\Modules\Pages\Controllers\Pages::DONATION_PAYMENTS);
        $currency   = $this->db->table('wl_currencies')->select('code')->where('is_default', 'Y')->get(1)->getRowArray();
        $rows       = $this->db->table('wl_enquiry')->where('type', '2')->like('product_service', 'Donation: ', 'after')->orderBy('id')->get()->getResultArray();

        foreach ($rows as $row) {
            $title  = trim(substr((string) $row['product_service'], strlen('Donation: ')));
            $method = preg_match('/^Payment method: (.+?) \(/m', (string) $row['message'], $m) ? $m[1] : '';
            [$address, $postal] = preg_match('/^(.*), ([A-Za-z0-9 \-]{1,10})$/', (string) $row['address'], $a) ? [$a[1], $a[2]] : [(string) $row['address'], ''];

            $this->db->transStart();
            $this->db->table('wl_donations')->insert([
                'donation_amount' => (float) preg_replace('/[^0-9.]/', '', (string) $row['budget']),
                'currency_code'   => $currency['code'] ?? 'SGD',
                'programme'       => $programmes[$title] ?? 'general',
                'programme_title' => $title !== '' ? $title : 'General Donation',
                'full_name'       => trim($row['first_name'] . ' ' . $row['last_name']),
                'email'           => $row['email'],
                'mobile'          => $row['mobile_number'],
                'country'         => $row['country'],
                'address'         => $address !== '' ? $address : null,
                'postal_code'     => $postal !== '' ? $postal : null,
                'is_anonymous'    => str_contains((string) $row['message'], 'asked for the donation to be anonymous') ? '1' : '0',
                'payment_method'  => $payments[$method] ?? strtolower($method),
                'payment_status'  => 'pending',
                'ip_address'      => (string) $row['ip_address'],
                'donation_date'   => $row['receive_date'],
            ]);
            $this->db->table('wl_enquiry')->where('id', (int) $row['id'])->delete();
            $this->db->transComplete();
        }
    }

    /**
     * Sitepanel > Other Management > Manage Donation Orders (view, edit the
     * payment status, delete), after Manage Event Registrations.
     */
    protected function add_section(): void
    {
        if ($this->db->table('tbl_admin_sections')->where('section_controller', self::SECTION)->countAllResults() > 0) {
            return;
        }
        $heading = $this->db->table('tbl_admin_sections')->where(['parent_id' => 0, 'section_title' => 'Other Management'])->get(1)->getRowArray();
        if (! $heading) {
            return;
        }
        $this->db->table('tbl_admin_sections')->insert([
            'parent_id'          => (int) $heading['id'],
            'section_title'      => 'Manage Donation Orders',
            'section_controller' => self::SECTION,
            'disp_order'         => 23,
            'actual_privilege'   => '1,3,6',
            'status'             => '1',
        ]);
    }
}
