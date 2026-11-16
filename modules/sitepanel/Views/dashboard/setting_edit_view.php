<?php
/**
 * Admin Settings form (the reference's dashboard/setting_edit_view).
 * $settings = stored values; $admin_email = the signed-in admin's
 * tbl_admin.admin_email. On a failed save the submitted values are shown
 * again via set_value() (which escapes output); the password never is.
 */
echo sp_view('includes/header');
$field = function ($key) use ($settings) {
	return set_value($key, (string) $settings[$key]);
};
?>
<?php if ($errors): ?>
<div class="alert alert-danger" role="alert">
	<p class="mb-1 fw-semibold">Settings were not saved:</p>
	<ul class="mb-0">
		<?php foreach ($errors as $error): ?>
		<li><?php echo html_escape($error); ?></li>
		<?php endforeach; ?>
	</ul>
</div>
<?php endif; ?>

<?php if ( ! $can_edit): ?>
<div class="alert alert-info" role="status">You can view these settings but not change them.</div>
<?php endif; ?>

<?php echo form_open('sitepanel/settings', array('novalidate' => 'novalidate')); ?>
<fieldset<?php echo $can_edit ? '' : ' disabled'; ?>>

	<section class="sp-panel mb-4" aria-labelledby="set-admin">
		<header class="sp-panel-head"><h2 class="sp-panel-title" id="set-admin">Admin account</h2></header>
		<div class="row g-3">
			<div class="col-12 col-lg-6">
				<label class="form-label" for="admin_email">Admin email <span class="text-danger">*</span></label>
				<input class="form-control" type="email" id="admin_email" name="admin_email" maxlength="191" required autocomplete="off" value="<?php echo set_value('admin_email', $admin_email); ?>">
				<div class="form-text">Your Sitepanel account's email address. Password reset links from “Forgot password?” on the login page are sent to it.</div>
			</div>
			<div class="col-12 col-lg-6">
				<label class="form-label" for="current_password">Current password</label>
				<?php /* autocomplete="new-password": stops browsers filling a saved login into this form. */ ?>
				<input class="form-control" type="password" id="current_password" name="current_password" maxlength="255" autocomplete="new-password">
				<div class="form-text">Needed only when you change the admin email.</div>
			</div>
		</div>
	</section>

	<section class="sp-panel mb-4" aria-labelledby="set-identity">
		<header class="sp-panel-head"><h2 class="sp-panel-title" id="set-identity">Website identity</h2></header>
		<div class="row g-3">
			<div class="col-12 col-lg-6">
				<label class="form-label" for="site_name">Site name <span class="text-danger">*</span></label>
				<input class="form-control" type="text" id="site_name" name="site_name" maxlength="150" required value="<?php echo $field('site_name'); ?>">
				<div class="form-text">Shown on the home page, footer, login page and logo alt text.</div>
			</div>
			<div class="col-12 col-lg-6">
				<label class="form-label" for="site_title">Website title <span class="text-danger">*</span></label>
				<input class="form-control" type="text" id="site_title" name="site_title" maxlength="150" required value="<?php echo $field('site_title'); ?>">
				<div class="form-text">Used in browser tab titles, e.g. “Dashboard | <em>title</em>”.</div>
			</div>
			<?php /* "Tagline / description" hidden: no public page displays it. The stored value is posted back
			   unchanged, because Settings::save() writes every key. Restore the field from the comment below to show it again:
			   <div class="col-12"><label class="form-label" for="site_tagline">Tagline / description</label>
			   <textarea class="form-control" id="site_tagline" name="site_tagline" rows="2" maxlength="300">…</textarea></div> */ ?>
			<input type="hidden" name="site_tagline" value="<?php echo $field('site_tagline'); ?>">
		</div>
	</section>

	<section class="sp-panel mb-4" aria-labelledby="set-contact">
		<header class="sp-panel-head"><h2 class="sp-panel-title" id="set-contact">Contact information</h2></header>
		<div class="row g-3">
			<div class="col-12 col-lg-6">
				<label class="form-label" for="contact_email">Contact email</label>
				<input class="form-control" type="email" id="contact_email" name="contact_email" maxlength="191" value="<?php echo $field('contact_email'); ?>">
				<div class="form-text">Contact Us page: "Solutions &amp; Scoping".</div>
			</div>
			<div class="col-12 col-lg-6">
				<label class="form-label" for="contact_phone">Contact phone</label>
				<input class="form-control" type="tel" id="contact_phone" name="contact_phone" maxlength="30" value="<?php echo $field('contact_phone'); ?>">
				<div class="form-text">Contact Us page: "Direct Line" (click to call).</div>
			</div>
			<div class="col-12">
				<label class="form-label" for="contact_address">Contact address</label>
				<textarea class="form-control" id="contact_address" name="contact_address" rows="2" maxlength="500"><?php echo $field('contact_address'); ?></textarea>
				<div class="form-text">Contact Us page: "Address / Location"; also the company address on printed invoices.</div>
			</div>
			<div class="col-12 col-lg-6">
				<label class="form-label" for="office_hours">Office hours</label>
				<input class="form-control" type="text" id="office_hours" name="office_hours" maxlength="150" value="<?php echo $field('office_hours'); ?>">
				<div class="form-text">Contact Us page: "Working Hours", e.g. Monday – Friday: 9:00 AM – 6:00 PM (SGT / UTC+8).</div>
			</div>
			<div class="col-12 col-lg-6">
				<label class="form-label" for="company_license">Registered entity</label>
				<input class="form-control" type="text" id="company_license" name="company_license" maxlength="150" value="<?php echo $field('company_license'); ?>">
				<div class="form-text">Company registration details, e.g. UEN 201912345K (kept for reference; not shown on the website).</div>
			</div>
			<?php
			$social_links = array(
				'twitter_link'   => 'Twitter Link',
				'linkedin_link'  => 'LinkedIn Link',
			);
			/* Hidden for now (Facebook, YouTube, Instagram): their stored values are posted back unchanged,
			   because Settings::save() writes every key. Move a key back into $social_links to show it again. */
			$hidden_links = array('facebook_link', 'youtube_link', 'instagram_link');
			?>
			<?php foreach ($hidden_links as $key): ?>
			<input type="hidden" name="<?php echo $key; ?>" value="<?php echo $field($key); ?>">
			<?php endforeach; ?>
			<?php foreach ($social_links as $key => $label): ?>
			<div class="col-12 col-lg-6">
				<label class="form-label" for="<?php echo $key; ?>"><?php echo $label; ?></label>
				<input class="form-control" type="url" id="<?php echo $key; ?>" name="<?php echo $key; ?>" maxlength="250" value="<?php echo $field($key); ?>">
			</div>
			<?php endforeach; ?>
			<div class="col-12 col-lg-6">
				<label class="form-label" for="google_analytics_id">Google Analytics ID</label>
				<input class="form-control" type="text" id="google_analytics_id" name="google_analytics_id" maxlength="50" value="<?php echo $field('google_analytics_id'); ?>">
				<div class="form-text">For example <code>G-XXXXXXXXXX</code>.</div>
			</div>
			<div class="col-12">
				<label class="form-label" for="google_web_code">Google Web Code</label>
				<textarea class="form-control" id="google_web_code" name="google_web_code" rows="4" maxlength="10000" spellcheck="false"><?php echo $field('google_web_code'); ?></textarea>
				<div class="form-text">Stored as entered; it is not run inside the sitepanel.</div>
			</div>
			<div class="col-12">
				<label class="form-label" for="map_code">Contactus Page Map Code</label>
				<textarea class="form-control" id="map_code" name="map_code" rows="4" maxlength="10000" spellcheck="false"><?php echo $field('map_code'); ?></textarea>
				<div class="form-text">Google Maps embed for the Contact Us page: the <code>&lt;iframe&gt;</code> code from Google Maps › Share › Embed a map, or its <code>https://www.google.com/maps…</code> link. Only the map link is used; other code is refused.</div>
			</div>
			<div class="col-12 col-lg-6">
				<label class="form-label" for="whatsapp_no">Whatsapp Number</label>
				<input class="form-control" type="text" id="whatsapp_no" name="whatsapp_no" maxlength="15" inputmode="tel" value="<?php echo $field('whatsapp_no'); ?>">
				<div class="form-text">Country code and number, digits only, e.g. <code>+971501234567</code>.</div>
			</div>
			<div class="col-12 col-lg-6">
				<label class="form-label" for="whatsapp_text">Whatsapp Text</label>
				<input class="form-control" type="text" id="whatsapp_text" name="whatsapp_text" maxlength="500" value="<?php echo $field('whatsapp_text'); ?>">
				<div class="form-text">Message pre-filled when a visitor opens WhatsApp.</div>
			</div>
		</div>
	</section>

	<section class="sp-panel mb-4" aria-labelledby="set-shop">
		<header class="sp-panel-head"><h2 class="sp-panel-title" id="set-shop">Online store</h2></header>
		<p class="form-text mt-0">Delivery charge and free delivery amount: <a href="<?php echo site_url('sitepanel/shipping'); ?>">Manage Shipping</a> (the default delivery method).</p>
		<div class="row g-3">
			<?php /* Moved to Sitepanel > Manage Shipping (default delivery method: rate and "free above").
			<div class="col-12 col-md-6 col-lg-4">
				<label class="form-label" for="shipping_charges">Shipping Charges (<?php echo display_symbol(); ?>) <span class="text-danger">*</span></label>
				<input class="form-control" type="text" inputmode="decimal" id="shipping_charges" name="shipping_charges" maxlength="10" required value="<?php echo $field('shipping_charges'); ?>">
				<div class="form-text">Delivery charge added to every order below the free delivery amount. 0.00 = free delivery for all orders.</div>
			</div>
			<div class="col-12 col-md-6 col-lg-4">
				<label class="form-label" for="free_delivery_above">Free Delivery Above (<?php echo display_symbol(); ?>) <span class="text-danger">*</span></label>
				<input class="form-control" type="text" inputmode="decimal" id="free_delivery_above" name="free_delivery_above" maxlength="10" required value="<?php echo $field('free_delivery_above'); ?>">
				<div class="form-text">Delivery is free when the cart subtotal reaches this amount. 0.00 = no free delivery.</div>
			</div>
			*/ ?>
			<div class="col-12 col-md-6 col-lg-4">
				<label class="form-label" for="gst_percent">VAT Percentage (%) <span class="text-danger">*</span></label>
				<input class="form-control" type="text" inputmode="decimal" id="gst_percent" name="gst_percent" maxlength="6" required value="<?php echo $field('gst_percent'); ?>">
				<div class="form-text">VAT added on top of product prices and delivery at checkout (0–100; Saudi Arabia: 15). New orders only: placed orders keep the rate they were placed with.</div>
			</div>
			<?php /* Low Stock Threshold hidden (the saved value, else 5, is still used for the "Low" stock badges):
			<div class="col-12 col-md-6 col-lg-4">
				<label class="form-label" for="low_stock_threshold">Low Stock Threshold <span class="text-danger">*</span></label>
				<input class="form-control" type="text" inputmode="numeric" id="low_stock_threshold" name="low_stock_threshold" maxlength="6" required value="<?php echo $field('low_stock_threshold'); ?>">
				<div class="form-text">A product (or colour / size option) with this many units or fewer is shown as low stock in Manage Products.</div>
			</div>
			*/ ?>
		</div>
	</section>

	<section class="sp-panel mb-4" aria-labelledby="set-general">
		<header class="sp-panel-head"><h2 class="sp-panel-title" id="set-general">General</h2></header>
		<div class="row g-3">
			<div class="col-12 col-lg-6">
				<label class="form-label" for="timezone">Time zone <span class="text-danger">*</span></label>
				<select class="form-select" id="timezone" name="timezone" required>
					<?php $selected_tz = set_value('timezone', (string) $settings['timezone'], FALSE); ?>
					<?php foreach ($timezones as $tz): ?>
					<option value="<?php echo html_escape($tz); ?>"<?php echo $tz === $selected_tz ? ' selected' : ''; ?>><?php echo html_escape($tz); ?></option>
					<?php endforeach; ?>
				</select>
				<div class="form-text">Used for all dates and times shown on the website and sitepanel.</div>
			</div>
			<div class="col-12 col-lg-6">
				<span class="form-label d-block">Maintenance mode</span>
				<?php $maintenance = service('request')->getMethod() === 'POST' ? service('request')->getPost('maintenance_mode') === '1' : $settings['maintenance_mode'] === '1'; ?>
				<div class="form-check form-switch">
					<input class="form-check-input" type="checkbox" role="switch" id="maintenance_mode" name="maintenance_mode" value="1"<?php echo $maintenance ? ' checked' : ''; ?>>
					<label class="form-check-label" for="maintenance_mode">Show a maintenance notice instead of the public website</label>
				</div>
				<div class="form-text">The sitepanel stays available.</div>
			</div>
		</div>
	</section>

	<?php if ($can_edit): ?>
	<div class="sp-form-actions mb-4">
		<button class="btn sp-btn-primary" type="submit">Save settings</button>
		<a class="btn sp-btn-outline" href="<?php echo site_url('sitepanel/settings'); ?>">Cancel</a>
	</div>
	<?php endif; ?>
</fieldset>
<?php echo form_close(); ?>
<?php echo sp_view('includes/footer'); ?>
