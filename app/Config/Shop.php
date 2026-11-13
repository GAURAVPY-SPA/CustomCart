<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Online store settings. Every value can be set in .env as shop.<property>.
 *
 * Prices are entered and shown GST-inclusive, as in the design ("Prices include GST"): an order
 * records the GST part of what the customer pays.
 */
class Shop extends BaseConfig
{
    // Currency: the default row of wl_currencies (site_currency(), display_price()).

    /**
     * Invoice number: prefix + order id (CC10008). Orders placed before the
     * prefix changed keep their saved number.
     */
    public string $invoicePrefix = 'CC';
    public int $invoiceOffset    = 10000;

    /**
     * Most units of one product line in the cart.
     */
    public int $maxQuantity = 99;

    /**
     * Products per listing page.
     */
    public int $perPage = 12;

    /**
     * Optional product features (the reference's brands, materials, colours,
     * sizes, weight, button type, labels, per-option stock and reviews). This
     * project's design (html/details.htm, listing.htm) does not use them, so
     * their Sitepanel fields, list filters and storefront parts are hidden.
     * The code, tables and existing data are kept: set a feature to TRUE to
     * bring it back. With 'stock' off, a product's quantity is one "Available
     * quantity" field on the product form; a product that already has colour
     * or size options keeps its per-option Stock page.
     *
     * @var array<string, bool>
     */
    public array $productFeatures = [
        'brand'       => false,
        'material'    => true,
        'colors'      => true,
        'sizes'       => true,
        'weight'      => false,
        'button_type' => false,
        'labels'      => false,
        'stock'       => true,
        'reviews'     => true,
    ];

    /**
     * Whether an optional product feature is on (see $productFeatures).
     */
    public function feature(string $name): bool
    {
        return ! empty($this->productFeatures[$name]);
    }

    /**
     * Payment methods offered at checkout (design: payment.htm, the card
     * tiles of the payment gateway). key => [title, description, icon]
     *
     * The payment gateway is not connected yet (the client provides its API):
     * an order is placed Unpaid and Sitepanel > Manage Orders marks it paid.
     * When the gateway is added, Cart::place_order() starts the payment and
     * its return / callback marks the order paid (OrderModel::set_payment_status()).
     *
     * @var array<string, array{0: string, 1: string, 2: string}>
     */
    public array $paymentMethods = [
        'online' => ['Online Payment', 'Pay securely by card through the payment gateway', 'credit-card'],
    ];

    /**
     * Card logos shown on the payment step (theme images), as in the design.
     *
     * @var array<string, string>
     */
    public array $paymentCards = ['mastercard' => 'Mastercard', 'visa' => 'Visa', 'paypal' => 'PayPal', 'maestro' => 'Maestro'];

    /**
     * Delivery country and its address rules (Saudi Arabia).
     */
    public string $country = 'Saudi Arabia';

    /**
     * Fallback State / Region list of the delivery address, used only while
     * Sitepanel > Manage Location has no active states for the delivery
     * country (Modules\Cart\Libraries\DeliveryAddress::locations()).
     *
     * @var list<string>
     */
    public array $regions = [
        'Eastern Province', 'Riyadh Province', 'Makkah Province', 'Madinah Province', 'Qassim Province', 'Asir Province',
        'Tabuk Province', 'Hail Province', 'Northern Borders Province', 'Jazan Province', 'Najran Province',
        'Al Bahah Province', 'Al Jawf Province',
    ];

    /**
     * NOT USED (the Saudi-only mobile check was removed; mobiles: DeliveryAddress::valid_mobile()).
     * Saudi mobile numbers: 05XXXXXXXX, 5XXXXXXXX, +9665XXXXXXXX or 009665XXXXXXXX
     * (spaces and dashes are ignored).
     */
    public string $mobilePattern = '/^(?:\+?966|00966|0)?5[0-9]{8}$/D';

    /**
     * Saudi postal codes: 5 digits, optionally with the 4-digit extension (32272 or 32272-1234).
     */
    public string $postalPattern = '/^[0-9]{5}(?:-[0-9]{4})?$/D';

    /**
     * Reasons a customer can give when asking to cancel or return an item
     * (the reference's wl_reasons, types 1 and 2).
     *
     * @var list<string>
     */
    public array $cancelReasons = [
        'Ordered by mistake',
        'Found a better price elsewhere',
        'Delivery is taking too long',
        'Need to change the delivery address',
        'Other',
    ];

    /**
     * @var list<string>
     */
    public array $returnReasons = [
        'Item is damaged or defective',
        'Wrong item received',
        'Item does not match the description',
        'Size or colour does not fit',
        'Other',
    ];

    /**
     * Failed sign-ins before an account is locked, and the lock in minutes
     * (wl_customers.number_of_login_try / block_time; unused in the reference).
     */
    public int $maxLoginTries = 5;
    public int $lockMinutes   = 15;

    /**
     * Lifetime of a password reset link, in minutes.
     */
    public int $resetMinutes = 60;
}
