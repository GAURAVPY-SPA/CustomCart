/* CustomCart sitepanel — CKEditor 4 for textareas with data-sp-editor (static page Description).
 * Set up like the reference project's "Full" editor (toolbar, 100% x 400px, uiColor #E2E2E2,
 * no image/link "Advanced" tabs), without its CKFinder file browser, Flash and form controls.
 * data-sp-upload-url = the image dialog's Upload tab posts there (the reference's CKFinder
 * QuickUpload: filebrowserImageUploadUrl), the section's <section>/upload_image route
 * (AdminController::upload_image).
 * The full editor keeps any markup (allowedContent: true — no CKEditor content filter), e.g.
 * <div class="col-sm-6"><figure class="blog-figure">: app/Libraries/HtmlSanitizer.php cleans the
 * HTML when it is saved (keeps classes and layout tags, removes scripts, event handlers and unsafe URLs).
 * data-sp-editor="basic" = a compact editor for short rich-text paragraphs (short texts):
 * paragraphs, bold/italic/underline, lists and links only, 180px high.
 */
(function () {
	'use strict';

	if (!window.CKEDITOR) { return; }

	var BASIC_ALLOWED = 'p br strong em u ul ol li; a[!href,target]';

	Array.prototype.forEach.call(document.querySelectorAll('textarea[data-sp-editor="basic"]'), function (textarea) {
		CKEDITOR.replace(textarea, {
			customConfig: '',
			language: 'en',
			versionCheck: false,
			width: '100%',
			height: '180px',
			uiColor: '#E2E2E2',
			removePlugins: 'scayt,wsc,exportpdf,uploadimage,uploadfile,easyimage,cloudservices,elementspath',
			removeDialogTabs: 'link:advanced;link:target',
			resize_dir: 'vertical',
			allowedContent: BASIC_ALLOWED,
			toolbar: [
				{ name: 'basicstyles', items: ['Bold', 'Italic', 'Underline', '-', 'RemoveFormat'] },
				{ name: 'paragraph', items: ['NumberedList', 'BulletedList'] },
				{ name: 'links', items: ['Link', 'Unlink'] },
				{ name: 'clipboard', items: ['PasteText', '-', 'Undo', 'Redo'] },
				{ name: 'tools', items: ['Maximize'] }
			]
		});
	});

	// CKEditor drops empty inline elements by default, which removed the design's decorations from
	// CMS content (<span class="cms-heading-accent"></span>, icons such as <i class="bi bi-flag"></i>).
	// The basic editor's allowedContent still leaves out span and i.
	CKEDITOR.dtd.$removeEmpty.span = 0;
	CKEDITOR.dtd.$removeEmpty.i = 0;

	Array.prototype.forEach.call(document.querySelectorAll('textarea[data-sp-editor]:not([data-sp-editor="basic"])'), function (textarea) {
		var config = {
			customConfig: '',
			language: 'en',
			versionCheck: false,
			width: '100%',
			height: '400px',
			uiColor: '#E2E2E2',
			extraPlugins: 'find,selectall,justify,font,colorbutton,showblocks,indentblock',
			removePlugins: 'scayt,wsc,exportpdf,uploadimage,uploadfile,easyimage,cloudservices',
			removeDialogTabs: 'image:advanced;link:advanced',
			format_tags: 'p;h1;h2;h3;h4;h5;h6;pre;address;div',
			allowedContent: true,
			toolbar: [
				{ name: 'document', items: ['Source'] },
				{ name: 'clipboard', items: ['Cut', 'Copy', 'Paste', 'PasteText', 'PasteFromWord', '-', 'Undo', 'Redo'] },
				{ name: 'editing', items: ['Find', 'Replace', '-', 'SelectAll'] },
				'/',
				{ name: 'basicstyles', items: ['Bold', 'Italic', 'Underline', 'Strike', 'Subscript', 'Superscript', '-', 'RemoveFormat'] },
				{ name: 'paragraph', items: ['NumberedList', 'BulletedList', '-', 'Outdent', 'Indent', '-', 'Blockquote', '-', 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock'] },
				{ name: 'links', items: ['Link', 'Unlink', 'Anchor'] },
				{ name: 'insert', items: ['Image', 'Table', 'HorizontalRule', 'SpecialChar'] },
				'/',
				{ name: 'styles', items: ['Format', 'Font', 'FontSize'] },
				{ name: 'colors', items: ['TextColor', 'BGColor'] },
				{ name: 'tools', items: ['Maximize', 'ShowBlocks'] }
			]
		};

		if (textarea.getAttribute('data-sp-upload-url')) {
			config.filebrowserImageUploadUrl = textarea.getAttribute('data-sp-upload-url');
		}

		CKEDITOR.replace(textarea, config);
	});
})();
