<?php
/*
| Sections come from tbl_admin_sections (headings = parent rows), already
| filtered to what this admin may open — see AdminAccess::menu().
| The active item is the controller's 'menu_highlight' config item.
| The "Other Management" heading is an expandable group (open on its own
| pages) that always ends with Logout, so it is shown even without sections.
*/
$active_menu    = (string) $menu_highlight;
$group_name     = \Modules\Sitepanel\Libraries\AdminAccess::OTHER_MANAGEMENT;
$group_sections = ($menu !== FALSE && isset($menu[$group_name])) ? $menu[$group_name] : array();
$other_group_open = in_array($active_menu, array_column($group_sections, 'controller'), TRUE);
// Shown in this order, after Dashboard and before Other Management.
$standalone_groups = array(
	'testimonial' => array('label' => 'Testimonials', 'icon' => 'message', 'id' => 'sp-nav-testimonials', 'sections' => array()),
	'team'        => array('label' => 'Team & Partners', 'icon' => 'users', 'id' => 'sp-nav-team', 'sections' => array()),
	'news'        => array('label' => 'News & Events', 'icon' => 'banner', 'id' => 'sp-nav-news-events', 'sections' => array()),
	'newsletter'  => array('label' => 'Newsletter', 'icon' => 'mail', 'id' => 'sp-nav-newsletter', 'sections' => array()),
	// Manage Product Enquiry, Manage Enquiry and Refer a Friend (their tbl_admin_sections order).
	'enquiry'     => array('label' => 'Enquiries', 'icon' => 'inbox', 'id' => 'sp-nav-enquiries', 'sections' => array()),
);
// Further sections shown inside a group keyed by another controller.
$group_of = array('product_enquiry' => 'enquiry', 'referrals' => 'enquiry');
// Headings shown as expandable groups, in their tbl_admin_sections order (the
// reference sitepanel's store groups) => icon. Other headings ("General") are plain links.
$heading_groups = array(
	'Product Management' => 'box',
	'Members Management' => 'users',
	'Manage Orders'      => 'cart',
	'Manage Entities'    => 'sliders',
);

if ($menu !== FALSE)
{
	foreach ($menu as $heading => $sections)
	{
		foreach ($sections as $index => $section)
		{
			$group_key = isset($group_of[$section['controller']]) ? $group_of[$section['controller']] : $section['controller'];
			if (isset($standalone_groups[$group_key]))
			{
				$standalone_groups[$group_key]['sections'][] = $section;
				unset($menu[$heading][$index]);
			}
		}

		if (empty($menu[$heading]))
		{
			unset($menu[$heading]);
		}
	}

	$group_sections = isset($menu[$group_name]) ? $menu[$group_name] : array();
	$other_group_open = in_array($active_menu, array_column($group_sections, 'controller'), TRUE);
}
$has_meta_section = in_array('meta', array_column($group_sections, 'controller'), TRUE);
$show_meta_fallback = ! $has_meta_section && (int) $admin['admin_type'] === \Modules\Sitepanel\Libraries\AdminAccess::SUPER_ADMIN;
if (($show_meta_fallback && $active_menu === 'meta') || $active_menu === 'change_password')
{
	$other_group_open = TRUE;
}

