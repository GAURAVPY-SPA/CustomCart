<?php
/*
 * One series of the dashboard's orders graph (no chart library): a smooth
 * monotone curve (it never dips below 0 or overshoots a peak) over a gradient
 * area, small points, light grid lines. $series = Dashboard::orders_chart()[<period>]:
 * caption, total, points => list<[label, title, count]>; $period = its key.
 *
 * The plot is an SVG in a 0-100 coordinate space stretched to the plot box
 * (fixed height, any width; strokes don't scale). Axis labels and points are
 * HTML placed by percentage, so their size stays the same at every width.
 * Every point has a hit area (data-sp-chart-point: tooltip text, position)
 * that the dashboard script turns into a tooltip with a guide line on hover /
 * keyboard focus; without the script the area's <title> is the tooltip. The
 * figures are also in a visually hidden table.
 */
$points = $series['points'];
$n      = count($points);
$max    = max(array_column($points, 'count') ?: array(0));

// Y axis: steps of a round size (1, 2, 5 × 10^k) at least max / 4, up to just above the peak
// (2 to 4 grid lines, so a small series doesn't sit in empty space).
$step = 1;
while ($step * 4 < $max) {
	$digits = (int) floor(log10($step));
	$lead   = (int) round($step / (10 ** $digits));
	$step   = $lead === 1 ? 2 * (10 ** $digits) : ($lead === 2 ? 5 * (10 ** $digits) : 10 ** ($digits + 1));
}
$lines = min(4, max(2, (int) ceil($max * 1.1 / $step)));
$top   = $step * $lines;

$gap   = $n > 1 ? 100 / ($n - 1) : 0;
$every = max(1, (int) ceil($n / 8)); // at most about 8 x-axis labels, never overlapping
$noun  = function ($count) { return number_format($count).' order'.($count === 1 ? '' : 's'); };
$id    = 'orders-chart-'.$period;
$f     = function ($v) { return rtrim(rtrim(number_format($v, 3, '.', ''), '0'), '.'); };

$xy = array();
foreach ($points as $i => $p) {
	$xy[] = array($n > 1 ? $i * $gap : 50, 100 - ($p['count'] / $top) * 100);
}

