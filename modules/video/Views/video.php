<?php
/*
| /videos-gallery — html/video-gallery.htm. $videos = [title, meta, embed, link,
| thumb] (Video::index); $unavailable = the videos could not be read.
| The site script (customcart.js) plays a button's data-video (YouTube) in #ktVideo.
*/
echo view('top_application');
?>
<?php echo view('inner_hero', array('hero' => array('title' => 'Video Gallery', 'banner' => 'Videos Gallery', 'crumbs' => array('Video Gallery' => NULL)))); ?>
<section class="container-xxl kt-container kt-sec" data-reveal>
<?php if ( ! $videos): ?>
  <p class="kt-text-2 text-center mb-0"><?php echo $unavailable ? 'The videos are not available right now. Please try again in a few minutes.' : 'Videos will be added here soon.'; ?></p>
<?php else: ?>
  <div class="row row-cols-2 row-cols-md-3 row-cols-xl-4 kt-g-gal">
<?php foreach ($videos as $video): ?>
        <div class="col"><div class="kt-gal-item position-relative ratio ratio-1x1 rounded-5 overflow-hidden bg-primary-subtle"><div><img loading="lazy" fetchpriority="low" decoding="async" src="<?php echo html_escape($video['thumb']); ?>" alt="Video: <?php echo html_escape($video['title']); ?>" class="w-100 h-100 object-fit-cover"><span class="kt-gal-vid position-absolute rounded-pill text-white fw-bold d-flex align-items-center pe-none"><i class="bi bi-camera-video"></i>VIDEO</span><button type="button" class="btn kt-gal-btn kt-gal-play position-absolute rounded-circle d-flex align-items-center justify-content-center" data-bs-toggle="modal" data-bs-target="#ktVideo" data-video="<?php echo html_escape($video['link']); ?>" aria-label="Play video: <?php echo html_escape($video['title']); ?>"><i class="bi bi-play-fill"></i></button></div></div></div>
<?php endforeach; ?>
  </div>
<?php endif; ?>
</section>

<div class="modal fade kt-lightbox" id="ktVideo" tabindex="-1" aria-label="Video player" aria-hidden="true">
<div class="modal-dialog modal-fullscreen">
<div class="modal-content kt-lb kt-lb-v kt-lb-clear position-relative border-0 rounded-0 d-flex align-items-center justify-content-center">
<button type="button" class="btn btn-kt-prev kt-lb-close position-absolute rounded-circle d-flex align-items-center justify-content-center" data-bs-dismiss="modal" aria-label="Close"><i class="bi bi-x-lg"></i></button>
<div class="kt-lb-frame kt-lb-video position-relative overflow-hidden"><iframe src="about:blank" title="CustomCart video" class="d-block w-100 h-100 border-0" id="ktVideoFrame" allow="autoplay; encrypted-media; picture-in-picture; fullscreen" allowfullscreen></iframe></div>
</div>
</div>
</div>
<?php echo view('bottom_application'); ?>
