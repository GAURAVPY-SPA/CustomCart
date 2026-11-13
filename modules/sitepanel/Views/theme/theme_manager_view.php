<?php 
echo sp_view('includes/header'); ?>

<?php if ($errors): ?>
<div class="alert alert-danger" role="alert">
	<p class="mb-1 fw-semibold">Theme was not changed:</p>
	<ul class="mb-0"><?php foreach ($errors as $error): ?><li><?php echo html_escape($error); ?></li><?php endforeach; ?></ul>
</div>
<?php endif; ?>

<section class="sp-panel mb-4" aria-labelledby="current-theme-title">
	<header class="sp-panel-head"><h2 class="sp-panel-title" id="current-theme-title">Currently active theme</h2></header>
	<p class="mb-3">
		<strong><?php echo $active_theme === 'custom' ? 'Existing custom theme' : html_escape($presets[$active_theme]['name']); ?></strong>
		<?php if ($active_theme === 'custom'): ?>
		<span class="sp-muted">— preserved as found; choosing a preset below will not apply until you save.</span>
		<?php endif; ?>
	</p>
	<div class="d-flex flex-wrap gap-3">
		<?php foreach ($current_palette as $label => $color): ?>
		<div class="d-flex align-items-center gap-2">
			<span aria-hidden="true" style="display:inline-block;width:24px;height:24px;border:1px solid #888;border-radius:4px;background-color:<?php echo html_escape($color); ?>"></span>
			<span class="small"><?php echo html_escape($label); ?> <code><?php echo html_escape($color); ?></code></span>
		</div>
		<?php endforeach; ?>
	</div>
</section>

<?php if ( ! $can_edit): ?>
<div class="alert alert-info" role="status">You can view the theme options but cannot apply them.</div>
<?php endif; ?>

<?php echo form_open('sitepanel/theme', array('novalidate' => 'novalidate'), array('form' => 'stat_gradients')); ?>
<section class="sp-panel mb-4" aria-labelledby="stat-gradients-title">
	<header class="sp-panel-head"><h2 class="sp-panel-title" id="stat-gradients-title">Dashboard Statistics Card Gradients</h2></header>
	<fieldset<?php echo $can_edit ? '' : ' disabled'; ?>>
		<p class="sp-muted">Each dashboard summary card has its own gradient. The card text turns white or dark automatically, whichever reads better on the colors you pick. Theme presets below do not change these gradients.</p>
		<?php foreach ($gradients as $key => $g): ?>
		<?php
			$id    = 'grad-'.$key;
			$angle = (ctype_digit((string) $g['angle']) && isset($angles[(int) $g['angle']])) ? (int) $g['angle'] : (int) $g['default']['angle'];
		?>
		<div class="sp-grad-card" data-sp-grad-card>
			<h3 class="sp-stat-label mt-3 mb-2" id="<?php echo html_escape($id); ?>-title"><?php echo html_escape($g['label']); ?> card</h3>
			<div class="row g-3 align-items-end">
				<div class="col-12 col-lg-3">
					<div class="sp-stat sp-stat--neutral sp-stat--grad" data-sp-grad-preview aria-hidden="true"
						style="--sp-g-start:<?php echo html_escape($g['start_picker']); ?>;--sp-g-end:<?php echo html_escape($g['end_picker']); ?>;--sp-g-angle:<?php echo $angle; ?>deg;--sp-g-text:<?php echo html_escape($g['text']); ?>">
						<span class="sp-stat-icon"><?php echo sp_icon(sp_section_icon($key)); ?></span>
						<div class="sp-stat-body">
							<p class="sp-stat-value">128</p>
							<p class="sp-stat-label"><?php echo html_escape($g['label']); ?></p>
							<p class="sp-stat-meta">Live preview</p>
						</div>
					</div>
				</div>
				<?php foreach (array('start' => 'Start color', 'end' => 'End color') as $stop => $stop_label): ?>
				<div class="col-6 col-md-4 col-lg-2">
					<label class="form-label" for="<?php echo html_escape($id.'-'.$stop); ?>"><?php echo $stop_label; ?></label>
					<div class="d-flex align-items-center gap-2">
						<input class="form-control form-control-color" type="color" value="<?php echo html_escape($g[$stop.'_picker']); ?>" data-sp-color-for="<?php echo html_escape($id.'-'.$stop); ?>" aria-label="Pick: <?php echo html_escape($g['label'].' '.strtolower($stop_label)); ?>">
						<input class="form-control" type="text" id="<?php echo html_escape($id.'-'.$stop); ?>" name="gradients[<?php echo html_escape($key); ?>][<?php echo $stop; ?>]" value="<?php echo html_escape($g[$stop]); ?>" data-sp-grad="<?php echo $stop; ?>" maxlength="7" spellcheck="false" autocomplete="off" style="max-width:7rem">
					</div>
				</div>
				<?php endforeach; ?>
				<div class="col-12 col-md-4 col-lg-3">
					<label class="form-label" for="<?php echo html_escape($id.'-angle'); ?>">Direction</label>
					<select class="form-select" id="<?php echo html_escape($id.'-angle'); ?>" name="gradients[<?php echo html_escape($key); ?>][angle]" data-sp-grad="angle">
						<?php foreach ($angles as $deg => $angle_label): ?>
						<option value="<?php echo (int) $deg; ?>"<?php echo (string) $deg === (string) $g['angle'] ? ' selected' : ''; ?>><?php echo html_escape($angle_label); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="col-12 col-lg-2">
					<?php if ($can_edit): ?>
					<button class="btn sp-btn-outline w-100" type="submit" name="reset_card" value="<?php echo html_escape($key); ?>"
						title="Default: <?php echo html_escape($g['default']['start'].' → '.$g['default']['end']); ?>">Reset to default</button>
					<?php endif; ?>
				</div>
			</div>
			<p class="small mt-2 mb-0" data-sp-grad-note>
				Text: <?php echo $g['text'] === '#ffffff' ? 'white' : 'dark'; ?> · contrast <?php echo number_format($g['contrast'], 2); ?>:1<?php if ($g['contrast'] < 4.5): ?> — <strong class="text-danger">low; choose darker or lighter colors so the text stays readable</strong><?php endif; ?>
			</p>
		</div>
		<?php endforeach; ?>
		<?php if ($can_edit): ?>
		<div class="sp-form-actions mt-4">
			<button class="btn sp-btn-primary" type="submit">Save card gradients</button>
		</div>
		<?php endif; ?>
	</fieldset>
