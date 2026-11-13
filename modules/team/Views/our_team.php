<?php
/*
| /our-team (Team::index), built from html/our-team.htm. $members = active
| team rows + image_url, $unavailable.
*/
echo view('top_application');
echo view('inner_hero', array('hero' => array('title' => 'Our Team', 'banner' => 'Our Team', 'crumbs' => array('About Us' => site_url('aboutus'), 'Our Team' => NULL))));
?>
<!-- OUR TEAM -->
<section class="container-xxl kt-container kt-sec" data-reveal>
  <div class="text-center kt-mb-56">
    <div class="kt-eyebrow d-inline-flex align-items-center fw-bold text-primary mb-3">OUR PEOPLE</div>
    <h2 class="kt-h2 mb-0 fw-bold text-dark">Meet Our Team</h2>
  </div>
<?php if ( ! $members): ?>
  <p class="text-center text-body-secondary mb-0"><?php echo $unavailable ? 'Our team could not be loaded. Please try again in a few minutes.' : 'Our team profiles will be added soon.'; ?></p>
<?php else: ?>
  <div class="row row-cols-2 row-cols-md-3 row-cols-xl-4 kt-g-gal">
<?php foreach ($members as $row): ?>
    <div class="col"><div class="d-flex flex-column gap-3"><div class="kt-member-img ratio ratio-1x1 rounded-5 overflow-hidden bg-primary-subtle"><?php if ($row['image_url'] !== ''): ?><img loading="lazy" fetchpriority="low" decoding="async" src="<?php echo html_escape($row['image_url']); ?>" alt="<?php echo html_escape($row['name']); ?>" class="object-fit-cover"><?php endif; ?></div><div class="text-center"><div class="kt-fs-20 fw-bold text-dark"><?php echo html_escape($row['name']); ?></div><?php if ((string) $row['designation'] !== ''): ?><div class="kt-member-role text-primary mt-1"><?php echo html_escape($row['designation']); ?></div><?php endif; ?></div></div></div>
<?php endforeach; ?>
  </div>
<?php endif; ?>
</section>
<?php echo view('bottom_application'); ?>
