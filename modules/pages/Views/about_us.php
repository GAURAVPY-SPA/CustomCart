<?php
/*
| About Us (layout about_us) — html/about-us.htm: the CMS area (the page's
| Description, Manage Static Pages), Mission & Vision (#mission-vision, the
| design's text) and the Our Team slider (Manage Team & Partners: $team, left
| out when there are no members).
| $page, $content (sanitised HTML), $banner, $team [name, designation, image].
*/
echo view('top_application');
?>
<?php echo view('inner_hero', array('hero' => array('title' => $page['page_name'], 'banner' => $banner, 'crumbs' => array($page['page_name'] => NULL)))); ?>
<section class="container-xxl kt-container kt-sec" data-reveal>
  <div class="kt-cms mx-auto">
<?php echo $content; ?>
  </div>
</section>

<section id="mission-vision" class="container-xxl kt-container kt-sec pt-0 kt-mv kt-anchor" data-reveal>
  <div class="row align-items-center kt-g-mv">
    <div class="col-12 col-xl">
      <div class="kt-mv-text position-relative text-center text-xl-end">
        <div class="kt-mv-circle position-absolute top-50 start-50 translate-middle rounded-circle pe-none"></div>
        <div class="position-relative">
          <span class="kt-mv-badge d-inline-flex align-items-center justify-content-center rounded-circle bg-primary-subtle text-primary fw-bold">MISSION</span>
          <p class="kt-mv-p mb-0">To make premium everyday clothing simple to buy online, with honest prices and fast delivery.</p>
        </div>
      </div>
    </div>
    <div class="col-12 col-xl kt-grow-11">
      <div class="kt-mv-visual position-relative w-100 mx-auto ratio ratio-1x1">
        <div>
          <div class="kt-mv-arc position-absolute rounded-circle pe-none"></div>
          <div class="position-absolute top-0 start-0 w-100 h-100 rounded-circle overflow-hidden bg-primary-subtle" data-circ><img loading="lazy" fetchpriority="low" decoding="async" src="<?php echo theme_url(); ?>images/mission-vision-image.jpg" alt="CustomCart essentials collection" class="w-100 h-100 object-fit-cover"></div>
        </div>
      </div>
    </div>
    <div class="col-12 col-xl">
      <div class="kt-mv-text position-relative text-center text-xl-start">
        <div class="kt-mv-circle position-absolute top-50 start-50 translate-middle rounded-circle pe-none"></div>
        <div class="position-relative">
          <span class="kt-mv-badge d-inline-flex align-items-center justify-content-center rounded-circle bg-dark text-white fw-bold">VISION</span>
          <p class="kt-mv-p mb-0">To be the online store people trust first for the clothes they wear every day.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<?php if ($team): ?>
<section class="container-xxl kt-container kt-sec pt-0" data-reveal>
  <div class="d-flex flex-wrap align-items-end justify-content-between kt-gap-20 kt-mb-40">
    <div>
      <div class="kt-eyebrow d-flex align-items-center fw-bold text-primary mb-3">OUR PEOPLE</div>
      <h2 class="kt-h2 mb-0 fw-bold text-dark">Meet Our Team</h2>
    </div>
    <div class="d-flex kt-gap-10">
      <button type="button" class="btn btn-kt-prev kt-btn-52 rounded-circle d-flex align-items-center justify-content-center" data-kt-slide="prev" data-kt-target="#ktTeamTrack" aria-label="Previous"><i class="bi bi-arrow-left"></i></button>
      <button type="button" class="btn btn-kt-next kt-btn-52 rounded-circle d-flex align-items-center justify-content-center" data-kt-slide="next" data-kt-target="#ktTeamTrack" aria-label="Next"><i class="bi bi-arrow-right"></i></button>
    </div>
  </div>
  <div class="kt-track d-flex overflow-x-auto" id="ktTeamTrack" data-kt-loop data-kt-auto="4000">
<?php foreach ($team as $member): ?>
    <div class="kt-team-card d-flex flex-column gap-3"><div class="kt-member-img ratio ratio-1x1 rounded-5 overflow-hidden bg-primary-subtle"><?php if ($member['image'] !== ''): ?><img loading="lazy" fetchpriority="low" decoding="async" src="<?php echo html_escape($member['image']); ?>" alt="<?php echo html_escape($member['name']); ?>" class="object-fit-cover"><?php else: ?><img loading="lazy" fetchpriority="low" decoding="async" src="<?php echo theme_url(); ?>images/placeholder.svg" alt="" class="object-fit-cover"><?php endif; ?></div><div class="text-center"><div class="kt-fs-20 fw-bold text-dark"><?php echo html_escape($member['name']); ?></div><?php if ($member['designation'] !== ''): ?><div class="kt-member-role text-primary mt-1"><?php echo html_escape($member['designation']); ?></div><?php endif; ?></div></div>
<?php endforeach; ?>
  </div>
</section>
<?php endif; ?>
<?php echo view('bottom_application'); ?>
