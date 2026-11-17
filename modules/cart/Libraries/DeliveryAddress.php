<?php

namespace Modules\Cart\Libraries;

use App\Libraries\FormValidation;
use Config\Shop;
use Modules\Sitepanel\Models\CityModel;

/**
 * The delivery address form (design: guest-checkout.htm, checkout.htm
 * Add New Address, address.htm): full name, mobile, alternate mobile,
 * landmark, address, Country -> State / Region -> City (from Sitepanel >
 * Manage Location: active countries that have active states, their active
 * states and those states' active cities) and postal code.
 *
 * Mobile numbers: any country (7-15 digits, optional +). The postal code
 * follows the chosen country: the Saudi rule of Config\Shop for Saudi
 * Arabia, an international format for any other. Used by the checkout and My Account > My Address.
 */
class DeliveryAddress
{
    public const FIELDS = ['full_name', 'mobile', 'alt_mobile', 'landmark', 'address', 'city', 'state', 'zipcode', 'country'];

    /**
     * Mobile number of a country other than the shop's: optional +, 7-15 digits.
     */
    public const INTL_MOBILE = '/^\+?[0-9]{7,15}$/D';

    /**
     * Postal code of a country other than the shop's: 3-10 letters, digits, spaces or dashes.
     */
    public const INTL_POSTAL = '/^[A-Za-z0-9][A-Za-z0-9 \-]{2,9}$/D';

    /**
     * Empty form values, with the default country.
     */
    public static function blank(): array
    {
        return ['country' => self::default_country()] + array_fill_keys(self::FIELDS, '');
    }

    /**
     * The posted form values, trimmed.
     */
    public static function posted($request): array
    {
        $values = [];
        foreach (self::FIELDS as $field) {
            $values[$field] = trim((string) $request->getPost($field));
        }

        return $values;
    }

    /**
     * A mobile number without spaces, dashes or brackets.
     */
    public static function digits(string $mobile): string
    {
        return (string) preg_replace('/[\s\-()]/', '', $mobile);
    }

    /**
     * Whether the Saudi rules (Config\Shop) apply to this country.
     */
    public static function is_home(string $country): bool
    {
        return $country === config(Shop::class)->country;
    }

    /**
     * Whether $mobile is a valid mobile number: any country (optional +, 7-15
     * digits; spaces, dashes and brackets ignored). $country is kept for callers.
     */
    public static function valid_mobile(string $mobile, string $country = ''): bool
    {
        return preg_match(self::INTL_MOBILE, self::digits($mobile)) === 1;
    }

    /**
     * The "invalid mobile" message for $country ($what: e.g. "for the Alternate Mobile").
     */
    public static function mobile_message(string $country, string $what = ''): string
    {
        $what = $what !== '' ? ' ' . $what : '';

        return 'Please enter a valid mobile number' . $what . ' (7 to 15 digits, e.g. +966501234567).';
    }

