<?php

namespace App\Libraries;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;

/**
 * Allow-list HTML cleaner for the Sitepanel's CKEditor content (static pages,
 * blogs, products, emails).
 *
 * The editor keeps any markup (assets/sitepanel/js/editor.js: no content
 * filter), so this is the safety layer: it keeps the tags in ALLOWED with the
 * attributes listed for each tag plus GLOBAL_ATTRIBUTES (class, id, title,
 * role, lang, dir, aria-*, data-*), e.g. <div class="col-sm-6"><figure
 * class="blog-figure">. Inline styles are reduced to the CSS properties in
 * STYLES with plain values (no url(), expression(), comments or escapes).
 * Links may use http(s), mailto, tel, a site-relative path or a #fragment;
 * images and media only http(s) or a site-relative path; an <iframe> only an
 * https address of IFRAME_HOSTS (video / map embeds). Event handlers (on...)
 * and every other attribute are removed. Disallowed tags are unwrapped
 * (their text kept); script-like elements (DROPPED_TAGS) are removed with
 * their content; comments are removed. Text without any tags is turned into
 * paragraphs.
 */
class HtmlSanitizer
{
	/** tag => allowed attributes besides GLOBAL_ATTRIBUTES ('style' = filtered inline style) */
	const ALLOWED = array(
		'p' => array('style'), 'div' => array('style'), 'pre' => array('style'), 'address' => array('style'),
		'h1' => array('style'), 'h2' => array('style'), 'h3' => array('style'),
		'h4' => array('style'), 'h5' => array('style'), 'h6' => array('style'),
		'blockquote' => array('style', 'cite'), 'span' => array('style'),
		'ul' => array('style'), 'ol' => array('style', 'start', 'reversed', 'type'), 'li' => array('style', 'value'),
		'dl' => array('style'), 'dt' => array('style'), 'dd' => array('style'),
		'strong' => array(), 'b' => array(), 'em' => array(), 'i' => array(), 'u' => array(),
		's' => array(), 'strike' => array(), 'sub' => array(), 'sup' => array(), 'br' => array(), 'hr' => array('style'),
		'small' => array(), 'mark' => array(), 'abbr' => array(), 'cite' => array(), 'code' => array(), 'kbd' => array(),
		'samp' => array(), 'var' => array(), 'q' => array('cite'), 'del' => array('datetime'), 'ins' => array('datetime'),
		'time' => array('datetime'), 'bdi' => array(), 'bdo' => array(), 'wbr' => array(),
		// Layout / HTML5 sections (e.g. Bootstrap rows and columns of the design)
		'section' => array('style'), 'article' => array('style'), 'header' => array('style'), 'footer' => array('style'),
		'aside' => array('style'), 'main' => array('style'), 'nav' => array('style'),
		'figure' => array('style'), 'figcaption' => array('style'),
		'details' => array('style', 'open'), 'summary' => array('style'),
		'a' => array('href', 'target', 'name', 'rel', 'style'),
		'img' => array('src', 'alt', 'width', 'height', 'style', 'loading', 'decoding'),
		'picture' => array(),
		'video' => array('src', 'poster', 'width', 'height', 'style', 'controls', 'autoplay', 'muted', 'loop', 'playsinline', 'preload'),
		'audio' => array('src', 'controls', 'autoplay', 'muted', 'loop', 'preload'),
		'source' => array('src', 'type', 'media'),
		'iframe' => array('src', 'width', 'height', 'style', 'allow', 'allowfullscreen', 'loading', 'referrerpolicy'),
		'table' => array('border', 'cellpadding', 'cellspacing', 'summary', 'style'),
		'caption' => array('style'), 'thead' => array(), 'tbody' => array(), 'tfoot' => array(), 'tr' => array('style'),
		'colgroup' => array('span'), 'col' => array('span', 'style'),
		'th' => array('colspan', 'rowspan', 'scope', 'style'),
		'td' => array('colspan', 'rowspan', 'scope', 'style'),
	);

	/** Attributes kept on every allowed tag (aria-* and data-* too, see clean_attributes()) */
	const GLOBAL_ATTRIBUTES = array('class', 'id', 'title', 'role', 'lang', 'dir');

	/** CSS properties kept in style attributes */
	const STYLES = array('color', 'background-color', 'font-family', 'font-size', 'font-weight', 'font-style',
		'line-height', 'letter-spacing', 'text-align', 'text-decoration', 'text-transform', 'text-indent', 'white-space',
		'margin', 'margin-left', 'margin-right', 'margin-top', 'margin-bottom',
		'padding', 'padding-left', 'padding-right', 'padding-top', 'padding-bottom', 'float', 'clear', 'display',
		'width', 'height', 'max-width', 'min-width', 'max-height', 'min-height', 'vertical-align',
		'border', 'border-width', 'border-style', 'border-color', 'border-collapse', 'border-radius',
		'border-top', 'border-right', 'border-bottom', 'border-left', 'list-style-type', 'object-fit', 'gap');

