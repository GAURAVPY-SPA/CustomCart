<?php
/*
| A published static page (Sitepanel > Manage Static Pages) shown as its text,
| in the design's CMS layout (html/privacy-policy.htm, terms.htm, returns.htm,
| refunds.htm): inner banner (title, breadcrumb) and the .kt-cms area.
| $page = wl_cms_pages row, $parent = its published parent page (NULL = none),
| $content = sanitised HTML, $banner = banner positions (Pages::banner_positions()).
*/
echo view('top_application');
?>
<?php echo view('inner_hero', array('hero' => array('title' => $page['page_name'], 'banner' => $banner, 'crumbs' => ($parent ? array($parent['page_name'] => site_url($parent['friendly_url'])) : array()) + array($page['page_name'] => NULL)))); ?>
<section class="container-xxl kt-container kt-sec" data-reveal>
  <div class="kt-cms mx-auto">
<?php echo $content; ?>
  </div>
</section>
<?php echo view('bottom_application'); ?>
