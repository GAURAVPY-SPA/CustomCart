<?php
/* Manage Bulk Upload Product (the reference's catalog/bulk_upload_view): upload form, sample / ID sheet downloads, the result of an import and the columns. */
echo sp_view('includes/header'); ?>
<?php echo sp_view('includes/form_errors', array('errors' => $errors)); ?>

<?php if ($results !== NULL): ?>
<?php
	$added   = count(array_filter($results, function ($r) { return $r[3] === NULL; }));
	$skipped = array_filter($results, function ($r) { return $r[3] !== NULL; });
?>
<?php if ($skipped): ?>
<div class="alert alert-danger" role="alert">
	<p class="mb-1 fw-semibold"><?php echo count($skipped); ?> row<?php echo count($skipped) === 1 ? ' was' : 's were'; ?> not added:</p>
	<ul class="mb-0">
		<?php foreach ($skipped as list($line, $code, $name, $error)): ?>
		<li>Row <?php echo (int) $line; ?><?php echo $code !== '' ? ' ('.html_escape($code).')' : ''; ?>: <?php echo html_escape($error); ?></li>
		<?php endforeach; ?>
	</ul>
</div>
<?php endif; ?>
<section class="sp-panel mb-4" aria-labelledby="result-title">
	<header class="sp-panel-head"><h2 class="sp-panel-title" id="result-title">Import result</h2></header>
	<p><strong><?php echo $added; ?></strong> of <?php echo count($results); ?> product<?php echo count($results) === 1 ? '' : 's'; ?> added. Check them in <a href="<?php echo site_url('sitepanel/products'); ?>">Manage Products</a>.</p>
	<?php if ($ignored): ?>
	<p class="sp-muted">Not imported, these columns are not used: <?php echo html_escape(implode(', ', $ignored)); ?>.</p>
	<?php endif; ?>
	<div class="sp-table-wrap">
		<table class="sp-table sp-table--stack">
			<caption class="visually-hidden">Result of each row of the file</caption>
			<thead><tr><th scope="col">Row</th><th scope="col">Product code</th><th scope="col">Name</th><th scope="col">Result</th></tr></thead>
			<tbody>
				<?php foreach ($results as list($line, $code, $name, $error)): ?>
				<tr>
					<td data-label="Row"><?php echo (int) $line; ?></td>
					<td data-label="Product code"><?php echo html_escape($code); ?></td>
					<td class="sp-cell-text" data-label="Name"><?php echo html_escape($name); ?></td>
					<td data-label="Result"><?php echo $error === NULL ? '<span class="sp-badge sp-badge--ok">Added</span>' : '<span class="text-danger">'.html_escape($error).'</span>'; ?></td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</section>
<?php endif; ?>

<?php if ($addPrvg): ?>
<?php echo form_open_multipart('sitepanel/products_bulk', array('novalidate' => 'novalidate')); ?>
	<section class="sp-panel mb-4" aria-labelledby="form-title">
		<header class="sp-panel-head"><h2 class="sp-panel-title" id="form-title">Bulk Upload</h2></header>
		<div class="row g-3">
			<div class="col-12 col-lg-8">
				<label class="form-label" for="bulk_data">Upload Excel Data <span class="text-danger">*</span></label>
				<input class="form-control" type="file" id="bulk_data" name="bulk_data" accept=".xlsx,.csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,text/csv" required aria-describedby="bulk_data_help">
				<div class="form-text" id="bulk_data_help">Excel workbook (.xlsx) or CSV, at most <?php echo html_escape((string) $max_mb); ?> MB and <?php echo (int) $max_rows; ?> products per file. Rows whose product code already exists are skipped.</div>
			</div>
			<div class="col-12">
				<div class="d-flex sp-row-actions gap-2">
					<a class="btn sp-btn-outline" href="<?php echo site_url('sitepanel/products_bulk/template'); ?>">Download Products Sample Sheet</a>
					<?php foreach ($id_lists as $list => $title): ?>
					<a class="btn sp-btn-outline" href="<?php echo site_url('sitepanel/products_bulk/ids/'.$list); ?>">Download <?php echo html_escape($title); ?> to View IDs</a>
					<?php endforeach; ?>
				</div>
				<p class="mt-3 sp-muted mb-0"><strong>Note:</strong> Please download the products sample sheet to view the columns and example rows, and the other sheets for the IDs to use. A category, fabric, colour or size can be given by its ID or by its name. Upload product photos to <code>uploads/products/</code> first and give their file names in the Image columns. The sample sheet is also available as <a href="<?php echo site_url('sitepanel/products_bulk/template?format=csv'); ?>">CSV</a>.</p>
			</div>
		</div>
		<div class="sp-form-actions mt-3 mb-2">
		<button class="btn sp-btn-primary" type="submit">Upload Data</button>
		<a class="btn sp-btn-outline" href="<?php echo site_url('sitepanel/products'); ?>">Cancel</a>
	</div>
	</section>
	
<?php echo form_close(); ?>
<?php else: ?>
<p class="sp-empty">You don't have permission to add products.</p>
<?php endif; ?>

<section class="sp-panel mb-4" aria-labelledby="columns-title">
	<header class="sp-panel-head"><h2 class="sp-panel-title" id="columns-title">Columns of the sheet</h2></header>
	<p class="sp-muted">The first row names the columns (any order, any letter case). Only the first worksheet is imported. Categories, fabrics, colours and sizes must already exist; the import never creates them.</p>
	<div class="sp-table-wrap">
		<table class="sp-table sp-table--stack">
			<caption class="visually-hidden">Columns of the upload sheet</caption>
			<thead><tr><th scope="col">Column</th><th scope="col">Required</th><th scope="col">Value</th></tr></thead>
			<tbody>
				<?php foreach ($columns as $column): ?>
				<tr>
					<td data-label="Column"><code><?php echo html_escape($column[0]); ?></code></td>
					<td data-label="Required"><?php echo $column[1] ? 'Yes' : 'No'; ?></td>
					<td class="sp-cell-text" data-label="Value"><?php echo html_escape($column[3]); ?></td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</section>
<?php echo sp_view('includes/footer'); ?>