	const DROPPED_TAGS = array('script', 'style', 'frame', 'frameset', 'object', 'embed',
		'applet', 'noscript', 'template', 'svg', 'math', 'form', 'input', 'button', 'textarea',
		'select', 'option', 'title', 'head', 'meta', 'link', 'base');

	/** Hosts an <iframe> may load (https only): video and map embeds */
	const IFRAME_HOSTS = array('www.youtube.com', 'youtube.com', 'www.youtube-nocookie.com', 'player.vimeo.com',
		'www.google.com', 'maps.google.com');

	/** Attributes without a value (kept as name="name") */
	const BOOLEAN_ATTRIBUTES = array('controls', 'autoplay', 'muted', 'loop', 'playsinline', 'reversed', 'open', 'allowfullscreen');

	/**
	 * @param  string $html
	 * @return string
	 */
	public function clean($html)
	{
		$html = str_replace("\0", '', (string) $html);

		if (trim($html) === '')
		{
			return '';
		}

		if (strpos($html, '<') === FALSE)
		{
			return $this->paragraphs($html);
		}

		$doc = new DOMDocument('1.0', 'UTF-8');
		$previous = libxml_use_internal_errors(TRUE);
		$doc->loadHTML('<?xml encoding="UTF-8"?><div>'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET);
		libxml_clear_errors();
		libxml_use_internal_errors($previous);

		$root = $doc->getElementsByTagName('div')->item(0);
		if ( ! $root)
		{
			return '';
		}

		$this->clean_children($root);

		$out = '';
		foreach ($root->childNodes as $child)
		{
			$out .= $doc->saveHTML($child);
		}

		return trim($out);
	}

	/**
	 * @param DOMNode $node
	 */
	protected function clean_children(DOMNode $node)
	{
		foreach (iterator_to_array($node->childNodes) as $child)
		{
			if ($child instanceof DOMText)
			{
				continue; // text (and CDATA, a DOMText subclass) is escaped on output
			}

			if ( ! $child instanceof DOMElement)
			{
				$node->removeChild($child); // comments, processing instructions...
				continue;
			}

			$tag = strtolower($child->nodeName);

			if (in_array($tag, self::DROPPED_TAGS, TRUE))
			{
				$node->removeChild($child);
				continue;
			}

			$this->clean_children($child);

			if ( ! isset(self::ALLOWED[$tag]))
			{
				// Unwrap: keep the (already cleaned) content, drop the tag.
				while ($child->firstChild)
				{
					$node->insertBefore($child->firstChild, $child);
				}
				$node->removeChild($child);
				continue;
			}

			$this->clean_attributes($child, $tag);

			if (($tag === 'img' || $tag === 'iframe') && ! $child->hasAttribute('src'))
			{
				$node->removeChild($child); // nothing left to show (or an iframe of another site)
			}
		}
	}

	/**
	 * Keep only the allowed attributes of $tag, with safe values.
	 *
	 * @param DOMElement $el
	 * @param string     $tag
	 */
	protected function clean_attributes(DOMElement $el, $tag)
	{
		$keep = array();
		foreach (iterator_to_array($el->attributes) as $attribute)
		{
			$name    = strtolower($attribute->nodeName);
			$allowed = in_array($name, self::ALLOWED[$tag], TRUE) || in_array($name, self::GLOBAL_ATTRIBUTES, TRUE)
				|| preg_match('/^(aria|data)-[a-z0-9][a-z0-9-]{0,40}$/', $name);
			$value   = $allowed ? $this->clean_attribute($tag, $name, (string) $attribute->nodeValue) : NULL;
			if ($value !== NULL)
			{
				$keep[$name] = $value;
			}
			$el->removeAttribute($attribute->nodeName);
		}

		foreach ($keep as $name => $value)
		{
			$el->setAttribute($name, $value);
		}

		if ($tag === 'a' && isset($keep['href']) && (isset($keep['target']) || preg_match('#^https?://#i', $keep['href'])))
		{
			$el->setAttribute('rel', 'noopener nofollow');
		}
	}

