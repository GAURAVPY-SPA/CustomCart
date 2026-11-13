<?php
/*
| /brochure (Certificates::brochure), built from html/brochure.htm: PDF cards
| with title, description, file type and size and a download link.
| $items = rows + url, type, size; $unavailable.
*/
echo view('top_application');
echo view('inner_hero', array('hero' => array('title' => 'Brochures', 'banner' => 'Brochures', 'crumbs' => array('Brochures' => NULL))));
?>
<!-- BROCHURES: PDF icon in place of a cover image -->
<section class="container-xxl kt-container kt-sec" data-reveal>
<?php if ( ! $items): ?>
  <p class="text-center text-body-secondary mb-0"><?php echo $unavailable ? 'Brochures could not be loaded. Please try again in a few minutes.' : 'Our brochures will be added soon.'; ?></p>
<?php else: ?>
  <div class="row row-cols-1 row-cols-lg-2 kt-g-bro">
<?php foreach ($items as $item): ?>
    <div class="col">
      <div class="card kt-bro h-100 bg-white flex-row align-items-center">
        <span class="kt-bro-ico position-relative rounded-4 bg-primary-subtle text-primary d-flex align-items-center justify-content-center flex-shrink-0"><i class="bi bi-file-earmark-<?php echo $item['type'] === 'PDF' ? 'pdf' : 'text'; ?>"></i><span class="kt-bro-tag position-absolute rounded-pill bg-warning text-dark fw-bold"><?php echo html_escape($item['type']); ?></span></span>
        <div class="d-flex flex-column align-items-start flex-grow-1 kt-bro-body">
          <h2 class="kt-bro-name fw-bold text-dark mb-0"><?php echo html_escape($item['title']); ?></h2>
          <?php if ((string) $item['description'] !== ''): ?><p class="kt-bro-text text-body-secondary mb-0"><?php echo html_escape($item['description']); ?></p><?php endif; ?>
          <div class="kt-bro-foot d-flex flex-wrap align-items-center justify-content-between w-100">
            <span class="kt-bro-meta text-body-secondary"><i class="bi bi-file-earmark"></i> <?php echo html_escape($item['type']); ?> &middot; <?php echo html_escape($item['size']); ?></span>
            <a href="<?php echo html_escape($item['url']); ?>" target="_blank" rel="noopener" download class="btn btn-kt-dark kt-bro-btn rounded-pill d-inline-flex align-items-center">DOWNLOAD <i class="bi bi-download"></i></a>
          </div>
        </div>
      </div>
    </div>
<?php endforeach; ?>
  </div>
<?php endif; ?>
</section>
<?php echo view('bottom_application'); ?>