    /**
     * Add the address rules to a validator (data: the form values, $values).
     * The State must belong to the Country and, when the state has cities,
     * the City to the State.
     */
    public static function rules(FormValidation $v, array $values): void
    {
        $shop    = config(Shop::class);
        $tree    = self::locations();
        $country = (string) ($values['country'] ?? '');
        $states  = $tree[$country] ?? [];
        $cities  = $states[$values['state'] ?? ''] ?? [];
        $home    = self::is_home($country);
        $mobile  = static fn ($value) => $value === '' || self::valid_mobile((string) $value, $country);

        $v->set_rules('full_name', 'Full Name', 'required|max_length[100]|' . FormValidation::PERSON_NAME, ['regex_match' => 'The Full Name may contain only letters, spaces and . \' -']);
        $v->set_rules('mobile', 'Mobile Number', ['required', ['valid_mobile', $mobile]], ['valid_mobile' => self::mobile_message($country)]);
        $v->set_rules('alt_mobile', 'Alternate Mobile', [['valid_mobile', $mobile]], ['valid_mobile' => self::mobile_message($country, 'for the Alternate Mobile') . ' Or leave it empty.']);
        $v->set_rules('landmark', 'Landmark', 'max_length[150]');
        $v->set_rules('address', 'Address', 'required|min_length[5]|max_length[255]');
        $v->set_rules('country', 'Country', ['required', ['valid_country', static fn ($value) => isset($tree[(string) $value])]],
            ['valid_country' => 'Please select your country from the list.']);
        $v->set_rules('state', 'State / Region', ['required', ['valid_state', static fn ($value) => isset($states[(string) $value])]],
            ['valid_state' => 'Please select your state / region from the list.']);
        if ($cities) {
            $v->set_rules('city', 'City', ['required', ['valid_city', static fn ($value) => in_array((string) $value, $cities, true)]],
                ['valid_city' => 'Please select your city from the list.']);
        } else {
            $v->set_rules('city', 'City', "required|max_length[100]|regex_match[/^[\\p{L}][\\p{L} .'\\-]*$/u]", ['regex_match' => 'Please enter a valid city name.']);
        }
        $v->set_rules('zipcode', 'Postal Code', ['required', ['valid_postal', static fn ($value) => preg_match($home ? $shop->postalPattern : self::INTL_POSTAL, (string) $value) === 1]],
            ['valid_postal' => $home ? 'A Saudi postal code has 5 digits, e.g. 32272 (or 32272-1234).' : 'Please enter a valid postal code (3-10 letters or digits).']);
    }

    /**
     * Address values as an address book row (Members\Models\AddressModel::FIELDS).
     */
    public static function to_book(array $values): array
    {
        [$first, $last] = split_name($values['full_name']);

        return [
            'first_name' => mb_substr($first, 0, 50), 'last_name' => mb_substr($last, 0, 50), 'address' => $values['address'],
            'landmark' => $values['landmark'] !== '' ? $values['landmark'] : null, 'mobile' => self::digits($values['mobile']),
            'alt_mobile' => $values['alt_mobile'] !== '' ? self::digits($values['alt_mobile']) : null, 'zipcode' => $values['zipcode'],
            'city' => $values['city'], 'state' => $values['state'], 'country' => $values['country'],
        ];
    }

    /**
     * Form values of an address book row.
     */
    public static function from_book(array $row): array
    {
        return [
            'full_name' => trim($row['first_name'] . ' ' . $row['last_name']), 'mobile' => (string) $row['mobile'],
            'alt_mobile' => (string) ($row['alt_mobile'] ?? ''), 'landmark' => (string) ($row['landmark'] ?? ''),
            'address' => (string) $row['address'], 'city' => (string) $row['city'], 'state' => (string) $row['state'],
            'zipcode' => (string) $row['zipcode'], 'country' => (string) ($row['country'] ?: self::default_country()),
        ];
    }

    /**
     * The Country -> State -> City lists of the form (Sitepanel > Manage
     * Location): [country => [state => [cities]]]; a state without cities
     * has an empty list (the City is then typed). When nothing is set up (or
     * the database can't be read): the shop's country with the regions of
     * Config\Shop and typed cities.
     *
     * @return array<string, array<string, list<string>>>
     */
    public static function locations(): array
    {
        static $tree = null;
        if ($tree !== null) {
            return $tree;
        }

        try {
            $tree = (new CityModel())->get_location_tree();
        } catch (\Throwable $e) {
            log_message('error', 'DeliveryAddress::locations: ' . $e->getMessage());
            $tree = false;
        }
        if (! $tree) {
            $shop = config(Shop::class);
            $tree = [$shop->country => array_fill_keys($shop->regions, [])];
        }

        return $tree;
    }

    /**
     * The preselected country: the shop's country when it is offered, else the first one.
     */
    public static function default_country(): string
    {
        $tree = self::locations();
        $home = config(Shop::class)->country;

        return isset($tree[$home]) ? $home : (string) array_key_first($tree);
    }
}
