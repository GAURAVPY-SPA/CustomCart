<?php
/*
| Product listing (design: html/listing.htm; search results: search-result.htm),
| see Products::render_listing(). Filters are one GET form (Apply Filters; the
| sort select submits it too): q, cat, sizes[], colors[], fabrics[], price[], sort.
*/
use Modules\Products\Controllers\Products;

$cards  = product_cards($products);
$from   = $total ? ($pager['page'] - 1) * $pager['per_page'] + 1 : 0;
$to     = min($total, $from + count($products) - 1);
$intro  = $category ? trim((string) $category['category_description']) : '';
$active = $category ? (int) $category['category_id'] : 0;
echo view('top_application');
echo view('inner_hero', array('hero' => $hero + array('banner' => 'Online Store')));
?>
<section class="container-xxl kt-container kt-sec" data-reveal>
<form action="<?php echo html_escape($form_url); ?>" method="post" id="ktFilterForm">
<?php /* POST: filters and sort are kept in the session for this listing (Products::render_listing()), not in the address */ ?>
<?php echo csrf_field(); ?>
<input type="hidden" name="listing" value="1">
<div class="row kt-g-catpage">
<aside class="col-12 col-lg-3">
<button type="button" class="btn btn-kt-outline kt-filter-toggle rounded-pill w-100 d-flex d-lg-none align-items-center justify-content-center gap-2" data-bs-toggle="collapse" data-bs-target="#ktFilters" aria-expanded="false" aria-controls="ktFilters"><i class="bi bi-sliders"></i> FILTERS</button>
<div class="collapse d-lg-block" id="ktFilters">
<div class="kt-side bg-white border">

<div class="kt-side-head d-flex align-items-center justify-content-between">
<span class="d-flex align-items-center kt-gap-12"><span class="kt-side-head-ico rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"><i class="bi bi-funnel"></i></span><span class="d-flex flex-column"><span class="kt-side-head-title fw-bold text-white">Filter Products</span><span class="kt-side-head-sub">Refine your results</span></span></span>
<button type="submit" name="clear" value="1" class="btn kt-side-clear rounded-pill fw-bold d-inline-flex align-items-center gap-1 flex-shrink-0"><i class="bi bi-arrow-counterclockwise"></i> CLEAR</button>
</div>

<?php if ($tree): ?>
<div class="kt-side-block">
<button type="button" class="kt-side-title btn w-100 d-flex align-items-center justify-content-between" data-bs-toggle="collapse" data-bs-target="#ktFCat" aria-expanded="true" aria-controls="ktFCat">CATEGORIES <i class="bi bi-chevron-down"></i></button>
<div class="collapse show" id="ktFCat">
<div class="kt-side-cats d-flex flex-column" id="ktCatAcc">
<?php foreach ($tree as $i => $top):
	$open = $active === (int) $top['category_id'] || in_array($active, array_map('intval', array_column($top['children'], 'category_id')), TRUE);
	$cid  = 'ktCat'.($i + 1);
?>
<div class="kt-side-cat-item<?php echo $open ? ' is-active' : ''; ?>">
<?php if ($top['children']): ?>
<button type="button" class="kt-side-cat btn w-100 d-flex align-items-center<?php echo $open ? '' : ' collapsed'; ?>" data-bs-toggle="collapse" data-bs-target="#<?php echo $cid; ?>" aria-expanded="<?php echo $open ? 'true' : 'false'; ?>" aria-controls="<?php echo $cid; ?>"><span class="flex-grow-1 text-start"><?php echo html_escape($top['category_name']); ?></span><span class="kt-side-count rounded-pill"><?php echo (int) $top['count']; ?></span><i class="bi bi-chevron-down"></i></button>
<div class="collapse<?php echo $open ? ' show' : ''; ?>" id="<?php echo $cid; ?>" data-bs-parent="#ktCatAcc">
<ul class="kt-side-sub list-unstyled mb-0">
<li><a href="<?php echo html_escape(category_url($top['friendly_url'])); ?>"<?php echo $active === (int) $top['category_id'] ? ' class="is-active"' : ''; ?>>All <?php echo html_escape($top['category_name']); ?></a></li>
<?php foreach ($top['children'] as $child): ?>
<li><a href="<?php echo html_escape(category_url($child['friendly_url'])); ?>"<?php echo $active === (int) $child['category_id'] ? ' class="is-active"' : ''; ?>><?php echo html_escape($child['category_name']); ?></a></li>
<?php endforeach; ?>
</ul>
</div>
<?php else: ?>
<a href="<?php echo html_escape(category_url($top['friendly_url'])); ?>" class="kt-side-cat btn w-100 d-flex align-items-center"><span class="flex-grow-1 text-start"><?php echo html_escape($top['category_name']); ?></span><span class="kt-side-count rounded-pill"><?php echo (int) $top['count']; ?></span></a>
<?php endif; ?>
</div>
<?php endforeach; ?>
</div>
</div>
</div>
<?php endif; ?>