	/**
	 * @param  string $tag
	 * @param  string $name
	 * @param  string $value
	 * @return string|null Safe value, NULL = drop the attribute
	 */
	protected function clean_attribute($tag, $name, $value)
	{
		$value = trim($value);

		if (in_array($name, self::BOOLEAN_ATTRIBUTES, TRUE))
		{
			return $name;
		}
		if (strpos($name, 'aria-') === 0 || strpos($name, 'data-') === 0)
		{
			return mb_substr($value, 0, 300); // text: escaped on output; never a URL the browser follows
		}

		switch ($name)
		{
			case 'href':
				return $this->safe_url($value, TRUE) ? $value : NULL;
			case 'src':
				if ($tag === 'iframe')
				{
					$host = strtolower((string) parse_url($value, PHP_URL_HOST));
					return (preg_match('#^https://#i', $value) && in_array($host, self::IFRAME_HOSTS, TRUE)) ? $value : NULL;
				}
				return $this->safe_url($value, FALSE) ? $value : NULL;
			case 'poster':
			case 'cite':
				return $this->safe_url($value, FALSE) ? $value : NULL;
			case 'target':
				return ($value === '_blank') ? $value : NULL;
			case 'name':
			case 'id':
				return preg_match('/^[A-Za-z][\w\-:.]{0,63}$/', $value) ? $value : NULL;
			case 'class':
				// Class names only (letters, digits, - _ : /), e.g. "col-sm-6 blog-figure".
				$value = trim(preg_replace('/\s+/', ' ', $value));
				return ($value !== '' && strlen($value) <= 500 && preg_match('#^[A-Za-z0-9_\-:/ ]+$#', $value)) ? $value : NULL;
			case 'title':
			case 'lang':
			case 'datetime':
			case 'type':
			case 'media':
				return mb_substr($value, 0, 300); // text: escaped on output
			case 'role':
				return preg_match('/^[a-z]{2,30}$/', $value) ? $value : NULL;
			case 'dir':
				return in_array($value, array('ltr', 'rtl', 'auto'), TRUE) ? $value : NULL;
			case 'rel':
				return preg_match('/^[a-z ]{1,60}$/', $value) ? $value : NULL;
			case 'loading':
				return in_array($value, array('lazy', 'eager'), TRUE) ? $value : NULL;
			case 'decoding':
				return in_array($value, array('async', 'sync', 'auto'), TRUE) ? $value : NULL;
			case 'preload':
				return in_array($value, array('none', 'metadata', 'auto'), TRUE) ? $value : NULL;
			case 'referrerpolicy':
				return preg_match('/^[a-z\-]{1,40}$/', $value) ? $value : NULL;
			case 'allow':
				return preg_match('/^[a-z\-; ]{1,200}$/', $value) ? $value : NULL;
			case 'start':
			case 'value':
			case 'span':
				return preg_match('/^\d{1,4}$/', $value) ? $value : NULL;
			case 'width':
			case 'height':
			case 'border':
			case 'cellpadding':
			case 'cellspacing':
			case 'colspan':
			case 'rowspan':
				return preg_match('/^\d{1,4}%?$/', $value) ? $value : NULL;
			case 'scope':
				return in_array($value, array('row', 'col', 'rowgroup', 'colgroup'), TRUE) ? $value : NULL;
			case 'alt':
			case 'summary':
				return mb_substr($value, 0, 300); // text: escaped on output
			case 'style':
				$style = $this->clean_style($value);
				return ($style === '') ? NULL : $style;
		}

		return NULL;
	}

	/**
	 * Keep allowed CSS properties whose value is plain (words, numbers,
	 * units, #colours, rgb()/rgba(), quoted font names).
	 *
	 * @param  string $style
	 * @return string
	 */
	protected function clean_style($style)
	{
		$out = array();

		foreach (explode(';', $style) as $declaration)
		{
			if (strpos($declaration, ':') === FALSE)
			{
				continue;
			}

			list($property, $value) = array_map('trim', explode(':', $declaration, 2));
			$property = strtolower($property);
			$value    = preg_replace('/\s*!important$/i', '', $value);

			if ( ! in_array($property, self::STYLES, TRUE) || $value === '' || strlen($value) > 200)
			{
				continue;
			}

			// No url(), expression(), escapes, comments or markup: only these characters,
			// and parentheses only as rgb()/rgba().
			$without_rgb = preg_replace('/rgba?\(\s*[\d.\s,%]+\)/i', '', $value);
			if (preg_match('/^[#\w\s,.%\-\'"]*$/', $without_rgb) && stripos($value, 'expression') === FALSE)
			{
				$out[] = $property.': '.$value;
			}
		}

		return implode('; ', $out);
	}

	/**
	 * @param  string $url
	 * @param  bool   $link TRUE for <a href> (also mailto:, tel:, #fragment)
	 * @return bool
	 */
	protected function safe_url($url, $link)
	{
		// Browsers ignore control characters and whitespace inside a scheme ("java\tscript:").
		$compact = preg_replace('/[\x00-\x20\x7F]+/', '', (string) $url);

		if ($compact === '')
		{
			return FALSE;
		}

		if (preg_match('#^https?://#i', $compact) || ($link && preg_match('#^(mailto:|tel:)#i', $compact)))
		{
			return TRUE;
		}

		if ( ! $link && $compact[0] === '#')
		{
			return FALSE;
		}

		// Relative: "/path", "#id", "page" — anything without a scheme. "//host" is protocol-relative (off-site): refused.
		return strpos($compact, '//') !== 0 && ! preg_match('#^[a-z][a-z0-9+.\-]*:#i', $compact);
	}

	/**
	 * Plain text -> <p> paragraphs (blank-line separated) with <br> line breaks.
	 *
	 * @param  string $text
	 * @return string
	 */
	protected function paragraphs($text)
	{
		$text = str_replace(array("\r\n", "\r"), "\n", trim($text));
		$out = array();

		foreach (preg_split('/\n\s*\n/', $text) as $block)
		{
			$out[] = '<p>'.nl2br(htmlspecialchars(trim($block), ENT_QUOTES, 'UTF-8'), FALSE).'</p>';
		}

		return implode("\n", $out);
	}
}
