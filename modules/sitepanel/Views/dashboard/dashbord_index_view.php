<?php
/* Dashboard: six summary cards, the Store overview ($store = DashboardModel::store_overview():
 * recent orders, order status, recent products, low-stock alerts), the Recent records box
 * ($recent = Dashboard::recent_boxes()), then the orders graph ($chart = Dashboard::orders_chart(),
 * NULL without access to Manage Orders; $period = the one shown). */
echo sp_view('includes/header');
$status_tone = array('Pending' => 'warn', 'Ready For Dispatch' => 'info', 'Dispatched' => 'info', 'Delivered' => 'ok', 'Closed' => 'ok', 'Canceled' => 'error', 'Rejected' => 'error', 'Return' => 'warn');
?>
<section class="sp-stats sp-stats--6" aria-label="Summary">
	<?php foreach ($cards as $card): ?>
		<?php echo sp_view('includes/stat_card', array('card' => $card)); ?>
	<?php endforeach; ?>
</section>

<div class="sp-overview">
	<?php if ($store['can_orders']): ?>
	<section class="sp-panel sp-overview-orders" aria-labelledby="ov-orders">
		<header class="sp-panel-head"><h2 class="sp-panel-title" id="ov-orders">Recent orders</h2><a class="sp-link" href="<?php echo site_url('sitepanel/orders'); ?>">View all</a></header>
		<?php if ($store['recent_orders'] === FALSE): ?>
			<p class="sp-empty mb-0">Orders could not be loaded. Please try again shortly.</p>
		<?php elseif ( ! $store['recent_orders']): ?>
			<p class="sp-empty mb-0">No orders yet.</p>
		<?php else: ?>
		<div class="table-responsive">
			<table class="table sp-table sp-ov-table mb-0">
				<thead><tr><th scope="col">Order</th><th scope="col">Customer</th><th scope="col">Date</th><th scope="col">Payment</th><th scope="col">Status</th><th scope="col" class="text-end">Total</th></tr></thead>
				<tbody>
				<?php foreach ($store['recent_orders'] as $o): ?>
				<tr>
					<td><a class="sp-link" href="<?php echo site_url('sitepanel/orders/view/'.(int) $o['order_id']); ?>"><strong><?php echo html_escape($o['invoice_number']); ?></strong></a></td>
					<td><?php echo html_escape(trim($o['first_name'].' '.$o['last_name'])); ?></td>
					<td><?php echo html_escape(date('j M Y', strtotime((string) $o['order_received_date']))); ?></td>
					<td><span class="sp-badge sp-badge--<?php echo $o['payment_status'] === 'Paid' ? 'ok' : 'warn'; ?>"><?php echo html_escape($o['payment_status']); ?></span></td>
					<td><span class="sp-badge sp-badge--<?php echo $status_tone[$o['order_status']] ?? 'info'; ?>"><?php echo html_escape($o['order_status']); ?></span></td>
					<td class="text-end"><?php echo html_escape(($o['currency_code'] ?: $currency).' '.number_format((float) $o['grand_total'], 2)); ?></td>
				</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php endif; ?>
	</section>

	<section class="sp-panel sp-overview-status" aria-labelledby="ov-status">
		<header class="sp-panel-head"><h2 class="sp-panel-title" id="ov-status">Order status</h2></header>
		<?php if ($store['statuses'] === FALSE): ?>
			<p class="sp-empty mb-0">Could not be read.</p>
		<?php else: $all = array_sum($store['statuses']); ?>
			<ul class="sp-status-list">
			<?php foreach (\Modules\Cart\Models\OrderModel::STATUS_FLOW as $status => $label): $n = (int) ($store['statuses'][$status] ?? 0); ?>
				<li>
					<span class="sp-status-name"><span class="sp-status-dot sp-status-dot--<?php echo $status_tone[$status] ?? 'info'; ?>"></span><?php echo html_escape($status); ?></span>
					<span class="sp-status-bar" aria-hidden="true"><span style="width:<?php echo $all ? round($n / $all * 100) : 0; ?>%"></span></span>
					<strong><?php echo number_format($n); ?></strong>
				</li>
			<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</section>
	<?php endif; ?>

	<?php if ($store['can_products']): ?>
	<section class="sp-panel sp-overview-products" aria-labelledby="ov-products">
		<header class="sp-panel-head"><h2 class="sp-panel-title" id="ov-products">Recent products</h2><a class="sp-link" href="<?php echo site_url('sitepanel/products'); ?>">View all</a></header>
		<?php if ($store['recent_products'] === FALSE): ?>
			<p class="sp-empty mb-0">Products could not be loaded.</p>
		<?php elseif ( ! $store['recent_products']): ?>
			<p class="sp-empty mb-0">No products yet.</p>
		<?php else: ?>
		<ul class="sp-product-list">
			<?php foreach ($store['recent_products'] as $p): $thumb = $p['media'] ? get_image('products', $p['media'], 96, 96, 'C') : ''; ?>
			<li>
				<?php if ($thumb !== ''): ?><img src="<?php echo html_escape($thumb); ?>" alt="" width="48" height="48"><?php else: ?><span class="sp-product-ph"><?php echo sp_icon('box'); ?></span><?php endif; ?>
				<span class="sp-product-main"><a class="sp-link" href="<?php echo site_url('sitepanel/products/edit/'.(int) $p['products_id']); ?>"><?php echo html_escape($p['product_name']); ?></a><small><?php echo html_escape($p['product_code']); ?> · <?php echo number_format((int) $p['product_quantity']); ?> in stock</small></span>
				<span class="sp-product-price"><?php echo html_escape($currency.' '.number_format((float) $p['display_price'], 2)); ?></span>
			</li>
			<?php endforeach; ?>
		</ul>
		<?php endif; ?>
	</section>

	<section class="sp-panel sp-overview-low" aria-labelledby="ov-low">
		<header class="sp-panel-head"><h2 class="sp-panel-title" id="ov-low">Low-stock alerts</h2></header>
		<?php if ($store['low_stock'] === FALSE): ?>
			<p class="sp-empty mb-0">Stock could not be read.</p>
		<?php elseif ( ! $store['low_stock']): ?>
			<p class="sp-empty mb-0">All stock levels are above their thresholds.</p>
		<?php else: ?>
		<ul class="sp-low-list">
			<?php foreach ($store['low_stock'] as $row): $variant = implode(' / ', array_filter(array((string) $row['color_name'], (string) $row['size_name']))); ?>
			<li>
				<span class="sp-low-ico" aria-hidden="true"><?php echo sp_icon('alert'); ?></span>
				<span class="sp-product-main"><a class="sp-link" href="<?php echo site_url('sitepanel/products/stock/'.(int) $row['product_id']); ?>"><?php echo html_escape($row['product_name']); ?></a><?php if ($variant !== ''): ?><small><?php echo html_escape($variant); ?></small><?php endif; ?></span>
				<span class="sp-badge sp-badge--warn"><?php echo (int) $row['product_quantity']; ?> left</span>
			</li>
			<?php endforeach; ?>
		</ul>
		<?php endif; ?>
	</section>
	<?php endif; ?>
