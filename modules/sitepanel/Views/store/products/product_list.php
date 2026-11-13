<?php
/*
 * Products: $filters = keyword, status, category, brand, stock ('low' | 'out');
 * $categories = id => path name; $brands = id => row; $low_stock = ids with
 * an option at or under its low-stock threshold; $product_categories =
 * products_id => its category ids (main first); $variant_ids = products with
 * colour / size options; $features = Config\Shop::$productFeatures.
 */
$filtered = (bool) array_filter($filters, 'strlen');
$on       = function ($feature) use ($features) { return ! empty($features[$feature]); };
echo sp_view('includes/header'); ?>
<section class="sp-panel mb-4" aria-labelledby="list-title">
	<header class="sp-panel-head sp-panel-head--wrap">
		<h2 class="sp-panel-title" id="list-title">Products</h2>
		<?php if ($addPrvg): ?>
		<a class="btn sp-btn-primary" href="<?php echo site_url('sitepanel/products/add'.($filters['category'] !== '' ? '?category='.(int) $filters['category'] : '')); ?>"><?php echo sp_icon('plus'); ?> Add product</a>
		<?php endif; ?>
	</header>

	<form class="sp-filters" method="get" action="<?php echo site_url('sitepanel/products'); ?>" role="search" aria-label="Search products">
		<div>
			<label class="form-label" for="filter-keyword">Name or code</label>
			<input class="form-control" type="search" id="filter-keyword" name="keyword" maxlength="80" value="<?php echo html_escape($filters['keyword']); ?>">
		</div>
		<div>
			<label class="form-label" for="filter-category">Category</label>
			<select class="form-select" id="filter-category" name="category">
				<option value="">All</option>
				<?php foreach ($categories as $id => $label): ?>
				<option value="<?php echo (int) $id; ?>"<?php echo (string) $id === (string) $filters['category'] ? ' selected' : ''; ?>><?php echo html_escape($label); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<?php if ($on('brand')): ?>
		<div>
			<label class="form-label" for="filter-brand">Brand</label>
			<select class="form-select" id="filter-brand" name="brand">
				<option value="">All</option>
				<?php foreach ($brands as $id => $brand): ?>
				<option value="<?php echo (int) $id; ?>"<?php echo (string) $id === (string) $filters['brand'] ? ' selected' : ''; ?>><?php echo html_escape($brand['brand_name']); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<?php endif; ?>
		<?php if ($on('stock')): ?>
		<div>
			<label class="form-label" for="filter-stock">Stock</label>
			<select class="form-select" id="filter-stock" name="stock">
				<option value="">All</option>
				<option value="low"<?php echo $filters['stock'] === 'low' ? ' selected' : ''; ?>>Low stock</option>
				<option value="out"<?php echo $filters['stock'] === 'out' ? ' selected' : ''; ?>>Out of stock</option>
			</select>
		</div>
		<?php endif; ?>
		<div>
			<label class="form-label" for="filter-status">Status</label>
			<select class="form-select" id="filter-status" name="status">
				<option value="">All</option>
				<option value="1"<?php echo $filters['status'] === '1' ? ' selected' : ''; ?>>Active</option>
				<option value="0"<?php echo $filters['status'] === '0' ? ' selected' : ''; ?>>Inactive</option>
			</select>
		</div>
		<div class="sp-filters-actions">
			<button class="btn sp-btn-primary" type="submit">Search</button>
			<?php if ($filtered): ?><a class="btn sp-btn-outline" href="<?php echo site_url('sitepanel/products'); ?>">Clear</a><?php endif; ?>
		</div>
		<div>
			<label class="form-label" for="filter-pagesize">Records per page</label>
			<select class="form-select" id="filter-pagesize" name="pagesize">
				<?php foreach (Modules\Sitepanel\Controllers\Products::PAGE_SIZES as $size): ?>
				<option value="<?php echo $size; ?>"<?php echo $pagesize === $size ? ' selected' : ''; ?>><?php echo $size; ?></option>
				<?php endforeach; ?>
			</select>
		</div>
	</form>

	<?php if ($res === FALSE): ?>
		<p class="sp-empty mb-0">Products could not be loaded. Please try again shortly.</p>
	<?php elseif (empty($res)): ?>
		<p class="sp-empty mb-0"><?php echo $total ? 'No products on this page.' : ($filtered ? 'No products match your search.' : 'No products yet.'); ?></p>
	<?php else: ?>
		<?php $bulk_enabled = $activatePrvg || $deactivatePrvg || $deletePrvg; ?>
		<?php $bulk = array('key' => 'products', 'base' => 'sitepanel/products', 'page' => $page, 'noun' => 'products', 'filters' => array('keyword' => $filters['keyword'], 'status_filter' => $filters['status'], 'category' => $filters['category'], 'brand' => $filters['brand'], 'stock' => $filters['stock'], 'pagesize' => $filters['pagesize']), 'can_activate' => $activatePrvg, 'can_deactivate' => $deactivatePrvg, 'can_delete' => $deletePrvg, 'set_as_options' => \Modules\Sitepanel\Controllers\Products::BULK_SET_AS, 'unset_as_options' => \Modules\Sitepanel\Controllers\Products::BULK_UNSET_AS, 'can_set_recently_added' => $editPrvg, 'can_unset_recently_added' => $editPrvg); ?>
		<?php echo sp_view('includes/bulk_actions', array('bulk' => $bulk)); ?>
		<div class="sp-table-wrap">
			<table class="sp-table sp-table--stack">
				<caption class="visually-hidden">Products, newest first</caption>
				<thead>
					<tr>
						<?php if ($bulk_enabled): ?><th scope="col"><input type="checkbox" aria-label="Select all products" data-sp-select-all="products" form="sp-bulk-products"></th><?php endif; ?>
						<th scope="col">Photo</th>
						<th scope="col">Product</th>
						<th scope="col">Category</th>
						<th scope="col">Price</th>
						<?php if ($on('stock')): ?><th scope="col">Stock</th><?php endif; ?>
						<th scope="col">Status</th>
						<th scope="col"><span class="visually-hidden">Actions</span></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($res as $row): ?>
					<?php $id = (int) $row['products_id']; $image = $image_upload->path('products', $row['image']); $stock = (int) $row['product_quantity']; ?>
					<tr>
						<?php if ($bulk_enabled): ?><td data-label="Select"><input type="checkbox" name="ids[]" value="<?php echo $id; ?>" aria-label="Select <?php echo html_escape($row['product_name']); ?>" data-sp-select="products" form="sp-bulk-products"></td><?php endif; ?>
						<td data-label="Photo"><?php if ($image !== ''): ?><img class="sp-thumb" src="<?php echo html_escape(get_image('products', $row['image'], 192, 108, 'C')); ?>" alt="" width="64" loading="lazy"><?php else: ?><span class="sp-muted small">None</span><?php endif; ?></td>
						<td class="sp-cell-text" data-label="Product">
							<?php echo html_escape($row['product_name']); ?>
							<span class="sp-muted d-block small"><code><?php echo html_escape($row['product_code']); ?></code><?php echo $on('brand') && $row['brand_name'] !== NULL ? ' · '.html_escape($row['brand_name']) : ''; ?></span>
							<?php if ($row['is_recently_added'] === '1'): ?><span class="sp-muted d-block small"><strong class="text-danger">Recently Added :</strong> <span class="sp-badge sp-badge--ok">✓</span></span><?php endif; ?>
						</td>
						<td data-label="Category">
							<?php foreach ($product_categories[$id] ?? array((int) $row['category_id']) as $n => $category_id): 
								?>
							<span class="d-block<?php echo $n > 0 ? ' sp-muted small' : ''; ?>"><?php echo html_escape(isset($categories[$category_id]) ? preg_replace('/ \(inactive\)$/', '', $categories[$category_id]) : (string) $row['category_name']); ?></span>
							<?php endforeach; ?>
						</td>
						<td data-label="Price">
							<?php echo html_escape(display_price($row['display_price'])); ?>
							<?php if ((float) $row['display_regular_price'] > (float) $row['display_price']): ?><del class="sp-muted d-block small"><?php echo html_escape(display_price($row['display_regular_price'])); ?></del><?php endif; ?>
						</td>
						<?php if ($on('stock')): ?>
						<td data-label="Stock">
							<?php echo $stock; ?>
							<?php if ($stock <= 0): ?><span class="sp-badge sp-badge--error d-inline-block ms-1">Out Of Stock</span><?php elseif (in_array($id, $low_stock, TRUE)): ?><span class="sp-badge sp-badge--muted d-inline-block ms-1">Low</span><?php endif; ?>
						</td>
						<?php endif; ?>
						<td data-label="Status">
							<?php if ($row['status'] === '1'): ?><span class="sp-badge sp-badge--ok">Active</span><?php else: ?><span class="sp-badge sp-badge--muted">Inactive</span><?php endif; ?>
						</td>
						<td data-label="Actions">
							<?php echo sp_view('includes/row_actions', array('a' => array(
								'base' => 'sitepanel/products', 'id' => $id, 'page' => $page,
								'label' => $row['product_name'], 'active' => $row['status'] === '1',
							))); ?>
							<div class="sp-row-actions mb-2 mt-2">
								<?php if ($on('stock')): ?>
								<a class="btn sp-btn-outline" href="<?php echo site_url('sitepanel/products/stock/'.$id); ?>">Stock<span class="visually-hidden"> of <?php echo html_escape($row['product_name']); ?></span></a>
								<?php endif; ?>
								<a class="btn sp-btn-outline" href="<?php echo site_url('sitepanel/products/media/'.$id); ?>">Photos<span class="visually-hidden"> of <?php echo html_escape($row['product_name']); ?></span></a>
								<a class="btn sp-btn-outline" href="<?php echo site_url('sitepanel/products/related/'.$id); ?>">Related<span class="visually-hidden"> products of <?php echo html_escape($row['product_name']); ?></span></a>
								<?php if ($on('reviews')): ?>
								<a class="btn sp-btn-outline" href="<?php echo site_url('sitepanel/reviews?product='.$id); ?>">Reviews<span class="visually-hidden"> of <?php echo html_escape($row['product_name']); ?></span></a>
								<?php endif; ?>
							</div>
							
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php echo sp_view('includes/bulk_actions', array('bulk' => array('position' => 'bottom') + $bulk)); ?>
	<?php endif; ?>
	<?php if ($res): ?>
	<?php echo sp_view('includes/list_foot', array('foot' => array('total' => $total, 'noun' => $total === 1 ? 'product' : 'products', 'pagination' => $page_links))); ?>
	<?php endif; ?>
</section>
<?php echo sp_view('includes/footer'); ?>
