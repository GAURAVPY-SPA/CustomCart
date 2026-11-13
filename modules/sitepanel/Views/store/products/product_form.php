<?php
/*
 * Add / edit a product. $res = the row (defaults when adding) plus
 * meta_title/meta_keyword/meta_description; $category_ids / $color_ids /
 * $size_ids = chosen; $category_tree = the categories as a tree (only
 * categories without sub-categories can be chosen, as before); $brands,
 * $materials, $colors, $sizes = id => row (FALSE = not loaded); $flags =
 * column => label; $meta = its wl_meta_tags row; $stock_rows = how many stock
 * rows the product has; $features = Config\Shop::$productFeatures (parts that
 * are off are not shown). Description and Additional information use the
 * shared CKEditor setup (editor.js). A new product also takes up to six photos.
 */
$is_edit = isset($res['products_id']);
$action  = $is_edit ? 'sitepanel/products/edit/'.(int) $res['products_id'] : 'sitepanel/products/add';
$value   = function ($key) use ($res) {
	return set_value($key, (string) $res[$key]);
};
$posted   = service('request')->getMethod() === 'POST';
$chosen   = function ($field, $saved) use ($posted) {
	$ids = $posted ? service('request')->getPost($field) : $saved;
	return array_map('intval', is_array($ids) ? $ids : array());
};
$category_ids = $chosen('category_ids', $category_ids);
$color_ids = $chosen('color_ids', $color_ids);
$size_ids  = $chosen('size_ids', $size_ids);
$selected  = function ($field) use ($res) { return (string) set_value($field, (string) $res[$field], FALSE); };
$on        = function ($feature) use ($features) { return ! empty($features[$feature]); };
// "Available quantity" instead of the Stock page: products with at most one stock row (no colour / size options).
$with_quantity = ! $on('stock') && $stock_rows <= 1;
$stock_page    = $is_edit && ($on('stock') || $stock_rows > 1);
echo sp_view('includes/header'); ?>
<?php echo sp_view('includes/form_errors', array('errors' => $errors)); ?>
<?php if ($categories === FALSE || $category_tree === FALSE || $colors === FALSE || $sizes === FALSE || $brands === FALSE || $materials === FALSE): ?>
<div class="alert alert-danger" role="alert">Some form options could not be loaded. Please try again shortly.</div>
<?php elseif ( ! $categories): ?>
<div class="alert alert-warning" role="alert">Add a <a class="alert-link" href="<?php echo site_url('sitepanel/category/add'); ?>">category</a> first: every product belongs to at least one.</div>
<?php endif; ?>
<?php if ($is_edit): ?>
<div class="sp-row-actions mb-3">
	<?php if ($stock_page): ?>
	<a class="btn sp-btn-outline" href="<?php echo site_url('sitepanel/products/stock/'.(int) $res['products_id']); ?>">Stock &amp; option prices</a>
	<?php endif; ?>
	<a class="btn sp-btn-outline" href="<?php echo site_url('sitepanel/products/media/'.(int) $res['products_id']); ?>">Photos</a>
	<a class="btn sp-btn-outline" href="<?php echo site_url('sitepanel/products/related/'.(int) $res['products_id']); ?>">Related products</a>
</div>
<?php endif; ?>