</div>

<section class="sp-panel mb-4" aria-labelledby="recent-title">
	<header class="sp-panel-head"><h2 class="sp-panel-title" id="recent-title">Recent records</h2></header>
	<div class="sp-recent">
		<?php foreach ($recent as $i => $box): ?>
		<article class="sp-recent-box" aria-labelledby="recent-<?php echo $i; ?>">
			<div class="sp-recent-head">
				<?php echo sp_icon($box['icon']); ?>
				<h3 class="sp-recent-title" id="recent-<?php echo $i; ?>"><?php echo html_escape($box['label']); ?></h3>
				<?php if ($box['access'] && $box['total'] !== NULL): ?><span class="sp-recent-total"><?php echo number_format($box['total']); ?></span><?php endif; ?>
			</div>
			<?php if ( ! $box['access']): ?>
				<p class="sp-recent-meta mb-0">No access to this section.</p>
			<?php else: ?>
				<?php if ($box['meta'] !== ''): ?><p class="sp-recent-meta"><?php echo html_escape($box['meta']); ?></p><?php endif; ?>
				<?php if ($box['items'] === FALSE): ?>
					<p class="sp-recent-meta">Could not be read. Please try again shortly.</p>
				<?php elseif ( ! $box['items']): ?>
					<p class="sp-recent-meta"><?php echo html_escape($box['empty']); ?>.</p>
				<?php else: ?>
					<ul class="sp-recent-list">
						<?php foreach ($box['items'] as $item): ?>
						<li><a class="sp-link" href="<?php echo html_escape($item['url']); ?>"><?php echo html_escape($item['title']); ?></a><span class="sp-recent-sub"><?php echo html_escape($item['sub']); ?></span></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
				<p class="sp-recent-foot"><a class="sp-link" href="<?php echo html_escape($box['url']); ?>">View all <?php echo html_escape(strtolower($box['label'])); ?></a></p>
			<?php endif; ?>
		</article>
		<?php endforeach; ?>
	</div>
