<?php
/*
| /news-and-events (News::index), built from html/news-and-events.htm: inner
| banner, the active items as news cards (three a row) and the design's
| pagination. $news = News::present() rows, $unavailable, $pager [page, pages, url].
*/
echo view('top_application');
echo view('inner_hero', array('hero' => array('title' => 'News & Events', 'banner' => 'News & Events', 'crumbs' => array('News & Events' => NULL))));
?>
<!-- NEWS & EVENTS -->
<section class="container-xxl kt-container kt-sec" data-reveal>
<?php if ( ! $news): ?>
  <p class="text-center text-body-secondary mb-0"><?php echo $unavailable ? 'News could not be loaded. Please try again in a few minutes.' : 'Please check back soon for news and events from CustomCart.'; ?></p>
<?php else: ?>
  <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 kt-g-news">
<?php foreach ($news as $item): ?>
    <div class="col">
      <article class="kt-news-card d-flex flex-column kt-gap-14">
        <div class="position-relative kt-mb-12">
          <a href="<?php echo html_escape($item['url']); ?>" class="kt-news-img d-block rounded-5 overflow-hidden bg-primary-subtle"><?php if ($item['image_url'] !== ''): ?><img loading="lazy" fetchpriority="low" decoding="async" src="<?php echo html_escape($item['image_url']); ?>" alt="<?php echo html_escape($item['news_title']); ?>" class="w-100 h-100 object-fit-cover"><?php endif; ?></a>
          <time datetime="<?php echo date('Y-m-d', $item['time']); ?>" class="kt-news-date position-absolute rounded-circle bg-white d-flex flex-column align-items-center justify-content-center lh-1"><span class="kt-news-d fw-bold text-dark"><?php echo date('d', $item['time']); ?></span><span class="kt-news-m fw-bold text-warning"><?php echo strtoupper(date('M', $item['time'])); ?></span></time>
        </div>
        <div class="kt-news-type fw-bold text-primary"><?php echo html_escape($item['type']); ?></div>
        <h3 class="kt-news-title mb-0 fw-bold"><a href="<?php echo html_escape($item['url']); ?>" class="text-dark"><?php echo html_escape($item['news_title']); ?></a></h3>
        <?php if ((string) $item['short_description'] !== ''): ?><p class="kt-news-text text-body-secondary mb-0"><?php echo html_escape(character_limiter((string) $item['short_description'], 160, '…')); ?></p><?php endif; ?>
        <a href="<?php echo html_escape($item['url']); ?>" class="kt-news-more fw-bold d-inline-flex align-items-center align-self-start">READ MORE <i class="bi bi-arrow-right"></i></a>
      </article>
    </div>
<?php endforeach; ?>
  </div>
<?php if ($pager['pages'] > 1): ?>
<?php $page_url = function ($n) use ($pager) { return $n > 1 ? $pager['url'].'?page='.$n : $pager['url']; }; ?>
  <nav class="kt-cat-pages d-flex justify-content-center" aria-label="News pages">
  <ul class="pagination kt-pagination mb-0">
  <li class="page-item<?php echo $pager['page'] <= 1 ? ' disabled' : ''; ?>"><a class="page-link rounded-circle" href="<?php echo html_escape($page_url(max(1, $pager['page'] - 1))); ?>" aria-label="Previous"><i class="bi bi-arrow-left"></i></a></li>
<?php for ($n = 1; $n <= $pager['pages']; $n++): ?>
  <li class="page-item<?php echo $n === $pager['page'] ? ' active' : ''; ?>"<?php echo $n === $pager['page'] ? ' aria-current="page"' : ''; ?>><a class="page-link rounded-circle" href="<?php echo html_escape($page_url($n)); ?>"><?php echo $n; ?></a></li>
<?php endfor; ?>
  <li class="page-item<?php echo $pager['page'] >= $pager['pages'] ? ' disabled' : ''; ?>"><a class="page-link rounded-circle" href="<?php echo html_escape($page_url(min($pager['pages'], $pager['page'] + 1))); ?>" aria-label="Next"><i class="bi bi-arrow-right"></i></a></li>
  </ul>
  </nav>
<?php endif; ?>
<?php endif; ?>
</section>
<?php echo view('bottom_application'); ?>
