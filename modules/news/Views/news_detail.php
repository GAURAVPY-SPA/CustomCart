<?php
/*
| /news-and-events/<slug> (News::detail), built from html/news-detail.htm:
| type, date, title, image, content (HtmlSanitizer), back link, share links
| (a[data-kt-page-share], customcart.js) and the Recent News sidebar.
| $item = wl_news row + type, time, image_url; $content; $recent = News::present() rows; $list_url.
*/
echo view('top_application');
echo view('inner_hero', array('hero' => array('title' => 'News & Events', 'banner' => 'News & Events', 'crumbs' => array('News & Events' => $list_url, $item['news_title'] => NULL))));
$share = rawurlencode(current_url());
?>
<!-- NEWS DETAIL -->
<section class="container-xxl kt-container kt-sec" data-reveal>
  <div class="row kt-g-nd">
    <article class="col-12 col-lg-8">
      <div class="kt-nd-meta d-flex flex-wrap align-items-center">
        <span class="kt-news-type fw-bold text-primary"><?php echo html_escape($item['type']); ?></span>
        <span class="kt-nd-sep rounded-circle"></span>
        <time datetime="<?php echo date('Y-m-d', $item['time']); ?>" class="text-body-secondary"><i class="bi bi-calendar3"></i> <?php echo date('d M Y', $item['time']); ?></time>
      </div>
      <h2 class="kt-nd-title fw-bold text-dark"><?php echo html_escape($item['news_title']); ?></h2>
      <?php if ($item['image_url'] !== ''): ?>
      <div class="kt-nd-img rounded-5 overflow-hidden bg-primary-subtle"><img loading="lazy" fetchpriority="low" decoding="async" src="<?php echo html_escape($item['image_url']); ?>" alt="<?php echo html_escape($item['news_title']); ?>" class="w-100 h-100 object-fit-cover"></div>
      <?php endif; ?>
      <div class="kt-cms">
<?php if (trim(strip_tags($content, '<img>')) !== ''): ?>
<?php echo $content; ?>
<?php elseif ((string) $item['short_description'] !== ''): ?>
<p><?php echo nl2br(html_escape($item['short_description'])); ?></p>
<?php endif; ?>
      </div>
      <div class="kt-nd-foot d-flex flex-wrap align-items-center justify-content-between">
        <a href="<?php echo html_escape($list_url); ?>" class="btn btn-kt-dark kt-nd-back rounded-pill d-inline-flex align-items-center"><i class="bi bi-arrow-left"></i> BACK TO NEWS</a>
        <div class="d-flex align-items-center gap-2">
          <span class="kt-nd-share fw-bold text-dark">SHARE</span>
          <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $share; ?>" target="_blank" rel="noopener" class="btn btn-kt-social rounded-circle d-flex align-items-center justify-content-center" aria-label="Share on Facebook" data-kt-page-share="facebook"><i class="bi bi-facebook"></i></a>
          <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo $share; ?>" target="_blank" rel="noopener" class="btn btn-kt-social rounded-circle d-flex align-items-center justify-content-center" aria-label="Share on LinkedIn" data-kt-page-share="linkedin"><i class="bi bi-linkedin"></i></a>
          <a href="https://twitter.com/intent/tweet?url=<?php echo $share; ?>" target="_blank" rel="noopener" class="btn btn-kt-social rounded-circle d-flex align-items-center justify-content-center" aria-label="Share on X" data-kt-page-share="x"><i class="bi bi-twitter-x"></i></a>
          <a href="https://wa.me/?text=<?php echo $share; ?>" target="_blank" rel="noopener" class="btn btn-kt-social rounded-circle d-flex align-items-center justify-content-center" aria-label="Share on WhatsApp" data-kt-page-share="whatsapp"><i class="bi bi-whatsapp"></i></a>
        </div>
      </div>
    </article>
    <aside class="col-12 col-lg-4">
      <div class="kt-nd-side bg-light border">
        <div class="kt-mini-title d-flex align-items-center gap-2 fw-bold text-primary">RECENT NEWS</div>
        <div class="d-flex flex-column">
<?php foreach ($recent as $row): ?>
        <a href="<?php echo html_escape($row['url']); ?>" class="kt-nd-recent d-flex align-items-center">
          <span class="kt-nd-thumb rounded-4 overflow-hidden bg-primary-subtle flex-shrink-0"><?php if ($row['thumb_url'] !== ''): ?><img loading="lazy" fetchpriority="low" decoding="async" src="<?php echo html_escape($row['thumb_url']); ?>" alt="" class="w-100 h-100 object-fit-cover"><?php endif; ?></span>
          <span class="d-flex flex-column"><span class="kt-nd-rdate text-body-secondary"><i class="bi bi-calendar3"></i> <?php echo date('d M Y', $row['time']); ?></span><span class="kt-nd-rtitle fw-bold text-dark"><?php echo html_escape($row['news_title']); ?></span></span>
        </a>
<?php endforeach; ?>
        </div>
        <a href="<?php echo html_escape($list_url); ?>" class="kt-news-more fw-bold d-inline-block">VIEW ALL NEWS →</a>
      </div>
    </aside>
  </div>
</section>
<?php echo view('bottom_application'); ?>