</section>

<?php if ($chart !== NULL): ?>
<section class="sp-panel sp-chart-panel mb-4" aria-labelledby="orders-chart-title" data-sp-chart>
	<header class="sp-panel-head sp-panel-head--wrap sp-chart-head">
		<h2 class="sp-panel-title sp-chart-title" id="orders-chart-title">Orders</h2>
		<?php if ($chart !== FALSE): ?>
		<nav class="sp-chart-periods" aria-label="Orders graph period">
			<?php foreach (\Modules\Sitepanel\Controllers\Dashboard::PERIODS as $key => $info): ?>
			<a class="sp-chart-period<?php echo $key === $period ? ' is-active' : ''; ?>" href="<?php echo site_url('sitepanel/dashboard').'?period='.$key; ?>" data-sp-chart-period="<?php echo $key; ?>"<?php echo $key === $period ? ' aria-current="true"' : ''; ?>><?php echo $info[0]; ?></a>
			<?php endforeach; ?>
		</nav>
		<?php endif; ?>
	</header>

	<?php if ($chart === FALSE): ?>
		<p class="sp-empty mb-0">Orders could not be loaded. Please try again shortly.</p>
	<?php else: ?>
		<?php foreach ($chart as $key => $series): ?>
		<div class="sp-chart-view" data-sp-chart-panel="<?php echo $key; ?>"<?php echo $key === $period ? '' : ' hidden'; ?>>
			<div class="sp-chart-summary">
				<span class="sp-chart-kpi-icon" aria-hidden="true"><?php echo sp_icon('cart'); ?></span>
				<div class="sp-chart-kpi">
					<p class="sp-chart-label">Total <?php echo $series['total'] === 1 ? 'order' : 'orders'; ?></p>
					<p class="sp-chart-total"><b><?php echo number_format($series['total']); ?></b></p>
				</div>
				<p class="sp-chart-caption"><?php echo html_escape($series['caption']); ?></p>
				<?php if ($series['change'] !== NULL): ?>
				<?php $pct = $series['change']['percent']; $dir = $pct > 0 ? 'up' : ($pct < 0 ? 'down' : 'flat'); ?>
				<p class="sp-chart-change sp-chart-change--<?php echo $dir; ?>" title="<?php echo number_format($series['change']['previous']).' orders in the '.html_escape($series['change']['against']); ?>">
					<span aria-hidden="true"><?php echo $dir === 'up' ? '▲' : ($dir === 'down' ? '▼' : '■'); ?></span>
					<?php echo ($pct > 0 ? '+' : '').number_format($pct, 1); ?>%<span class="sp-chart-change-text"> vs <?php echo html_escape($series['change']['against']); ?></span>
				</p>
				<?php endif; ?>
			</div>
			<?php if ($series['total'] === 0): ?>
				<div class="sp-chart-empty">
					<?php echo sp_icon('cart'); ?>
					<p class="mb-0">No orders <?php echo $key === 'daily' ? 'in the last 30 days' : ($key === 'monthly' ? 'in the last 12 months' : 'yet'); ?>. The graph fills in as orders are received.</p>
				</div>
			<?php else: ?>
				<?php echo sp_view('dashboard/orders_chart', array('series' => $series, 'period' => $key)); ?>
			<?php endif; ?>
		</div>
		<?php endforeach; ?>
		<p class="sp-chart-foot mb-0">Deleted orders are not counted. <a class="sp-link" href="<?php echo site_url('sitepanel/orders'); ?>">View all orders</a></p>
	<?php endif; ?>
</section>
<?php endif; ?>

<?php echo sp_view('includes/footer'); ?>
