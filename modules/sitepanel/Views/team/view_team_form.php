<?php
/* Add / edit a team member or partner. $res = the row (empty values when adding), $image = current image path ('' = none). */
use Modules\Team\Models\TeamModel;

$is_edit = isset($res['team_id']);
$action  = $is_edit ? 'sitepanel/team/edit/'.(int) $res['team_id'] : 'sitepanel/team/add';
$type    = set_value('team_type', (string) $res['team_type']);
echo sp_view('includes/header'); ?>
<?php echo sp_view('includes/form_errors', array('errors' => $errors)); ?>

<?php echo form_open_multipart($action, array('novalidate' => 'novalidate')); ?>
	<section class="sp-panel mb-4" aria-labelledby="form-title">
		<header class="sp-panel-head"><h2 class="sp-panel-title" id="form-title"><?php echo $is_edit ? 'Edit entry' : 'New entry'; ?></h2></header>
		<div class="row g-3">
			<div class="col-12 col-md-4">
				<label class="form-label" for="team_type">Type <span class="text-danger">*</span></label>
				<select class="form-select" id="team_type" name="team_type" required>
					<?php foreach (TeamModel::TYPES as $key => $label): ?>
					<option value="<?php echo $key; ?>"<?php echo $type === $key ? ' selected' : ''; ?>><?php echo $label; ?></option>
					<?php endforeach; ?>
				</select>
				<div class="form-text">Team members are shown on Our Team; partners on Partners and the homepage logo strip.</div>
			</div>
			<div class="col-12 col-md-8">
				<label class="form-label" for="name">Name <span class="text-danger">*</span></label>
				<input class="form-control" type="text" id="name" name="name" maxlength="150" required value="<?php echo set_value('name', (string) $res['name']); ?>">
				<div class="form-text">The person's or the company's name (also the image's alt text).</div>
			</div>
			<div class="col-12">
				<label class="form-label" for="designation">Designation</label>
				<input class="form-control" type="text" id="designation" name="designation" maxlength="150" value="<?php echo set_value('designation', (string) $res['designation']); ?>">
				<div class="form-text">Team members: the role shown under the name, e.g. <em>Production Manager</em>. Optional for partners.</div>
			</div>
			<div class="col-12">
				<label class="form-label" for="biography">Description</label>
				<textarea class="form-control" id="biography" name="biography" rows="4" maxlength="2000"><?php echo set_value('biography', (string) $res['biography']); ?></textarea>
				<div class="form-text">Optional, up to 2000 characters, for your records (the design shows photos, names and roles only).</div>
			</div>
			<div class="col-12">
				<label class="form-label" for="photo">Photo / logo<?php if ( ! $is_edit): ?> <span class="text-danger">*</span><?php endif; ?></label>
				<?php if ($is_edit && $image !== ''): ?>
				<p class="mb-2"><img class="sp-thumb" src="<?php echo html_escape(get_image('team', basename($image), 192, 108, 'R')); ?>" alt="Current image"></p>
				<?php endif; ?>
				<input class="form-control" type="file" id="photo" name="photo" accept=".jpg,.jpeg,.png,.gif,.webp,image/jpeg,image/png,image/gif,image/webp"<?php echo $is_edit ? '' : ' required'; ?>>
				<div class="form-text">JPG, PNG, GIF or WebP, up to 2 MB. Best <?php echo \Modules\Sitepanel\Controllers\Team::BEST_SIZE; ?>.<?php echo $is_edit ? ' Leave empty to keep the current image.' : ''; ?></div>
			</div>
		</div>
	</section>

	<div class="sp-form-actions mb-4">
		<button class="btn sp-btn-primary" type="submit"><?php echo $is_edit ? 'Save changes' : sp_icon('plus').' '.'Add entry'; ?></button>
		<a class="btn sp-btn-outline" href="<?php echo site_url('sitepanel/team'); ?>">Cancel</a>
	</div>
<?php echo form_close(); ?>
<?php echo sp_view('includes/footer'); ?>
