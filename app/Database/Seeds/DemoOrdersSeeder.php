<?php

namespace App\Database\Seeds;

use CodeIgniter\CLI\CLI;
use CodeIgniter\Database\Seeder;
use Config\Shop;
use Modules\Cart\Libraries\ShopCart;
use Modules\Cart\Models\OrderModel;
use RuntimeException;

/**
 * OPTIONAL sample orders for trying the dashboard's Orders graph and the
 * Orders pages on a development copy (php spark db:seed DemoOrdersSeeder).
 * Not run by DatabaseSeeder; add active products first (it needs products).
 *
 * Guest orders from 1 January two years ago until today: fewer at first,
 * more each year, busier around Chinese New Year / year end and Mid-Autumn,
 * and about one a day over the last 30 days, so the daily, monthly and yearly
 * views all have data. Each order has 1-3 lines of the store's active
 * products (their current price, photo and code), the delivery charge
 * and GST of Settings > Online store and invoice numbers as checkout records them
 * (OrderModel::place_order(), Shop::$invoicePrefix + $invoiceOffset + id) —
 * but stock is NOT taken. Status follows the order's age: Pending in the
 * last days, Ready For Dispatch / Dispatched in the last week, then mostly
 * Delivered / Closed with some Canceled, Rejected and Return.
 *
 * Every sample order has admin_comment SAMPLE_COMMENT and an @example.com
 * address. Runs once: when sample orders exist, nothing is added. To remove them:
 *   DELETE FROM wl_order WHERE admin_comment = 'Sample order (DemoOrdersSeeder)';
 * (their lines in wl_orders_products are removed with them).
 */
class DemoOrdersSeeder extends Seeder
{
    public const SAMPLE_COMMENT = 'Sample order (DemoOrdersSeeder)';

    protected const NAMES = [
        ['Wei Ling', 'Tan'], ['Jun Hao', 'Lim'], ['Siti', 'Rahman'], ['Arjun', 'Nair'], ['Mei Ying', 'Ng'],
        ['Daniel', 'Lee'], ['Hui Min', 'Goh'], ['Farah', 'Ismail'], ['Kumar', 'Pillai'], ['Grace', 'Wong'],
        ['Zhi Wei', 'Chua'], ['Aisyah', 'Hassan'], ['Benjamin', 'Ong'], ['Li Na', 'Chen'], ['Ravi', 'Menon'],
        ['Shu Fen', 'Koh'], ['Marcus', 'Teo'], ['Nurul', 'Huda'], ['Priya', 'Raj'], ['Kai Xuan', 'Yeo'],
    ];

    protected const STREETS = [
        ['Blk 123 Ang Mo Kio Avenue 6, #08-1234', '560123'], ['Blk 456 Tampines Street 42, #05-210', '520456'],
        ['Blk 78 Toa Payoh Lorong 4, #11-88', '310078'], ['12 Jurong West Street 91, #03-45', '640012'],
        ['Blk 210 Bedok North Street 1, #07-502', '460210'], ['Blk 33 Bukit Batok East Avenue 2, #10-17', '650033'],
        ['88 Punggol Field, #14-06', '828888'], ['Blk 501 Yishun Ring Road, #02-1120', '760501'],
        ['15 Clementi Avenue 3, #09-33', '129905'], ['Blk 640 Woodlands Drive 61, #06-410', '730640'],
    ];

