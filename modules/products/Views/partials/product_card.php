<?php
/*
| Product card (design: the .kt-prod cards of index.htm / listing.htm).
| $card from product_cards(): product (public row), options (colour / size to
| choose: Add to Cart opens the product page), swatches [colors, sizes], wish.
| $card_class: extra classes on the card (e.g. 'h-100' in a grid); $card_listing: the listing
| page variant (listing.htm: a size select on the card instead of the size list).
| Add to Cart / wishlist: customcart.js ([data-kt-add], [data-kt-wish]).
*/
$p      = $card['product'];
$url    = product_url($p['friendly_url']);
$prices = \Modules\Products\Models\ProductModel::prices($p);
$name   = (string) $p['product_name'];
$cat    = (string) ($p['category_name'] ?? '');
$img    = product_image_url($p['image'] ?? NULL, 600, 600);
$in     = (int) $p['product_quantity'] > 0;
$sizes  = $card['swatches']['sizes'];
$select = ! empty($card_listing) && $sizes;
// Add to Cart opens the product page while a colour (or a size, without the select) must be chosen first.
$choose = $card['swatches']['colors'] || ($sizes && ! $select) || ($card['options'] && ! $sizes);
?>
<div class="card kt-prod<?php echo ! empty($card_class) ? ' '.$card_class : ''; ?>" data-product-id="<?php echo (int) $p['products_id']; ?>" data-url="<?php echo html_escape($url); ?>" data-name="<?php echo html_escape($name); ?>" data-kt-options="<?php echo $choose ? '1' : '0'; ?>">
  <div class="position-relative kt-mb-18">
    <a href="<?php echo html_escape($url); ?>" class="kt-prod-img d-block ratio ratio-1x1 rounded-5 overflow-hidden bg-primary-subtle"><img loading="lazy" fetchpriority="low" decoding="async" src="<?php echo html_escape($img); ?>" alt="<?php echo html_escape($p['product_alt'] !== '' && $p['product_alt'] !== NULL ? $p['product_alt'] : $name); ?>" class="object-fit-cover"></a>
<?php if ($prices['percent'] > 0): ?>
    <span class="kt-prod-badge position-absolute rounded-circle bg-warning text-dark fw-bold d-flex align-items-center justify-content-center">-<?php echo $prices['percent']; ?>%</span>
<?php endif; ?>
    <div class="kt-prod-tools position-absolute d-flex flex-column gap-2">
      <button type="button" class="btn btn-kt-tool kt-wish rounded-circle d-flex align-items-center justify-content-center<?php echo $card['wish'] ? ' is-active' : ''; ?>" data-kt-wish aria-label="Wishlist" aria-pressed="<?php echo $card['wish'] ? 'true' : 'false'; ?>"><i class="bi bi-heart<?php echo $card['wish'] ? '-fill' : ''; ?>"></i></button>
      <a href="<?php echo html_escape($url); ?>" class="btn btn-kt-tool kt-view rounded-circle d-flex align-items-center justify-content-center" aria-label="View product details"><i class="bi bi-eye"></i></a>
    </div>
  </div>
<?php if ($cat !== ''): ?>
  <div class="kt-prod-cat fw-bold text-primary text-uppercase"><?php echo html_escape($cat); ?></div>
<?php endif; ?>
  <a href="<?php echo html_escape($url); ?>" class="kt-prod-name fw-bold"><?php echo html_escape($name); ?></a>
  <div class="d-flex align-items-baseline kt-gap-10 kt-mb-12"><span class="kt-price fw-bold text-dark"><?php echo display_price($prices['sale']); ?></span><?php if ($prices['saving'] > 0): ?><span class="kt-price-old text-decoration-line-through"><?php echo display_price($prices['regular']); ?></span><?php endif; ?></div>
<?php if ($card['swatches']['colors'] || $sizes): ?>
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 kt-mb-18">
    <span class="d-flex kt-gap-6"><?php foreach (array_slice($card['swatches']['colors'], 0, 5) as list($cname, $ccode)): ?><span class="kt-sw rounded-circle" style="background:<?php echo html_escape(preg_match('/^#[0-9a-f]{3,6}$/i', $ccode) ? $ccode : '#ccc'); ?>" title="<?php echo html_escape($cname); ?>"></span><?php endforeach; ?></span>
<?php if ($select): ?>
    <div class="kt-side-body pb-2 d-flex flex-wrap gap-2">
    <label class="kt-qv-fabric mb-0 w-100"><span class="visually-hidden">Size</span>
    <select class="form-select kt-select1 rounded-3" data-kt-size>
<?php foreach ($sizes as $sid => $sname): ?>
    <option value="<?php echo (int) $sid; ?>"><?php echo html_escape($sname); ?></option>
<?php endforeach; ?>
    </select>
    </label>
    </div>
<?php else: ?>
    <span class="kt-sizes d-flex gap-1"><?php foreach (array_slice($sizes, 0, 5) as $sname): ?><span><?php echo html_escape($sname); ?></span><?php endforeach; ?></span>
<?php endif; ?>
  </div>
<?php endif; ?>
  <div class="kt-prod-actions d-grid gap-2 mt-auto">
<?php if ($in): ?>
    <button type="button" class="btn btn-kt-dark kt-btn-sm rounded-pill" data-kt-add>Add to Cart</button>
    <a href="<?php echo html_escape($url); ?>" class="btn btn-kt-outline kt-btn-sm rounded-pill">Buy Now</a>
<?php else: ?>
    <span class="btn btn-kt-outline kt-btn-sm rounded-pill disabled" aria-disabled="true">Out of Stock</span>
    <button type="button" class="btn btn-kt-outline kt-btn-sm rounded-pill" data-bs-toggle="modal" data-bs-target="#ktInquiry">Send Inquiry</button>
<?php endif; ?>
  </div>
</div>
