<?php
/**
 * Summary card. Expects $card = [icon, label, value, meta, tone, url (optional), key (optional)]
 * tone: neutral | ok | warn | error. With a url the whole card is a link.
 * A neutral card with a key gets that key's gradient from Theme Management
 * (rule .sp-stat--g-<key>, printed by SitepanelTheme::css()).
 */
$tag  = empty($card['url']) ? 'article' : 'a';
$grad = $card['tone'] === 'neutral' && ! empty($card['key']) && preg_match('/^[a-z0-9_-]+$/', $card['key'])
	? ' sp-stat--grad sp-stat--g-'.$card['key']
	: '';
?>
<<?php echo $tag; ?> class="sp-stat sp-stat--<?php echo html_escape($card['tone']).$grad; ?>"<?php echo $tag === 'a' ? ' href="'.html_escape($card['url']).'"' : ''; ?>>
	<span class="sp-stat-icon"><?php echo sp_icon($card['icon']); ?></span>
	<div class="sp-stat-body">
		<p class="sp-stat-value">
			<?php if ($card['tone'] !== 'neutral'): ?><span class="sp-dot" aria-hidden="true"></span><?php endif; ?>
			<?php echo html_escape($card['value']); ?>
		</p>
		<h2 class="sp-stat-label"><?php echo html_escape($card['label']); ?></h2>
		<p class="sp-stat-meta"><?php echo html_escape($card['meta']); ?></p>
	</div>
</<?php echo $tag; ?>>