<?php if ($size_options): ?>
<div class="kt-side-block">
<button type="button" class="kt-side-title btn w-100 d-flex align-items-center justify-content-between" data-bs-toggle="collapse" data-bs-target="#ktFSize" aria-expanded="true" aria-controls="ktFSize">SIZE <i class="bi bi-chevron-down"></i></button>
<div class="collapse show" id="ktFSize">
<div class="kt-side-body d-flex flex-wrap gap-2">
<label class="kt-qv-fabric mb-0 w-100" for="ktFilterSize"><span class="visually-hidden">Size</span>
<select class="form-select kt-select rounded-3" id="ktFilterSize" name="sizes[]">
<option value="">All sizes</option>
<?php foreach ($size_options as $sid => $sname): ?>
<option value="<?php echo (int) $sid; ?>"<?php echo in_array((int) $sid, $selected['sizes'], TRUE) ? ' selected' : ''; ?>><?php echo html_escape(is_array($sname) ? $sname['size_name'] : $sname); ?></option>
<?php endforeach; ?>
</select>
</label>
</div>
</div>
</div>
<?php endif; ?>

<?php if ($color_options): ?>
<div class="kt-side-block">
<button type="button" class="kt-side-title btn w-100 d-flex align-items-center justify-content-between" data-bs-toggle="collapse" data-bs-target="#ktFColour" aria-expanded="true" aria-controls="ktFColour">COLOUR <i class="bi bi-chevron-down"></i></button>
<div class="collapse show" id="ktFColour">
<div class="kt-side-body d-flex flex-wrap kt-gap-14">
<?php foreach ($color_options as $cid => $color): $cname = is_array($color) ? $color['color_name'] : $color; $ccode = is_array($color) ? (string) $color['color_code'] : ''; ?>
<input type="checkbox" class="btn-check" id="ktCl<?php echo (int) $cid; ?>" name="colors[]" value="<?php echo (int) $cid; ?>" autocomplete="off"<?php echo in_array((int) $cid, $selected['colors'], TRUE) ? ' checked' : ''; ?>><label class="kt-side-sw kt-sw rounded-circle" for="ktCl<?php echo (int) $cid; ?>" title="<?php echo html_escape($cname); ?>" style="background:<?php echo html_escape(preg_match('/^#[0-9a-f]{3,6}$/i', $ccode) ? $ccode : '#ccc'); ?>"></label>
<?php endforeach; ?>
</div>
</div>
</div>
<?php endif; ?>

<?php if ($fabric_options): ?>
<div class="kt-side-block">
<button type="button" class="kt-side-title btn w-100 d-flex align-items-center justify-content-between" data-bs-toggle="collapse" data-bs-target="#ktFFabric" aria-expanded="true" aria-controls="ktFFabric">FABRIC <i class="bi bi-chevron-down"></i></button>
<div class="collapse show" id="ktFFabric">
<div class="kt-side-body d-flex flex-column">
<?php foreach ($fabric_options as $fid => $fabric): ?>
<div class="form-check kt-side-check"><input class="form-check-input" type="checkbox" id="ktFb<?php echo (int) $fid; ?>" name="fabrics[]" value="<?php echo (int) $fid; ?>"<?php echo in_array((int) $fid, $selected['fabrics'], TRUE) ? ' checked' : ''; ?>><label class="form-check-label" for="ktFb<?php echo (int) $fid; ?>"><?php echo html_escape(is_array($fabric) ? $fabric['material_name'] : $fabric); ?></label></div>
<?php endforeach; ?>
</div>
</div>
</div>
<?php endif; ?>

