<?php
/*
| Keyword + status (All / Active / Inactive) search form of a list. Pass
| everything in $filter_form (CI keeps view variables between partial loads):
|   url (list route, also the Clear link), aria_label (form label),
|   label (keyword field label), keyword, status (current filter values).
| Lists with other filter fields keep their own form.
*/
$has_filter = $filter_form['keyword'] !== '' || $filter_form['status'] !== '';
?>
<form class="sp-filters" method="get" action="<?php echo site_url($filter_form['url']); ?>" role="search" aria-label="<?php echo html_escape($filter_form['aria_label']); ?>">
		<div>
			<label class="form-label" for="filter-keyword"><?php echo html_escape($filter_form['label']); ?></label>
			<input class="form-control" type="search" id="filter-keyword" name="keyword" maxlength="80" value="<?php echo html_escape($filter_form['keyword']); ?>">
		</div>
		<div>
			<label class="form-label" for="filter-status">Status</label>
			<select class="form-select" id="filter-status" name="status">
				<option value="">All</option>
				<option value="1"<?php echo $filter_form['status'] === '1' ? ' selected' : ''; ?>>Active</option>
				<option value="0"<?php echo $filter_form['status'] === '0' ? ' selected' : ''; ?>>Inactive</option>
			</select>
		</div>
		<div class="sp-filters-actions">
			<button class="btn sp-btn-primary" type="submit">Search</button>
			<?php if ($has_filter): ?><a class="btn sp-btn-outline" href="<?php echo site_url($filter_form['url']); ?>">Clear</a><?php endif; ?>
		</div>
	</form>
