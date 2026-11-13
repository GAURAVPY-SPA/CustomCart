<?php

namespace Modules\Cart\Libraries;

use App\Libraries\Mailer;
use Config\Shop;
use Modules\Cart\Models\OrderModel;
use Throwable;

/**
 * Order emails (the reference's order_invoice_content() mails, sent on
 * placing an order and on Sitepanel > Orders status, payment and courier
 * changes), through the site's Mailer.
 *
 * Sent from Admin Settings > contact email with the site name; nothing is
 * sent while that email is not set. A failed email never stops the request:
 * every method returns FALSE and logs the reason instead.
 *
 *   placed($order, $items)                    customer confirmation + admin notification
 *   status_changed($order, $items, $headline) customer update ("Your order CC10003 has been dispatched")
 */
class OrderMailer
{
    protected ?Mailer $mailer = null;

    public function __construct(?Mailer $mailer = null)
    {
        $this->mailer = $mailer;
    }

    /**
     * The order confirmation to the customer and a new-order notice to the
     * site's contact email. TRUE when the customer's email was accepted.
     *
     * @param array       $order wl_order row
     * @param list<array> $items wl_orders_products rows
     */
    public function placed(array $order, array $items): bool
    {
        try {
            $site    = $this->site();
            $invoice = (string) $order['invoice_number'];

            $sent = $this->send(
                (string) $order['email'],
                'Order ' . $invoice . ' received - ' . $site['name'],
                $this->layout($site, 'Thank you for your order', [
                    'Dear ' . $this->name($order) . ',',
                    'We have received your order <strong>' . esc($invoice) . '</strong>. Here are its details.',
                ], $order, $items, true),
            );

            if ($site['email'] !== '') {
                $this->send(
                    $site['email'],
                    'New order ' . $invoice . ' (' . html_entity_decode(display_price(OrderModel::payable($order)), ENT_QUOTES | ENT_HTML5, 'UTF-8') . ')',
                    $this->layout($site, 'New order ' . $invoice, [
                        'A new order was placed by ' . esc(trim($order['first_name'] . ' ' . $order['last_name'])) . ' (' . esc($order['email']) . ', ' . esc($order['mobile_number']) . ').',
                        'Open it in the sitepanel: <a href="' . esc(site_url('sitepanel/orders/view/' . (int) $order['order_id']), 'attr') . '">Orders</a>.',
                    ], $order, $items, false),
                );
            }

            return $sent;
        } catch (Throwable $e) {
            log_message('error', 'OrderMailer::placed: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * An update to the customer: $headline is the first line and the subject,
     * followed by the order's status, payment, courier details and invoice.
     */
    public function status_changed(array $order, array $items, string $headline): bool
    {
        try {
            $site  = $this->site();
            $lines = ['Dear ' . $this->name($order) . ',', esc($headline)];
            $lines[] = 'Order status: <strong>' . esc($order['order_status']) . '</strong> &middot; Payment: <strong>' . esc($order['payment_status']) . '</strong>';

            if ((string) $order['courier_company_name'] !== '' || (string) $order['bill_number'] !== '') {
                $tracking = 'Courier: ' . esc((string) $order['courier_company_name']) . ' &middot; Tracking number: ' . esc((string) $order['bill_number']);
                if (preg_match('#^https?://#i', (string) $order['bill_url'])) {
                    $tracking .= ' &middot; <a href="' . esc($order['bill_url'], 'attr') . '">Track your parcel</a>';
                }
                $lines[] = $tracking;
            }

            return $this->send(
                (string) $order['email'],
                $headline . ' - ' . $site['name'],
                $this->layout($site, $headline, $lines, $order, $items, true),
            );
        } catch (Throwable $e) {
            log_message('error', 'OrderMailer::status_changed: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * The email body: heading, intro lines (already HTML), invoice table,
     * delivery address and payment details.
     *
     * @param bool $for_customer Adds payment instructions and the account link
     */
    public function layout(array $site, string $heading, array $intro, array $order, array $items, bool $for_customer): string
    {
        helper('shop');
        $shop  = config(Shop::class);
        $cell  = 'padding:8px 10px;border-bottom:1px solid #eee;';
        $right = $cell . 'text-align:right;white-space:nowrap;';

        $rows = '';

        foreach ($items as $item) {
            $variant = array_filter([
                (string) $item['product_color'] !== '' ? 'Colour: ' . $item['product_color'] : '',
                (string) $item['product_size'] !== '' ? 'Size: ' . $item['product_size'] : '',
            ]);
            $status = in_array($item['product_order_status'], OrderModel::LINE_ENDED, true) ? ' <em>(' . esc($item['product_order_status']) . ')</em>' : '';
            $rows .= '<tr><td style="' . $cell . '">' . esc($item['product_name']) . $status
                . '<br><span style="color:#777;font-size:12px;">' . esc($item['product_code']) . ($variant ? ' &middot; ' . esc(implode(', ', $variant)) : '') . '</span></td>'
                . '<td style="' . $right . '">' . (int) $item['quantity'] . '</td>'
                . '<td style="' . $right . '">' . display_price($item['product_price']) . '</td>'
                . '<td style="' . $right . '">' . display_price((float) $item['product_price'] * (int) $item['quantity']) . '</td></tr>';
        }

        $total = static fn (string $label, string $value, bool $strong = false) => '<tr><td colspan="3" style="' . $right . ($strong ? 'font-weight:bold;' : '') . '">' . $label . '</td><td style="' . $right . ($strong ? 'font-weight:bold;' : '') . '">' . $value . '</td></tr>';

        $rows .= $total('Subtotal', display_price($order['total_amount']));
        if ((float) $order['coupon_discount_amount'] > 0) {
            $rows .= $total('Coupon discount' . ((string) $order['coupon_code'] !== '' ? ' (' . esc($order['coupon_code']) . ')' : ''), '&minus;' . display_price($order['coupon_discount_amount']));
        }
        $rows .= $total('Delivery: ' . esc($order['shipping_method']), (float) $order['shipping_amount'] > 0 ? display_price($order['shipping_amount']) : 'Free');
        if ((float) $order['vat_amount'] > 0) {
            $rows .= $total('VAT (' . rtrim(rtrim(number_format((float) $order['vat_applied_cent'], 2), '0'), '.') . '%)', display_price($order['vat_amount']));
        }
        $rows .= $total('Grand total', display_price(OrderModel::payable($order)), true);

        $address = implode('<br>', array_map('esc', array_filter([
            $order['shipping_name'], $order['shipping_address'],
            trim(implode(', ', array_filter([$order['shipping_city'], $order['shipping_state'], $order['shipping_zipcode']]))),
            $order['shipping_country'], 'Mobile: ' . $order['shipping_mobile'],
        ])));

        $payment = '<p style="margin:0 0 6px;"><strong>Payment:</strong> ' . esc(payment_method_label((string) $order['payment_method'])) . ' &middot; ' . esc($order['payment_status']) . '</p>';
        if ($for_customer && $order['payment_status'] !== 'Paid') {
            if ($order['payment_method'] === 'paynow') {
                $payment .= '<p style="margin:0 0 6px;">Please pay <strong>' . display_price(OrderModel::payable($order)) . '</strong> by PayNow or bank transfer, quoting <strong>' . esc($order['invoice_number']) . '</strong> as the reference.</p>';
                $shop->paynowUen !== '' && $payment .= '<p style="margin:0 0 6px;">PayNow UEN: <strong>' . esc($shop->paynowUen) . '</strong></p>';
                $shop->bankDetails !== '' && $payment .= '<p style="margin:0 0 6px;">' . nl2br(esc($shop->bankDetails)) . '</p>';
            } elseif ($order['payment_method'] === 'collect') {
                $payment .= '<p style="margin:0 0 6px;">Please pay by cash or card when you collect your order at the CustomCart store. We\'ll email you when it is ready.</p>';
            }
        }

        $notes = (string) $order['delivery_notes'] !== '' ? '<p style="margin:12px 0 0;"><strong>Delivery notes:</strong><br>' . nl2br(esc($order['delivery_notes'])) . '</p>' : '';
        $link  = $for_customer && $order['customer_type'] === 'Member'
            ? '<p style="margin:16px 0 0;"><a href="' . esc(order_url('acc-order-invoice', $order), 'attr') . '">View this order in your account</a></p>'
            : '';

        return '<!doctype html><html><body style="margin:0;padding:24px;background:#f6f2f1;font-family:Arial,Helvetica,sans-serif;color:#242424;">'
            . '<div style="max-width:640px;margin:0 auto;background:#fff;border-radius:12px;padding:24px;">'
            . '<h1 style="margin:0 0 16px;font-size:20px;color:#d71920;">' . esc($heading) . '</h1>'
            . implode('', array_map(static fn ($line) => '<p style="margin:0 0 10px;line-height:1.5;">' . $line . '</p>', $intro))
            . '<p style="margin:16px 0 8px;"><strong>Order ' . esc($order['invoice_number']) . '</strong> &middot; ' . esc(date('d M Y, H:i', strtotime((string) $order['order_received_date']))) . '</p>'
            . '<table role="presentation" style="width:100%;border-collapse:collapse;font-size:14px;"><thead><tr>'
            . '<th style="' . $cell . 'text-align:left;">Product</th><th style="' . $right . '">Qty</th><th style="' . $right . '">Price</th><th style="' . $right . '">Total</th>'
            . '</tr></thead><tbody>' . $rows . '</tbody></table>'
            . '<table role="presentation" style="width:100%;margin-top:16px;font-size:14px;"><tr>'
            . '<td style="vertical-align:top;padding-right:12px;"><strong>Delivery address</strong><br>' . $address . '</td>'
            . '<td style="vertical-align:top;">' . $payment . '</td></tr></table>'
            . $notes . $link
            . '<p style="margin:20px 0 0;line-height:1.5;">Thanks and regards,<br>' . esc($site['name']) . ' Team</p>'
            . '</div></body></html>';
    }

    /**
     * ['name' => site name, 'email' => contact email or ''].
     */
    protected function site(): array
    {
        $settings = service('siteSettings');
        $email    = trim((string) $settings->get('contact_email'));

        return [
            'name'  => (string) $settings->get('site_name'),
            'email' => filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : '',
        ];
    }

    protected function name(array $order): string
    {
        $name = trim((string) $order['first_name'] . ' ' . (string) $order['last_name']);

        return esc($name !== '' ? $name : 'Customer');
    }

    protected function send(string $to, string $subject, string $html): bool
    {
        $site = $this->site();
        if ($site['email'] === '' || ! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            log_message('warning', 'OrderMailer: not sent (' . ($site['email'] === '' ? 'no contact email in Admin Settings' : 'invalid recipient') . ').');

            return false;
        }

        $this->mailer ??= new Mailer();
        if (! $this->mailer->send($to, $subject, $html, $site['email'], $site['name'])) {
            log_message('error', 'OrderMailer: "' . $subject . '" could not be sent: ' . $this->mailer->error());

            return false;
        }

        return true;
    }
}