</section>
<?php echo form_close(); ?>

<?php echo form_open('sitepanel/theme', array('novalidate' => 'novalidate'), array('form' => 'custom_colors')); ?>
<section class="sp-panel mb-4" aria-labelledby="custom-colors-title">
	<header class="sp-panel-head"><h2 class="sp-panel-title" id="custom-colors-title">Custom colors</h2></header>
	<fieldset<?php echo $can_edit ? '' : ' disabled'; ?>>
		<p class="sp-muted">The left sidebar and every sitepanel button. Applying a theme preset below resets these to that theme's colors; change them here afterwards to override.</p>
		<?php foreach ($custom as $group => $fields): ?>
		<h3 class="sp-stat-label mt-3 mb-2"><?php echo html_escape($group); ?></h3>
		<div class="row g-3">
			<?php foreach ($fields as $name => $f): ?>
			<?php $id = 'color-'.$name; ?>
			<div class="col-12 col-md-6 col-xl-4">
				<label class="form-label" for="<?php echo html_escape($id); ?>"><?php echo html_escape($f['label']); ?></label>
				<div class="d-flex align-items-center gap-2">
					<input class="form-control form-control-color" type="color" value="<?php echo html_escape($f['picker']); ?>" data-sp-color-for="<?php echo html_escape($id); ?>" aria-label="Pick: <?php echo html_escape($f['label']); ?>">
					<input class="form-control" type="text" id="<?php echo html_escape($id); ?>" name="colors[<?php echo html_escape($name); ?>]" value="<?php echo html_escape($f['value']); ?>" maxlength="9" spellcheck="false" autocomplete="off" style="max-width:9rem">
				</div>
			</div>
			<?php endforeach; ?>
		</div>
		<?php endforeach; ?>
		<?php if ($can_edit): ?>
		<div class="sp-form-actions mt-4">
			<button class="btn sp-btn-primary" type="submit">Save custom colors</button>
		</div>
		<?php endif; ?>
	</fieldset>
</section>
<?php echo form_close(); ?>

<?php echo form_open('sitepanel/theme', array('novalidate' => 'novalidate')); ?>
<fieldset<?php echo $can_edit ? '' : ' disabled'; ?>>
	<div class="row g-3">
		<?php foreach ($presets as $theme_id => $preset): ?>
		<?php $palette = $preset['palette']; ?>
		<div class="col-12 col-xl-6">
			<section class="sp-panel h-100" aria-labelledby="theme-<?php echo html_escape($theme_id); ?>">
				<div class="d-flex align-items-start gap-3">
					<input class="form-check-input mt-1" type="radio" id="theme-<?php echo html_escape($theme_id); ?>" name="theme_id" value="<?php echo html_escape($theme_id); ?>"<?php echo set_radio('theme_id', $theme_id, $active_theme === $theme_id); ?>>
					<div class="flex-grow-1">
						<h2 class="sp-panel-title mb-2"><label for="theme-<?php echo html_escape($theme_id); ?>"><?php echo html_escape($preset['name']); ?></label></h2>
						<p class="sp-muted">Select this coordinated palette for Sitepanel colors only.</p>
						<div class="d-flex flex-wrap gap-3" aria-label="<?php echo html_escape($preset['name']); ?> color palette">
							<?php foreach ($palette as $label => $color): ?>
							<div class="text-center small">
								<span aria-hidden="true" style="display:block;width:38px;height:30px;border:1px solid #888;border-radius:4px;background-color:<?php echo html_escape($color); ?>"></span>
								<span><?php echo html_escape(ucfirst($label)); ?></span><br>
								<code><?php echo html_escape($color); ?></code>
							</div>
							<?php endforeach; ?>
						</div>
					</div>
				</div>
			</section>
		</div>
		<?php endforeach; ?>
	</div>
	<?php if ($can_edit): ?>
	<div class="sp-form-actions mt-4 mb-4">
		<button class="btn sp-btn-primary" type="submit">Save and apply selected theme</button>
		<a class="btn sp-btn-outline" href="<?php echo site_url('sitepanel/dashboard'); ?>">Cancel</a>
	</div>
	<?php endif; ?>
</fieldset>
<?php echo form_close(); ?>

<?php echo sp_view('includes/footer'); ?>
