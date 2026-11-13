<?php
/*
 * Stock matrix of a product: one row per colour × size ($rows, with
 * color_name/color_code/size_name; FALSE = not loaded). Empty or 0 prices
 * use the product's own prices. On a failed save the posted values are shown.
 */
use Modules\Sitepanel\Controllers\Products;

$posted = function ($name, $sid, $stored) {
	$values = service('request')->getMethod() === 'POST' ? service('request')->getPost($name) : NULL;
	return html_escape(is_array($values) && isset($values[$sid]) ? (string) $values[$sid] : (string) $stored);
};
$price_value = function ($stored) { return (float) $stored > 0 ? number_format((float) $stored, 2, '.', '') : ''; };
echo sp_view('includes/header'); ?>
<?php echo sp_view('includes/form_errors', array('errors' => $errors)); ?>

<section class="sp-panel mb-4" aria-labelledby="stock-title">
	<header class="sp-panel-head sp-panel-head--wrap">
		<h2 class="sp-panel-title" id="stock-title">Stock of <?php echo html_escape($product['product_name']); ?></h2>
		<div class="sp-row-actions">
			<a class="btn sp-btn-outline" href="<?php echo site_url('sitepanel/products/edit/'.(int) $product['products_id']); ?>">Edit product</a>
			<a class="btn sp-btn-outline" href="<?php echo site_url('sitepanel/products/media/'.(int) $product['products_id']); ?>">Photos</a>
		</div>
	</header>
	<p class="sp-muted">Product price <?php echo html_escape(display_price($product['product_price'])); ?><?php if ((float) $product['product_discounted_price'] > 0): ?>, discounted <?php echo html_escape(display_price($product['product_discounted_price'])); ?><?php endif; ?>. Leave an option's prices empty to use these. <strong>Available</strong> is what can still be ordered: orders take units from it and cancelled items give them back. An option at or under its <strong>low-stock</strong> level is flagged in the product list.</p>

	<?php if ($rows === FALSE): ?>
		<p class="sp-empty mb-0">The stock could not be loaded. Please try again shortly.</p>
	<?php elseif (empty($rows)): ?>
		<p class="sp-empty mb-0">This product has no stock rows. Save the product once to create them.</p>
	<?php else: ?>
	<?php echo form_open('sitepanel/products/stock/'.(int) $product['products_id'], array('novalidate' => 'novalidate')); ?>
		<div class="sp-table-wrap">
			<table class="sp-table sp-table--stack">
				<caption class="visually-hidden">Stock and prices per option</caption>
				<thead>
					<tr>
						<th scope="col">Option</th>
						<th scope="col">Price</th>
						<th scope="col">Discounted price</th>
						<th scope="col">Available</th>
						<th scope="col">Sold</th>
						<th scope="col">Low-stock level</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($rows as $row): ?>
					<?php $sid = (int) $row['stock_id']; $label = Products::variant_label($row); $qty = (int) $row['product_quantity']; ?>
					<tr>
						<td class="sp-cell-text" data-label="Option">
							<?php if ($row['color_code'] !== NULL): ?><span class="d-inline-block rounded-circle border align-middle me-1" style="width:16px;height:16px;background:<?php echo html_escape($row['color_code']); ?>" aria-hidden="true"></span><?php endif; ?>
							<?php echo html_escape($label); ?>
							<?php if ($qty <= 0): ?><span class="sp-badge sp-badge--error d-inline-block ms-1">Out</span><?php elseif ($qty <= ((int) $row['inventory'] > 0 ? (int) $row['inventory'] : service('siteSettings')->low_stock_threshold())): ?><span class="sp-badge sp-badge--muted d-inline-block ms-1">Low</span><?php endif; ?>
						</td>
						<td data-label="Price"><input class="form-control form-control-sm" type="text" inputmode="decimal" maxlength="11" name="price[<?php echo $sid; ?>]" value="<?php echo $posted('price', $sid, $price_value($row['product_price'])); ?>" placeholder="<?php echo html_escape(number_format((float) $product['product_price'], 2, '.', '')); ?>" aria-label="Price of <?php echo html_escape($label); ?>"></td>
						<td data-label="Discounted price"><input class="form-control form-control-sm" type="text" inputmode="decimal" maxlength="11" name="discounted[<?php echo $sid; ?>]" value="<?php echo $posted('discounted', $sid, $price_value($row['product_discounted_price'])); ?>" placeholder="<?php echo (float) $product['product_discounted_price'] > 0 ? html_escape(number_format((float) $product['product_discounted_price'], 2, '.', '')) : ''; ?>" aria-label="Discounted price of <?php echo html_escape($label); ?>"></td>
						<td data-label="Available"><input class="form-control form-control-sm" type="number" min="0" max="999999" name="quantity[<?php echo $sid; ?>]" value="<?php echo $posted('quantity', $sid, $qty); ?>" aria-label="Available quantity of <?php echo html_escape($label); ?>"></td>
						<td data-label="Sold"><?php echo (int) $row['used_quantity']; ?></td>
						<td data-label="Low-stock level"><input class="form-control form-control-sm" type="number" min="0" max="999999" name="inventory[<?php echo $sid; ?>]" value="<?php echo $posted('inventory', $sid, (int) $row['inventory']); ?>" aria-label="Low-stock level of <?php echo html_escape($label); ?>"></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php if ($editPrvg): ?>
		<div class="sp-form-actions mt-3">
			<button class="btn sp-btn-primary" type="submit">Save stock</button>
			<a class="btn sp-btn-outline" href="<?php echo site_url('sitepanel/products'); ?>">Back to products</a>
		</div>
		<?php endif; ?>
	<?php echo form_close(); ?>
	<?php endif; ?>
</section>
<?php echo sp_view('includes/footer'); ?>