<?php echo form_open_multipart($action, array('novalidate' => 'novalidate')); ?>
	<section class="sp-panel mb-4" aria-labelledby="form-title">
		<header class="sp-panel-head"><h2 class="sp-panel-title" id="form-title"><?php echo $is_edit ? 'Edit product' : 'New product'; ?></h2></header>
		<div class="row g-3">
			<div class="col-12 col-lg-6">
				<fieldset aria-describedby="category-help">
					<legend class="form-label fs-6">Categories <span class="text-danger">*</span></legend>
					<?php if (is_array($category_tree) && $category_tree): ?>
					<ul class="sp-related-list" id="category-options">
						<?php
						$render_category_tree = function (array $nodes, int $depth = 0) use (&$render_category_tree, $category_ids): void {
							foreach ($nodes as $node) {
								$id     = (int) $node['id'];
								$label  = (string) $node['name'].($node['status'] !== '1' ? ' (Inactive)' : '');
								$indent = 'padding-left:'.(12 + $depth * 22).'px';
								if ( ! empty($node['children'])): ?>
						<li style="<?php echo $indent; ?>"><span class="sp-related-title"><?php echo html_escape($label); ?></span></li>
									<?php $render_category_tree($node['children'], $depth + 1);
								else:
									$checked = in_array($id, $category_ids, TRUE); ?>
						<li style="<?php echo $indent; ?>"<?php echo $checked ? ' class="is-checked"' : ''; ?>>
							<input class="form-check-input" type="checkbox" id="category-<?php echo $id; ?>" name="category_ids[]" value="<?php echo $id; ?>"<?php echo $checked ? ' checked' : ''; ?>>
							<label for="category-<?php echo $id; ?>"><?php echo html_escape($label); ?></label>
						</li>
								<?php endif;
							}
						};
						$render_category_tree($category_tree);
						?>
					</ul>
					<?php endif; ?>
					<div class="form-text" id="category-help">Tick every category the product is listed in. The first ticked one is its main category (breadcrumbs and product card); an edited product keeps its main category while it stays ticked.</div>
				</fieldset>
			</div>
			<?php if ($on('brand')): ?>
			<div class="col-6 col-lg-3">
				<label class="form-label" for="brand_id">Brand</label>
				<select class="form-select" id="brand_id" name="brand_id">
					<option value="">None</option>
					<?php foreach ((array) $brands as $id => $brand): ?>
					<option value="<?php echo (int) $id; ?>"<?php echo (string) $id === $selected('brand_id') ? ' selected' : ''; ?>><?php echo html_escape($brand['brand_name'].($brand['status'] === '1' ? '' : ' (inactive)')); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<?php endif; ?>
			<?php if ($on('material')): ?>
			<div class="col-6 col-lg-3">
				<label class="form-label" for="material_id">Material</label>
				<select class="form-select" id="material_id" name="material_id">
					<option value="">None</option>
					<?php foreach ((array) $materials as $id => $material): ?>
					<option value="<?php echo (int) $id; ?>"<?php echo (string) $id === $selected('material_id') ? ' selected' : ''; ?>><?php echo html_escape($material['material_name'].($material['status'] === '1' ? '' : ' (inactive)')); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<?php endif; ?>
			<div class="col-12 col-lg-8">
				<label class="form-label" for="product_name">Product name <span class="text-danger">*</span></label>
				<input class="form-control" type="text" id="product_name" name="product_name" maxlength="200" required value="<?php echo $value('product_name'); ?>">
			</div>
			<div class="col-12 col-lg-4">
				<label class="form-label" for="product_code">Product code (SKU) <span class="text-danger">*</span></label>
				<input class="form-control" type="text" id="product_code" name="product_code" maxlength="64" required value="<?php echo $value('product_code'); ?>">
			</div>
			<?php
			// URL slug: made by the server from the name (Products::slug_for()); shown read-only and not submitted (no name attribute).
			$saved_name  = $is_edit ? (string) $res['product_name'] : '';
			$saved_slug  = $is_edit ? (string) $res['friendly_url'] : '';
			$posted_name = trim((string) service('request')->getPost('product_name'));
			$slug_shown  = $posted && $posted_name !== '' && $posted_name !== $saved_name ? sp_page_slug($posted_name, Modules\Sitepanel\Controllers\Products::SLUG_MAX) : $saved_slug;
			?>
			<div class="col-12 col-lg-6">
				<label class="form-label" for="friendly_url">URL slug</label>
				<input class="form-control sp-input-readonly sp-muted" type="text" id="friendly_url" readonly tabindex="-1" placeholder="Made from the name" value="<?php echo html_escape($slug_shown); ?>" aria-describedby="slug-help" data-slug-source="product_name" data-slug-max="<?php echo Modules\Sitepanel\Controllers\Products::SLUG_MAX; ?>" data-saved-name="<?php echo html_escape($saved_name); ?>" data-saved-url="<?php echo html_escape($saved_slug); ?>">
				<div class="form-text" id="slug-help">Made automatically from the product name when saved; if it is already used, -2, -3… is added. It stays the same while the name is unchanged. The page address is /&lt;slug&gt;.</div>
			</div>
			<div class="col-12 col-lg-6">
				<label class="form-label" for="product_alt">Photo alt text</label>
				<input class="form-control" type="text" id="product_alt" name="product_alt" maxlength="100" value="<?php echo $value('product_alt'); ?>">
				<div class="form-text">Defaults to the product name.</div>
			</div>
			<div class="col-6 col-lg-3">
				<label class="form-label" for="product_price">Price <span class="text-danger">*</span></label>
				<input class="form-control" type="text" inputmode="decimal" id="product_price" name="product_price" maxlength="11" required value="<?php echo $value('product_price'); ?>">
				<div class="form-text">GST included, e.g. 79.00.</div>
			</div>
			<div class="col-6 col-lg-3">
				<label class="form-label" for="product_discounted_price">Discounted price</label>
				<input class="form-control" type="text" inputmode="decimal" id="product_discounted_price" name="product_discounted_price" maxlength="11" value="<?php echo $value('product_discounted_price'); ?>">
				<div class="form-text">Optional sale price, below the price.</div>
			</div>
			<?php if ($on('weight')): ?>
			<div class="col-6 col-lg-3">
				<label class="form-label" for="product_weight">Weight</label>
				<input class="form-control" type="text" id="product_weight" name="product_weight" maxlength="55" value="<?php echo $value('product_weight'); ?>">
				<div class="form-text">e.g. 1.2 kg</div>
			</div>
			<?php endif; ?>
			<?php if ($with_quantity): ?>
			<div class="col-6 col-lg-3">
				<label class="form-label" for="product_quantity">Available quantity <span class="text-danger">*</span></label>
				<input class="form-control" type="number" inputmode="numeric" id="product_quantity" name="product_quantity" min="0" max="999999" step="1" required value="<?php echo $value('product_quantity'); ?>">
				<div class="form-text">Units in stock; 0 shows the product as Out of Stock.</div>
			</div>
			<?php elseif ( ! $on('stock')): ?>
			<div class="col-6 col-lg-3">
				<span class="form-label d-block">Available quantity</span>
				<p class="mb-0"><?php echo (int) $res['product_quantity']; ?></p>
				<div class="form-text">This product has colour / size options, each with its own stock: <a class="sp-link" href="<?php echo site_url('sitepanel/products/stock/'.(int) $res['products_id']); ?>">Stock &amp; option prices</a>.</div>
			</div>
			<?php endif; ?>
			<div class="col-6 col-lg-3">
				<label class="form-label" for="status">Status <span class="text-danger">*</span></label>
				<select class="form-select" id="status" name="status" required>
					<option value="1"<?php echo set_select('status', '1', $res['status'] === '1'); ?>>Active</option>
					<option value="0"<?php echo set_select('status', '0', $res['status'] !== '1'); ?>>Inactive</option>
				</select>
			</div>
			<?php if ($on('button_type')): ?>
			<div class="col-12">
				<fieldset>
					<legend class="form-label fs-6">Button type <span class="text-danger">*</span></legend>
					<?php foreach (Modules\Sitepanel\Controllers\Products::BUTTON_TYPES as $button_value => $button_label): ?>
					<div class="form-check form-check-inline">
						<input class="form-check-input" type="radio" id="is_show_button_<?php echo $button_value; ?>" name="is_show_button" value="<?php echo $button_value; ?>"<?php echo (string) set_value('is_show_button', (string) ($res['is_show_button'] ?? '4')) === (string) $button_value ? ' checked' : ''; ?> required>
						<label class="form-check-label" for="is_show_button_<?php echo $button_value; ?>"><?php echo html_escape($button_label); ?></label>
					</div>
					<?php endforeach; ?>
				</fieldset>
			</div>
			<?php endif; ?>
			<?php if ($on('labels')): ?>
			<div class="col-12">
				<fieldset>
					<legend class="form-label fs-6">Labels</legend>
					<?php foreach ($flags as $flag => $label): ?>
					<?php $on = $posted ? service('request')->getPost($flag) === '1' : $res[$flag] === '1'; ?>
					<div class="form-check form-check-inline">
						<input class="form-check-input" type="checkbox" id="<?php echo $flag; ?>" name="<?php echo $flag; ?>" value="1"<?php echo $on ? ' checked' : ''; ?>>
						<label class="form-check-label" for="<?php echo $flag; ?>"><?php echo html_escape($label); ?></label>
					</div>
					<?php endforeach; ?>
					<div class="form-text">New and Sale / Hot show a badge on the product card; Featured products are listed first.</div>
				</fieldset>
			</div>
			<?php endif; ?>
			<div class="col-12">
				<label class="form-label" for="products_description">Description</label>
				<textarea class="form-control" id="products_description" name="products_description" rows="10" data-sp-editor data-sp-upload-url="<?php echo html_escape(site_url('sitepanel/products/upload_image')); ?>"><?php echo set_value('products_description', (string) $res['products_description']); ?></textarea>
			</div>
			<div class="col-12">
				<label class="form-label" for="products_specification">Additional information</label>
				<textarea class="form-control" id="products_specification" name="products_specification" rows="8" data-sp-editor data-sp-upload-url="<?php echo html_escape(site_url('sitepanel/products/upload_image')); ?>"><?php echo set_value('products_specification', (string) $res['products_specification']); ?></textarea>
				<div class="form-text">Shown in the "Additional Information" tab, e.g. a table of contents, sizes, weight and delivery. Scripts and unsafe markup are removed when saved.</div>
			</div>
		</div>
	</section>

	<?php if ($on('colors') || $on('sizes')): ?>
	<section class="sp-panel mb-4" aria-labelledby="options-title">
		<header class="sp-panel-head"><h2 class="sp-panel-title" id="options-title">Colours and sizes</h2></header>
		<p class="sp-muted">Optional. Customers choose one colour and one size; each combination gets its own stock (and may get its own price) on the Stock page. Removing a colour or size removes the stock of its combinations.</p>
		<div class="row g-3">
			<?php foreach (array('color_ids' => array('Colours', $colors, 'color_id', 'color_name', 'sitepanel/color', 'colors'), 'size_ids' => array('Sizes', $sizes, 'size_id', 'size_name', 'sitepanel/size', 'sizes')) as $field => list($legend, $list, $pk, $name, $manage, $feature)): ?>
			<?php if ( ! $on($feature)) { continue; } ?>
			<div class="col-12 col-lg-6">
				<fieldset>
					<legend class="form-label fs-6"><?php echo $legend; ?></legend>
					<?php if (empty($list)): ?>
					<p class="sp-muted small mb-0">None yet. <a class="sp-link" href="<?php echo site_url($manage); ?>">Manage <?php echo strtolower($legend); ?></a></p>
					<?php else: ?>
					<?php foreach ($list as $id => $option): ?>
					<div class="form-check form-check-inline">
						<input class="form-check-input" type="checkbox" id="<?php echo $field.'-'.(int) $id; ?>" name="<?php echo $field; ?>[]" value="<?php echo (int) $id; ?>"<?php echo in_array((int) $id, $field === 'color_ids' ? $color_ids : $size_ids, TRUE) ? ' checked' : ''; ?>>
						<label class="form-check-label" for="<?php echo $field.'-'.(int) $id; ?>">
							<?php if ($field === 'color_ids'): ?><span class="d-inline-block rounded-circle border align-middle me-1" style="width:14px;height:14px;background:<?php echo html_escape($option['color_code']); ?>" aria-hidden="true"></span><?php endif; ?>
							<?php echo html_escape($option[$name].($option['status'] === '1' ? '' : ' (inactive)')); ?>
						</label>
					</div>
					<?php endforeach; ?>
					<?php endif; ?>
				</fieldset>
			</div>
			<?php endforeach; ?>
		</div>
	</section>
	<?php endif; ?>

	<?php if ( ! $is_edit): ?>
	<section class="sp-panel mb-4" aria-labelledby="photos-title">
		<header class="sp-panel-head"><h2 class="sp-panel-title" id="photos-title">Photos</h2></header>
		<p class="sp-muted">Optional; more photos, their order and their colours are managed on the product's Photos page. The first photo is the main one. JPG, PNG, GIF or WebP, up to 2 MB each.</p>
		<div class="row g-3">
			<?php for ($n = 1; $n <= 6; $n++): ?>
			<div class="col-12 col-md-6 col-lg-4">
				<label class="form-label" for="photo_<?php echo $n; ?>">Photo <?php echo $n; ?></label>
				<input class="form-control" type="file" id="photo_<?php echo $n; ?>" name="photo_<?php echo $n; ?>" accept=".jpg,.jpeg,.png,.gif,.webp,image/jpeg,image/png,image/gif,image/webp">
			</div>
			<?php endfor; ?>
		</div>
	</section>
	<?php endif; ?>

	<section class="sp-panel mb-4" aria-labelledby="seo-title">
		<header class="sp-panel-head"><h2 class="sp-panel-title" id="seo-title">SEO</h2></header>
		<p class="sp-muted">Optional. Empty fields use the product name and description. Stored with the Meta Tags<?php if ( ! empty($meta['meta_id'])): ?> (<a class="sp-link" href="<?php echo site_url('sitepanel/meta/edit/'.(int) $meta['meta_id']); ?>">open in Meta Tags</a>)<?php endif; ?>.</p>
		<div class="row g-3">
			<div class="col-12 col-lg-6">
				<label class="form-label" for="meta_title">Meta title</label>
				<input class="form-control" type="text" id="meta_title" name="meta_title" maxlength="450" value="<?php echo $value('meta_title'); ?>">
			</div>
			<div class="col-12 col-lg-6">
				<label class="form-label" for="meta_keyword">Meta keywords</label>
				<input class="form-control" type="text" id="meta_keyword" name="meta_keyword" maxlength="450" value="<?php echo $value('meta_keyword'); ?>">
			</div>
			<div class="col-12">
				<label class="form-label" for="meta_description">Meta description</label>
				<textarea class="form-control" id="meta_description" name="meta_description" rows="3" maxlength="650"><?php echo $value('meta_description'); ?></textarea>
			</div>
		</div>
	</section>

	<div class="sp-form-actions mb-4">
		<button class="btn sp-btn-primary" type="submit"><?php echo $is_edit ? 'Save changes' : sp_icon('plus').' '.'Add product'; ?></button>
		<a class="btn sp-btn-outline" href="<?php echo site_url('sitepanel/products'); ?>">Cancel</a>
	</div>
<?php echo form_close(); ?>
<script src="<?php echo asset_url('assets/vendor/ckeditor4/ckeditor.js'); ?>"></script>
<script src="<?php echo asset_url('assets/sitepanel/js/editor.js'); ?>"></script>
<script src="<?php echo asset_url('assets/sitepanel/js/page-slug.js'); ?>"></script>
<?php echo sp_view('includes/footer'); ?>
