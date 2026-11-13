<?php
/*
| Page links of the store and account lists (design: the .kt-pagination of listing.htm).
|   $pager = ['page' => current (1-based), 'total' => rows, 'per_page' => rows per page,
|             'url' => page address, 'query' => other GET parameters to keep]
| Nothing is shown for a single page.
*/
$pager += array('query' => array());
$pages  = (int) ceil($pager['total'] / max(1, $pager['per_page']));
if ($pages < 2)
{
	return;
}
$current = min(max(1, (int) $pager['page']), $pages);
$link    = function ($n) use ($pager) {
	$query = array_filter($pager['query'], function ($v) { return $v !== '' && $v !== NULL && $v !== array(); });
	$n > 1 && $query['page'] = $n;
	return html_escape($pager['url'].($query ? '?'.http_build_query($query) : ''));
};
$shown = array_unique(array_filter(array(1, $current - 1, $current, $current + 1, $pages), function ($n) use ($pages) { return $n >= 1 && $n <= $pages; }));
sort($shown);
?>
<nav class="kt-cat-pages d-flex justify-content-center" aria-label="Pages">
<ul class="pagination kt-pagination mb-0">
<li class="page-item<?php echo $current === 1 ? ' disabled' : ''; ?>"><a class="page-link rounded-circle" href="<?php echo $current === 1 ? '#' : $link($current - 1); ?>" aria-label="Previous page"<?php echo $current === 1 ? ' tabindex="-1" aria-disabled="true"' : ''; ?>><i class="bi bi-arrow-left" aria-hidden="true"></i></a></li>
<?php $previous = 0; foreach ($shown as $n): ?>
<?php if ($n - $previous > 1): ?><li class="page-item disabled"><span class="page-link rounded-circle">&hellip;</span></li><?php endif; ?>
<li class="page-item<?php echo $n === $current ? ' active' : ''; ?>"><a class="page-link rounded-circle" href="<?php echo $link($n); ?>"<?php echo $n === $current ? ' aria-current="page"' : ''; ?>><?php echo $n; ?></a></li>
<?php $previous = $n; endforeach; ?>
<li class="page-item<?php echo $current === $pages ? ' disabled' : ''; ?>"><a class="page-link rounded-circle" href="<?php echo $current === $pages ? '#' : $link($current + 1); ?>" aria-label="Next page"<?php echo $current === $pages ? ' tabindex="-1" aria-disabled="true"' : ''; ?>><i class="bi bi-arrow-right" aria-hidden="true"></i></a></li>
</ul>
</nav>