$section_item = function (array $section) use ($active_menu) {
	$is_active = ($active_menu === $section['controller']);
	return '<li><a class="sp-nav-link'.($is_active ? ' is-active' : '').'" href="'.html_escape(site_url('sitepanel/'.$section['controller'])).'" title="'.html_escape($section['title']).'"'
		.($is_active ? ' aria-current="page"' : '').'>'
		.sp_icon(sp_section_icon($section['controller']))
		.' <span class="sp-nav-label">'.html_escape($section['title']).'</span></a></li>';
};
?>
<aside class="sp-sidebar" id="sp-sidebar" aria-label="Sitepanel navigation">
	<a class="sp-sidebar-brand" href="<?php echo site_url('sitepanel/dashboard'); ?>" title="<?php echo html_escape($site_name); ?>">
		<span class="sp-brand-logo"><img src="<?php echo str_starts_with((string) $site_logo, 'uploads/') ? base_url($site_logo) : theme_url().'images/'.$site_logo; ?>" width="163" height="37" alt="<?php echo html_escape($site_name); ?> logo"></span>
	</a>

	<nav class="sp-nav">
		<?php if ($menu === FALSE): ?>
			<p class="sp-nav-heading">Menu unavailable</p>
		<?php else: ?>
			<?php foreach ($menu as $heading => $sections): ?>
			<?php if ($heading === $group_name) { continue; } ?>
			<?php if ( ! isset($heading_groups[$heading])): ?>
			<?php /* Heading text ("General") is not shown: the plain links (Dashboard) sit at the top of the sidebar. */ ?>
			<ul>
				<?php foreach ($sections as $section): ?>
				<?php echo $section_item($section); ?>
				<?php endforeach; ?>
			</ul>
			<?php else: ?>
			<?php /* A reference sitepanel group (Product Management, Manage Orders...): expandable, open on its own pages. */ ?>
			<?php $group_open = in_array($active_menu, array_column($sections, 'controller'), TRUE); $group_id = 'sp-nav-'.trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($heading)), '-'); ?>
			<div class="sp-nav-group">
				<button class="sp-nav-heading sp-nav-group-toggle" type="button" data-sp-nav-group aria-expanded="<?php echo $group_open ? 'true' : 'false'; ?>" aria-controls="<?php echo $group_id; ?>">
					<span class="sp-nav-group-title"><?php echo sp_icon($heading_groups[$heading]); ?><span><?php echo html_escape($heading); ?></span></span>
					<?php echo sp_icon('chevron'); ?>
				</button>
				<ul class="sp-nav-group-list" id="<?php echo $group_id; ?>"<?php echo $group_open ? '' : ' hidden'; ?>>
					<?php foreach ($sections as $section): ?>
					<?php echo $section_item($section); ?>
					<?php endforeach; ?>
				</ul>
			</div>
			<?php endif; ?>
			<?php endforeach; ?>
		<?php endif; ?>

		<?php foreach ($standalone_groups as $controller => $group): ?>
		<?php if (empty($group['sections'])) { continue; } ?>
		<?php $group_open = in_array($active_menu, array_column($group['sections'], 'controller'), TRUE); ?>
		<div class="sp-nav-group">
			<button class="sp-nav-heading sp-nav-group-toggle" type="button" data-sp-nav-group aria-expanded="<?php echo $group_open ? 'true' : 'false'; ?>" aria-controls="<?php echo html_escape($group['id']); ?>">
				<span class="sp-nav-group-title"><?php echo sp_icon($group['icon']); ?><span><?php echo html_escape($group['label']); ?></span></span>
				<?php echo sp_icon('chevron'); ?>
			</button>
			<ul class="sp-nav-group-list" id="<?php echo html_escape($group['id']); ?>"<?php echo $group_open ? '' : ' hidden'; ?>>
				<?php foreach ($group['sections'] as $section): ?>
				<?php echo $section_item($section); ?>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php endforeach; ?>

		<div class="sp-nav-group">
			<button class="sp-nav-heading sp-nav-group-toggle" type="button" data-sp-nav-group aria-expanded="<?php echo $other_group_open ? 'true' : 'false'; ?>" aria-controls="sp-nav-other">
				<span class="sp-nav-group-title"><?php echo sp_icon('sliders'); ?><span><?php echo html_escape($group_name); ?></span></span>
				<?php echo sp_icon('chevron'); ?>
			</button>
			<ul class="sp-nav-group-list" id="sp-nav-other"<?php echo $other_group_open ? '' : ' hidden'; ?>>
				<?php foreach ($group_sections as $section): ?>
				<?php echo $section_item($section); ?>
				<?php endforeach; ?>
				<?php if ($show_meta_fallback): ?>
				<?php echo $section_item(array('title' => 'Manage Meta Tags', 'controller' => 'meta')); ?>
				<?php endif; ?>
				<?php echo $section_item(array('title' => 'Change Password', 'controller' => 'change_password')); ?>
				<li>
					<?php echo form_open('sitepanel/logout', array('class' => 'sp-nav-form')); ?>
						<button class="sp-nav-link" type="submit" title="Logout"><?php echo sp_icon('logout'); ?> <span class="sp-nav-label">Logout</span></button>
					<?php echo form_close(); ?>
				</li>
			</ul>
		</div>
	</nav>
</aside>
