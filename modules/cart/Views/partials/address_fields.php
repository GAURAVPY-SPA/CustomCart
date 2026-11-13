<?php
/*
| Delivery address fields (design: the address form of guest-checkout.htm,
| checkout.htm Add New Address and address.htm); rules:
| Modules\Cart\Libraries\DeliveryAddress. $values (DeliveryAddress::FIELDS),
| $locations (DeliveryAddress::locations(): country => state => its cities),
| $id_prefix. Country -> State -> City: the State list follows the Country
| ([data-kt-state-for]) and the City list the State ([data-kt-city-for]),
| customcart.js; a state without cities gets a typed City.
*/
$p = isset($id_prefix) ? $id_prefix : 'ktGa';
$v = function ($key) use ($values) { return html_escape((string) ($values[$key] ?? '')); };
?>
<div class="row kt-g-ctf">
<div class="col-12 col-md-6"><label for="<?php echo $p; ?>Name" class="kt-ct-label fw-bold">Full Name <span class="text-danger">*</span></label><input type="text" id="<?php echo $p; ?>Name" name="full_name" required maxlength="100" autocomplete="name" placeholder="Full name" class="form-control kt-input rounded-pill" value="<?php echo $v('full_name'); ?>"></div>
<div class="col-12 col-md-6"><label for="<?php echo $p; ?>Mobile" class="kt-ct-label fw-bold">Mobile Number <span class="text-danger">*</span></label><input type="tel" id="<?php echo $p; ?>Mobile" name="mobile" required maxlength="20" autocomplete="tel" placeholder="+966 5X XXX XXXX" class="form-control kt-input rounded-pill" value="<?php echo $v('mobile'); ?>"></div>
<div class="col-12 col-md-6"><label for="<?php echo $p; ?>Alt" class="kt-ct-label fw-bold">Alternate Mobile <span class="kt-ct-opt">(optional)</span></label><input type="tel" id="<?php echo $p; ?>Alt" name="alt_mobile" maxlength="20" placeholder="+966 5X XXX XXXX" class="form-control kt-input rounded-pill" value="<?php echo $v('alt_mobile'); ?>"></div>
<div class="col-12 col-md-6"><label for="<?php echo $p; ?>Landmark" class="kt-ct-label fw-bold">Landmark <span class="kt-ct-opt">(optional)</span></label><input type="text" id="<?php echo $p; ?>Landmark" name="landmark" maxlength="150" placeholder="Near mall, mosque, etc." class="form-control kt-input rounded-pill" value="<?php echo $v('landmark'); ?>"></div>
<div class="col-12"><label for="<?php echo $p; ?>Address" class="kt-ct-label fw-bold">Address <span class="text-danger">*</span></label><textarea id="<?php echo $p; ?>Address" name="address" rows="2" required maxlength="255" autocomplete="street-address" placeholder="Building, street, district" class="form-control kt-textarea"><?php echo $v('address'); ?></textarea></div>
<?php
$country    = (string) ($values['country'] ?? '');
$state      = (string) ($values['state'] ?? '');
$city       = (string) ($values['city'] ?? '');
$states     = isset($locations[$country]) ? $locations[$country] : array();
$cities     = isset($states[$state]) ? $states[$state] : array();
$city_typed = isset($states[$state]) && !$cities; /* a state without cities: the City is typed */
if ($cities && $city !== '' && !in_array($city, $cities, TRUE)) { $cities[] = $city; } /* a saved city no longer listed: shown, then rejected on save */
?>
<div class="col-12 col-md-6"><label for="<?php echo $p; ?>Country" class="kt-ct-label fw-bold">Country <span class="text-danger">*</span></label><select id="<?php echo $p; ?>Country" name="country" required autocomplete="country-name" class="form-select kt-input kt-select rounded-pill" data-kt-locations="<?php echo html_escape(json_encode($locations, JSON_UNESCAPED_UNICODE)); ?>">
<?php if (count($locations) > 1): ?><option value="">Select country</option><?php endif; ?>
<?php foreach (array_keys($locations) as $name): ?>
<option value="<?php echo html_escape($name); ?>"<?php echo $country === (string) $name ? ' selected' : ''; ?>><?php echo html_escape($name); ?></option>
<?php endforeach; ?>
</select></div>
<div class="col-12 col-md-6" data-kt-state-for="<?php echo $p; ?>Country"><label for="<?php echo $p; ?>State" class="kt-ct-label fw-bold">State / Region <span class="text-danger">*</span></label><select id="<?php echo $p; ?>State" name="state" required autocomplete="address-level1" class="form-select kt-input kt-select rounded-pill">
<option value=""><?php echo $states ? 'Select state / region' : 'Select country first'; ?></option>
<?php foreach (array_keys($states) as $name): ?>
<option value="<?php echo html_escape($name); ?>"<?php echo $state === (string) $name ? ' selected' : ''; ?>><?php echo html_escape($name); ?></option>
<?php endforeach; ?>
</select></div>
<div class="col-12 col-md-6" data-kt-city-for="<?php echo $p; ?>State"><label for="<?php echo $p; ?>City" class="kt-ct-label fw-bold">City <span class="text-danger">*</span></label>
<select id="<?php echo $p; ?>City" name="city" required autocomplete="address-level2" class="form-select kt-input kt-select rounded-pill"<?php echo $city_typed ? ' hidden disabled' : ''; ?>>
<option value=""><?php echo isset($states[$state]) ? 'Select city' : 'Select state first'; ?></option>
<?php foreach ($cities as $name): ?>
<option value="<?php echo html_escape($name); ?>"<?php echo $city === $name ? ' selected' : ''; ?>><?php echo html_escape($name); ?></option>
<?php endforeach; ?>
</select>
<input type="text" id="<?php echo $p; ?>CityText" name="city" required maxlength="100" autocomplete="address-level2" placeholder="City" aria-label="City" class="form-control kt-input rounded-pill" value="<?php echo $city_typed ? html_escape($city) : ''; ?>"<?php echo $city_typed ? '' : ' hidden disabled'; ?>></div>
<div class="col-12 col-md-6"><label for="<?php echo $p; ?>Zip" class="kt-ct-label fw-bold">PIN / Postal Code <span class="text-danger">*</span></label><input type="text" id="<?php echo $p; ?>Zip" name="zipcode" required maxlength="10" autocomplete="postal-code" placeholder="e.g. 32272" class="form-control kt-input rounded-pill" value="<?php echo $v('zipcode'); ?>"></div>
</div>