<div class="kt-side-block">
<button type="button" class="kt-side-title btn w-100 d-flex align-items-center justify-content-between" data-bs-toggle="collapse" data-bs-target="#ktFPrice" aria-expanded="true" aria-controls="ktFPrice">PRICE <i class="bi bi-chevron-down"></i></button>
<div class="collapse show" id="ktFPrice">
<div class="kt-side-body d-flex flex-column">
<?php $n = 0; foreach (Products::PRICE_RANGES as $key => $range): $n++; ?>
<div class="form-check kt-side-check"><input class="form-check-input" type="checkbox" id="ktPrp<?php echo $n; ?>" name="price[]" value="<?php echo $key; ?>"<?php echo in_array($key, $prices, TRUE) ? ' checked' : ''; ?>><label class="form-check-label" for="ktPrp<?php echo $n; ?>"><?php echo Products::price_range_label($range); ?></label></div>
<?php endforeach; ?>
</div>
</div>
</div>

<button type="submit" class="btn btn-kt-dark kt-side-apply rounded-pill w-100 d-flex align-items-center justify-content-center gap-2">APPLY FILTERS <i class="bi bi-arrow-right"></i></button>
</div>
</div>
</aside>

<div class="col-12 col-lg-9">
<?php if ($keyword !== ''): ?>
<p class="kt-cat-intro kt-text-2">Search results for <strong class="text-dark">&ldquo;<?php echo html_escape($keyword); ?>&rdquo;</strong> <button type="submit" name="clear_search" value="1" class="btn btn-link kt-shop-link fw-bold ms-1 p-0 align-baseline" aria-label="Clear the search and show all products"><i class="bi bi-x-circle"></i></button></p>
<?php elseif ($intro !== ''): ?>
<p class="kt-cat-intro kt-text-2"><?php echo nl2br(html_escape($intro)); ?></p>
<?php endif; ?>
<div class="kt-cat-bar d-flex flex-wrap align-items-center justify-content-between">
<span class="text-body-secondary"><?php if ($total): ?>Showing <strong class="text-dark"><?php echo $from; ?>&ndash;<?php echo $to; ?></strong> of <strong class="text-dark"><?php echo $total; ?></strong> products<?php else: ?>No products found<?php endif; ?></span>
<div class="d-flex align-items-center gap-2">
<label for="ktSort" class="kt-cat-sort-label fw-bold text-dark text-nowrap">SORT BY</label>
<select id="ktSort" name="sort" class="form-select kt-select kt-cat-sort rounded-pill" onchange="this.form.submit()">
<?php foreach (Products::SORT_LABELS as $key => $label): ?>
<option value="<?php echo $key === 'default' ? '' : $key; ?>"<?php echo $sort === $key ? ' selected' : ''; ?>><?php echo html_escape($label); ?></option>
<?php endforeach; ?>
</select>
</div>
</div>
<?php if ($cards): ?>
<div class="row row-cols-1 row-cols-sm-2 row-cols-xl-3 kt-g-catgrid">
<?php foreach ($cards as $card): ?>
<div class="col">
<?php echo view('Modules\Products\Views\partials\product_card', array('card' => $card, 'card_class' => 'h-100', 'card_listing' => TRUE)); ?>
</div>
<?php endforeach; ?>
</div>
<?php echo view('Modules\Products\Views\partials\pager', array('pager' => $pager)); ?>
<?php else: ?>
<div class="text-center kt-text-2 py-5">
<p class="kt-lead mb-4"><?php echo $filtered ? 'No products match these filters.' : 'No products in this category yet.'; ?></p>
<?php if ($filtered): ?><button type="submit" name="<?php echo $keyword !== '' && ! $cat && ! $prices && ! $availability && ! array_filter($selected) ? 'clear_search' : 'clear'; ?>" value="1" class="btn btn-kt-outline rounded-pill kt-btn-56 px-4">CLEAR FILTERS</button><?php endif; ?>
<button type="button" class="btn btn-kt-dark rounded-pill kt-btn-56 px-4" data-bs-toggle="modal" data-bs-target="#ktInquiry">SEND INQUIRY</button>
</div>
<?php endif; ?>
</div>
</div>
</form>
</section>
<?php echo view('bottom_application'); ?>