    public function run(): void
    {
        $db = $this->db;
        if ($db->table(OrderModel::TABLE)->where('admin_comment', self::SAMPLE_COMMENT)->countAllResults() > 0) {
            CLI::write('Sample orders already exist; nothing was added. (Remove them first to create a new set: see DemoOrdersSeeder.)', 'yellow');

            return;
        }

        $products = $db->query("SELECT p.products_id, p.product_name, p.product_code, p.product_price, p.product_discounted_price,
                (SELECT m.media FROM wl_products_media m WHERE m.products_id = p.products_id ORDER BY m.is_default = 'Y' DESC, m.sort_order LIMIT 1) AS image
            FROM wl_products p WHERE p.status = '1' ORDER BY p.products_id")->getResultArray();
        if (! $products) {
            throw new RuntimeException('DemoOrdersSeeder needs active products (add products in Sitepanel / run StoreSeeder first).');
        }
        $amounts = service('siteSettings')->shop_amounts(); // delivery and GST as at checkout

        $shop  = config(Shop::class);
        $now   = time();
        $today = strtotime('today');
        $year  = (int) date('Y', $today);
        mt_srand(20261103); // the same sample set on every copy
        $added = 0;

        $db->transException(true)->transStart();
        for ($day = mktime(0, 0, 0, 1, 1, $year - 2); $day <= $today; $day = strtotime('+1 day', $day)) {
            for ($i = $this->orders_on($day, $today, $year); $i > 0; $i--) {
                $at = $day + mt_rand(8 * 3600, 22 * 3600);
                if ($at > $now) {
                    $at = $now - mt_rand(60, 3600);
                }
                if ($at >= $day) {
                    $this->add_order($at, $today, $products, $amounts, $shop);
                    $added++;
                }
            }
        }
        $db->transComplete();
        $db->transException(false);

        CLI::write('Sample orders added: ' . $added . '.', 'green');
    }

    /**
     * How many orders a day gets: about 1.3 a day over the last 30 days,
     * else 0.35 (this year), 0.18 (last year), 0.07 (the year before), more in
     * the festive months (Dec-Feb) and around Mid-Autumn (Sep).
     */
    protected function orders_on(int $day, int $today, int $year): int
    {
        $age  = (int) round(($today - $day) / 86400);
        $rate = $age < 30 ? 1.3 : [$year => 0.35, $year - 1 => 0.18][(int) date('Y', $day)] ?? 0.07;
        $rate *= in_array((int) date('n', $day), [12, 1, 2], true) ? 1.6 : ((int) date('n', $day) === 9 ? 1.3 : 1.0);

        return (int) floor($rate) + (mt_rand() / mt_getrandmax() < $rate - floor($rate) ? 1 : 0);
    }

    /**
     * One guest order with 1-3 product lines, saved like checkout saves it (without taking stock).
     */
    protected function add_order(int $at, int $today, array $products, array $amounts, Shop $shop): void
    {
        [$first, $last] = self::NAMES[mt_rand(0, count(self::NAMES) - 1)];
        [$street, $zip] = self::STREETS[mt_rand(0, count(self::STREETS) - 1)];
        $mobile         = '+65 ' . mt_rand(8, 9) . mt_rand(100, 999) . ' ' . mt_rand(1000, 9999);
        $email          = strtolower(str_replace(' ', '', $first) . '.' . $last) . mt_rand(1, 99) . '@example.com';

        // 1-3 different products, 1-3 of each, at their current price.
        $picked = array_rand($products, min(count($products), mt_rand(1, 3)));
        $lines  = [];
        foreach ((array) $picked as $key) {
            $p       = $products[$key];
            $price   = (float) $p['product_discounted_price'] > 0 ? (float) $p['product_discounted_price'] : (float) $p['product_price'];
            $lines[] = ['product' => $p, 'price' => $price, 'quantity' => mt_rand(1, 10) <= 7 ? 1 : mt_rand(2, 3)];
        }
        $subtotal = round(array_sum(array_map(static fn ($l) => $l['price'] * $l['quantity'], $lines)), 2);

        // Delivery (Manage Shipping) and GST (Settings) as at checkout; some customers pay at the centre.
        $method   = (new \Modules\Cart\Models\ShippingModel())->get_store_method();
        $delivery = $method ? ShopCart::shipping_for($subtotal, (float) $method['shipment_rate'], (float) $method['free_above']) : 0.0;
        $grand    = round($subtotal + $delivery, 2);
        $gst      = round($grand * $amounts['gst_percent'] / (100 + $amounts['gst_percent']), 2);
        $collect  = mt_rand(1, 10) > 7;

        [$status, $line_status] = $this->status_for((int) floor(($today - strtotime(date('Y-m-d', $at))) / 86400));
        $paid      = ! in_array($status, ['Pending', 'Canceled', 'Rejected'], true) || ($status === 'Pending' && mt_rand(0, 1) === 1);
        $delivered = in_array($status, ['Delivered', 'Closed', 'Return'], true);

        $this->db->table(OrderModel::TABLE)->insert([
            'customers_id'           => 0,
            'customer_type'          => 'Guest',
            'invoice_number'         => 'TMP-' . bin2hex(random_bytes(8)),
            'first_name'             => $first,
            'last_name'              => $last,
            'email'                  => $email,
            'mobile_number'          => $mobile,
            'shipping_name'          => $first . ' ' . $last,
            'shipping_address'       => $street,
            'shipping_mobile'        => $mobile,
            'shipping_zipcode'       => $zip,
            'shipping_country'       => 'Saudi Arabia',
            'shipping_city'          => 'Dammam',
            'shipping_id'            => null,
            'shipping_method'        => $delivery > 0 ? 'Standard Delivery' : 'Free Delivery',
            'coupon_discount_amount' => 0,
            'shipping_amount'        => $delivery,
            'total_amount'           => $subtotal,
            'vat_amount'             => $gst,
            'vat_applied_cent'       => $amounts['gst_percent'],
            'price_without_tax'      => round($grand - $gst, 2),
            'grand_total'            => $grand,
            'currency_code'          => site_currency()['code'], // the wl_currencies default the amounts were shown in
            'currency_symbol'        => mb_substr(site_currency()['symbol_left'] !== '' ? site_currency()['symbol_left'] : site_currency()['symbol_right'], 0, 10),
            'currency_value'         => 1,
            'order_status'           => $status,
            'order_received_date'    => date('Y-m-d H:i:s', $at),
            'order_delivery_date'    => $delivered ? date('Y-m-d H:i:s', $at + mt_rand(2, 5) * 86400) : null,
            'payment_method'         => $collect ? 'collect' : 'paynow',
            'payment_status'         => $paid ? 'Paid' : 'Unpaid',
            'payment_date'           => $paid ? date('Y-m-d H:i:s', $at + mt_rand(5, 180) * 60) : null,
            'courier_company_name'   => ! $collect && in_array($status, ['Dispatched', 'Delivered', 'Closed', 'Return'], true) ? 'Ninja Van' : null,
            'bill_number'            => ! $collect && in_array($status, ['Dispatched', 'Delivered', 'Closed', 'Return'], true) ? 'NV' . mt_rand(10000000, 99999999) : null,
            'admin_comment'          => self::SAMPLE_COMMENT,
            'ip_address'             => '127.0.0.1',
        ]);
        $order_id = (int) $this->db->insertID();
        $invoice  = $shop->invoicePrefix . ($shop->invoiceOffset + $order_id);
        $this->db->table(OrderModel::TABLE)->where(OrderModel::PRIMARY_KEY, $order_id)->update(['invoice_number' => $invoice]);

        foreach ($lines as $n => $l) {
            $this->db->table('wl_orders_products')->insert([
                'orders_id'            => $order_id,
                'sub_order_id'         => $invoice . '-' . ($n + 1),
                'products_id'          => (int) $l['product']['products_id'],
                'product_name'         => $l['product']['product_name'],
                'product_code'         => $l['product']['product_code'],
                'product_image'        => $l['product']['image'],
                'product_price'        => $l['price'],
                'quantity'             => $l['quantity'],
                'product_order_status' => $line_status,
            ]);
        }
    }

    /**
     * [order status, line status] for an order received $age days ago.
     *
     * @return array{0: string, 1: string}
     */
    protected function status_for(int $age): array
    {
        $roll = mt_rand(1, 100);
        if ($age <= 2) {
            return $roll <= 85 ? ['Pending', 'Pending'] : ['Ready For Dispatch', 'Ready For Dispatch'];
        }
        if ($age <= 7) {
            return $roll <= 35 ? ['Ready For Dispatch', 'Ready For Dispatch'] : ($roll <= 85 ? ['Dispatched', 'Dispatched'] : ['Delivered', 'Delivered']);
        }

        return match (true) {
            $roll <= 72 => ['Delivered', 'Delivered'],
            $roll <= 88 => ['Closed', 'Closed'],
            $roll <= 94 => ['Canceled', 'Cancelled'],
            $roll <= 97 => ['Return', 'Returned'],
            default     => ['Rejected', 'Rejected'],
        };
    }
}