// Monotone cubic (Fritsch–Carlson) tangents, then one Bézier segment per pair of points.
$line = 'M'.$f($xy[0][0]).' '.$f($xy[0][1]);
if ($n > 1) {
	$d = array();
	for ($i = 0; $i < $n - 1; $i++) {
		$d[$i] = ($xy[$i + 1][1] - $xy[$i][1]) / ($xy[$i + 1][0] - $xy[$i][0]);
	}
	$m = array($d[0]);
	for ($i = 1; $i < $n - 1; $i++) {
		$m[$i] = ($d[$i - 1] * $d[$i] <= 0) ? 0 : ($d[$i - 1] + $d[$i]) / 2;
	}
	$m[$n - 1] = $d[$n - 2];
	for ($i = 0; $i < $n - 1; $i++) {
		if ($d[$i] == 0) {
			$m[$i] = $m[$i + 1] = 0;
			continue;
		}
		$a = $m[$i] / $d[$i];
		$b = $m[$i + 1] / $d[$i];
		if ($a * $a + $b * $b > 9) {
			$t = 3 / sqrt($a * $a + $b * $b);
			$m[$i] = $t * $a * $d[$i];
			$m[$i + 1] = $t * $b * $d[$i];
		}
	}
	for ($i = 0; $i < $n - 1; $i++) {
		$dx = ($xy[$i + 1][0] - $xy[$i][0]) / 3;
		$line .= ' C'.$f($xy[$i][0] + $dx).' '.$f($xy[$i][1] + $m[$i] * $dx)
			.' '.$f($xy[$i + 1][0] - $dx).' '.$f($xy[$i + 1][1] - $m[$i + 1] * $dx)
			.' '.$f($xy[$i + 1][0]).' '.$f($xy[$i + 1][1]);
	}
}
$area = $line.' L'.$f($xy[$n - 1][0]).' 100 L'.$f($xy[0][0]).' 100 Z';
?>
<div class="sp-chart-scroll">
	<div class="sp-chart-frame" role="img" aria-labelledby="<?php echo $id; ?>-title">
		<span class="visually-hidden" id="<?php echo $id; ?>-title">Orders per <?php echo $period === 'daily' ? 'day' : ($period === 'monthly' ? 'month' : 'year'); ?>, <?php echo html_escape(strtolower($series['caption'])); ?>: <?php echo $noun($series['total']); ?></span>
		<div class="sp-chart-y" aria-hidden="true">
			<?php for ($i = 0; $i <= $lines; $i++): ?>
			<span style="top: <?php echo $f(100 - $i / $lines * 100); ?>%"><?php echo number_format($step * $i); ?></span>
			<?php endfor; ?>
		</div>
		<div class="sp-chart-plot" data-sp-chart-plot>
			<svg class="sp-chart" viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true" focusable="false">
				<defs>
					<linearGradient id="<?php echo $id; ?>-fill" x1="0" y1="0" x2="0" y2="1">
						<stop offset="0" class="sp-chart-stop-top"></stop>
						<stop offset="1" class="sp-chart-stop-bottom"></stop>
					</linearGradient>
				</defs>
				<g class="sp-chart-grid">
					<?php for ($i = 0; $i <= $lines; $i++): ?>
					<?php $gy = $f(100 - $i / $lines * 100); ?>
					<line class="<?php echo $i === 0 ? 'is-base' : ''; ?>" x1="0" x2="100" y1="<?php echo $gy; ?>" y2="<?php echo $gy; ?>" vector-effect="non-scaling-stroke"></line>
					<?php endfor; ?>
				</g>
				<g class="sp-chart-drawn">
					<path class="sp-chart-area" d="<?php echo $area; ?>" fill="url(#<?php echo $id; ?>-fill)"></path>
					<path class="sp-chart-line" d="<?php echo $line; ?>" vector-effect="non-scaling-stroke"></path>
				</g>
				<line class="sp-chart-guide" x1="0" x2="0" y1="0" y2="100" vector-effect="non-scaling-stroke" data-sp-chart-guide></line>
			</svg>
			<div class="sp-chart-points" aria-hidden="true">
				<?php foreach ($xy as $i => $pt): ?>
				<span class="sp-chart-point<?php echo $n > 16 ? ' is-small' : ''; ?>" style="left: <?php echo $f($pt[0]); ?>%; top: <?php echo $f($pt[1]); ?>%" data-sp-chart-dot="<?php echo $i; ?>"></span>
				<?php endforeach; ?>
			</div>
			<svg class="sp-chart-hits" viewBox="0 0 100 100" preserveAspectRatio="none" focusable="false">
				<?php foreach ($points as $i => $p): ?>
				<?php
				$label = $p['title'].': '.$noun($p['count']);
				$x0    = $n > 1 ? max(0, $xy[$i][0] - $gap / 2) : 0;
				$x1    = $n > 1 ? min(100, $xy[$i][0] + $gap / 2) : 100;
				?>
				<rect class="sp-chart-hit" x="<?php echo $f($x0); ?>" y="0" width="<?php echo $f($x1 - $x0); ?>" height="100"
					tabindex="0" aria-label="<?php echo html_escape($label); ?>" data-sp-chart-point="<?php echo $i; ?>"
					data-title="<?php echo html_escape($p['title']); ?>" data-count="<?php echo html_escape($noun($p['count'])); ?>"
					data-x="<?php echo $f($xy[$i][0]); ?>" data-y="<?php echo $f($xy[$i][1]); ?>"><title><?php echo html_escape($label); ?></title></rect>
				<?php endforeach; ?>
			</svg>
			<div class="sp-chart-tip" data-sp-chart-tip hidden><span class="sp-chart-tip-title"></span><strong class="sp-chart-tip-value"></strong></div>
		</div>
		<div class="sp-chart-x" aria-hidden="true">
			<?php foreach ($points as $i => $p): ?>
			<?php if ($i % $every === 0): ?>
			<span class="<?php echo $n > 1 && $i === 0 ? 'is-first' : ($n > 1 && $i === $n - 1 ? 'is-last' : ''); ?>" style="left: <?php echo $f($xy[$i][0]); ?>%"><?php echo html_escape($p['label']); ?></span>
			<?php endif; ?>
			<?php endforeach; ?>
		</div>
	</div>
</div>
<table class="visually-hidden">
	<caption>Orders, <?php echo html_escape(strtolower($series['caption'])); ?></caption>
	<thead><tr><th scope="col"><?php echo $period === 'daily' ? 'Day' : ($period === 'monthly' ? 'Month' : 'Year'); ?></th><th scope="col">Orders</th></tr></thead>
	<tbody>
		<?php foreach ($points as $p): ?>
		<tr><th scope="row"><?php echo html_escape($p['title']); ?></th><td><?php echo number_format($p['count']); ?></td></tr>
		<?php endforeach; ?>
	</tbody>
</table>
