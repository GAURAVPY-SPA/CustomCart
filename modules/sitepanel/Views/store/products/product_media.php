<?php
/*
 * Photos of a product ($media in display order, FALSE = not loaded): colour
 * (one of $colors = color_id => name, 0 = every colour), order, the default
 * (main) photo and delete; plus up to four new uploads for one colour.
 * $features = Config\Shop::$productFeatures: the colour fields and the Stock
 * link show only when 'colors' / 'stock' are on.
 */
$on     = function ($feature) use ($features) { return ! empty($features[$feature]); };
$colors = $on('colors') ? $colors : array();
echo sp_view('includes/header'); ?>
<?php echo sp_view('includes/form_errors', array('errors' => $errors)); ?>

<?php echo form_open_multipart('sitepanel/products/media/'.(int) $product['products_id'], array('novalidate' => 'novalidate')); ?>
<section class="sp-panel mb-4" aria-labelledby="media-title">
	<header class="sp-panel-head sp-panel-head--wrap">
		<h2 class="sp-panel-title" id="media-title">Photos of <?php echo html_escape($product['product_name']); ?></h2>
		<div class="sp-row-actions">
			<a class="btn sp-btn-outline" href="<?php echo site_url('sitepanel/products/edit/'.(int) $product['products_id']); ?>">Edit product</a>
			<?php if ($on('stock')): ?>
			<a class="btn sp-btn-outline" href="<?php echo site_url('sitepanel/products/stock/'.(int) $product['products_id']); ?>">Stock</a>
			<?php endif; ?>
		</div>
	</header>
	<p class="sp-muted">The main photo is shown on product cards and first on the product page.<?php if ($on('colors')): ?> A photo tied to a colour is shown when the customer picks that colour<?php echo $colors ? '' : ' (this product has no colours)'; ?>.<?php endif; ?></p>

	<?php if ($media === FALSE): ?>
		<p class="sp-empty mb-0">The photos could not be loaded. Please try again shortly.</p>
	<?php elseif (empty($media)): ?>
		<p class="sp-empty mb-0">No photos yet. The store shows a placeholder until one is added.</p>
	<?php else: ?>
		<div class="sp-table-wrap">
			<table class="sp-table sp-table--stack">
				<caption class="visually-hidden">Product photos in display order</caption>
				<thead>
					<tr>
						<th scope="col">Photo</th>
						<th scope="col">Main</th>
						<?php if ($on('colors')): ?><th scope="col">Colour</th><?php endif; ?>
						<th scope="col">Order</th>
						<th scope="col">Delete</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($media as $n => $row): ?>
					<?php $id = (int) $row['id']; $path = $image_upload->path('products', $row['media']); ?>
					<tr>
						<td data-label="Photo"><?php if ($path !== ''): ?><img class="sp-thumb" src="<?php echo html_escape(get_image('products', $row['media'], 192, 108, 'C')); ?>" alt="Photo <?php echo $n + 1; ?>" width="96" loading="lazy"><?php else: ?><span class="sp-muted small">File missing</span><?php endif; ?></td>
						<td data-label="Main"><input class="form-check-input" type="radio" name="default_id" value="<?php echo $id; ?>"<?php echo $row['is_default'] === 'Y' ? ' checked' : ''; ?> aria-label="Main photo: photo <?php echo $n + 1; ?>"></td>
						<?php if ($on('colors')): ?>
						<td data-label="Colour">
							<select class="form-select form-select-sm" name="color[<?php echo $id; ?>]" aria-label="Colour of photo <?php echo $n + 1; ?>">
								<option value="0">All colours</option>
								<?php foreach ($colors as $cid => $name): ?>
								<option value="<?php echo (int) $cid; ?>"<?php echo (int) $row['color_id'] === (int) $cid ? ' selected' : ''; ?>><?php echo html_escape($name); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
						<?php endif; ?>
						<td data-label="Order"><input class="form-control form-control-sm" style="max-width:5.5rem" type="number" min="0" max="9999" name="sort[<?php echo $id; ?>]" value="<?php echo (int) $row['sort_order']; ?>" aria-label="Order of photo <?php echo $n + 1; ?>"></td>
						<td data-label="Delete"><input class="form-check-input" type="checkbox" name="delete[]" value="<?php echo $id; ?>" aria-label="Delete photo <?php echo $n + 1; ?>"></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>
</section>

<?php if ($editPrvg && is_array($media) && count($media) < $max_photos): ?>
<section class="sp-panel mb-4" aria-labelledby="upload-title">
	<header class="sp-panel-head"><h2 class="sp-panel-title" id="upload-title">Add photos</h2></header>
	<div class="row g-3">
		<?php for ($n = 1; $n <= 4; $n++): ?>
		<div class="col-12 col-md-6 col-lg-3">
			<label class="form-label" for="new_photo_<?php echo $n; ?>">New photo <?php echo $n; ?></label>
			<input class="form-control" type="file" id="new_photo_<?php echo $n; ?>" name="new_photo_<?php echo $n; ?>" accept=".jpg,.jpeg,.png,.gif,.webp,image/jpeg,image/png,image/gif,image/webp">
		</div>
		<?php endfor; ?>
		<?php if ($colors): ?>
		<div class="col-12 col-lg-4">
			<label class="form-label" for="new_color">Colour of the new photos</label>
			<select class="form-select" id="new_color" name="new_color">
				<option value="0">All colours</option>
				<?php foreach ($colors as $cid => $name): ?>
				<option value="<?php echo (int) $cid; ?>"><?php echo html_escape($name); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<?php endif; ?>
		<div class="col-12"><p class="form-text mb-0">Up to <?php echo (int) $max_photos; ?> photos per product. JPG, PNG, GIF or WebP, up to 2 MB and <?php echo (int) $max_size[0].' × '.(int) $max_size[1]; ?> pixels; square or 4:3 photos fit the store best.</p></div>
	</div>
</section>
<?php endif; ?>

<?php if ($editPrvg && is_array($media)): ?>
<div class="sp-form-actions mb-4">
	<button class="btn sp-btn-primary" type="submit">Save photos</button>
	<a class="btn sp-btn-outline" href="<?php echo site_url('sitepanel/products'); ?>">Back to products</a>
</div>
<?php endif; ?>
<?php echo form_close(); ?>
<?php echo sp_view('includes/footer'); ?>
