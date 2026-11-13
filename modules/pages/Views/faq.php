<?php
/*
| /faq — html/faq.htm: the FAQs shown on the FAQ page (Sitepanel > Manage
| FAQs, "Show on FAQ page"), numbered, the first one open.
| $faqs [faq_id, faq_question, faq_answer], $unavailable.
*/
echo view('top_application');
?>
<?php echo view('inner_hero', array('hero' => array('title' => 'FAQ', 'banner' => 'FAQ', 'crumbs' => array('FAQ' => NULL)))); ?>
<section class="container-xxl kt-container kt-sec" data-reveal>
  <div class="kt-fqp-wrap mx-auto">
      <div class="kt-eyebrow d-flex align-items-center justify-content-center fw-bold text-primary mb-3">FAQ</div>
      <h2 class="kt-h2 fw-bold text-dark text-center mb-4">Frequently Asked Questions</h2>
<?php if ( ! $faqs): ?>
      <p class="kt-text-2 text-center mb-0"><?php echo $unavailable ? 'The FAQs are not available right now. Please try again in a few minutes.' : 'Questions and answers will be added here soon.'; ?></p>
<?php else: ?>
      <div class="accordion kt-fqp d-flex flex-column" id="ktFaq">
<?php foreach ($faqs as $i => $faq): $id = 'ktFaq'.(int) $faq['faq_id']; $open = ($i === 0); ?>
        <div class="accordion-item">
          <h3 class="accordion-header"><button type="button" class="accordion-button<?php echo $open ? '' : ' collapsed'; ?> fw-bold" data-bs-toggle="collapse" data-bs-target="#<?php echo $id; ?>" aria-expanded="<?php echo $open ? 'true' : 'false'; ?>" aria-controls="<?php echo $id; ?>"><span class="kt-fqp-num flex-shrink-0"><?php echo sprintf('%02d', $i + 1); ?></span><span class="flex-grow-1"><?php echo html_escape($faq['faq_question']); ?></span><span class="kt-fqp-ico rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"><i class="bi bi-plus-lg"></i></span></button></h3>
          <div id="<?php echo $id; ?>" class="accordion-collapse collapse<?php echo $open ? ' show' : ''; ?>" data-bs-parent="#ktFaq"><div class="accordion-body"><?php echo nl2br(html_escape((string) $faq['faq_answer'])); ?></div></div>
        </div>
<?php endforeach; ?>
      </div>
<?php endif; ?>
  </div>
</section>
<?php echo view('bottom_application'); ?>
