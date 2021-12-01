/*global jQuery, ajaxurl, bfox_blog_iframe_select_change */
'use strict';

function resizeTooltip(){
	let current_size = window.innerWidth;
	if(current_size >=1024){
		return current_size * 0.5;
	}
	if(current_size >=650){
		return current_size * 0.8;
	}
	return window.innerWidth - 10;
}

jQuery(document).ready(function () {
	var refClassPrefix = 'bible-tip-';
	// Add tooltips to bible ref links
	// NOTE: For live() qTip config see: http://stackoverflow.com/questions/2005521/problem-with-qtip-tips-not-showing-because-elements-load-after-the-script#answer-2485862
	jQuery('body').on('click', 'a.bible-tip',function () {
		var refStr, index, url, classes, lang = 'en', load_str = 'Loading...', close_str = 'Close';
		
		classes = jQuery(this).attr('class').split(' ');
		for (index = 0; index < classes.length; index = index + 1) {
			if (classes[index].indexOf(refClassPrefix) === 0) {
				refStr = classes[index].replace(refClassPrefix, '');
				break;
			}
		}
		if(classes[3].includes('bible_link_lang')){
			lang = classes[3].split('-');
			lang = lang[1];
			load_str = lang === 'es' ? 'Cargando ...': load_str;
			close_str = lang === 'es' ? 'Cerrar' : close_str;
		}
		
		if (undefined === refStr) {
			return true;
		}
		
		url = ajaxurl + ((ajaxurl.indexOf('?') === -1) ? '?' : '&') + 'bfox-tooltip-ref=' + refStr+'&lang=' + lang;

		jQuery(this).qtip({
			content: {
				text: load_str,
				url: url,
				title: {
					text: '<a href="' + jQuery(this).attr('href') + '">' + jQuery(this).text() + '</a>', // Give the tooltip a title using each elements text
					button: close_str // Show a close link in the title
				}
			},
			position: {
				corner: {
					target: 'bottomMiddle', // Position the tooltip above the link
					tooltip: 'topMiddle'
				},
				adjust: {
					screen: true // Keep the tooltip on-screen at all times
				}
			},
			overwrite: false,
			show: {
				ready: true,
				when: 'click',
				solo: true
			},
			hide: 'unfocus',
			style: {
				tip: true, // Apply a speech bubble tip to the tooltip at the designated tooltip corner
				border: {
					width: 0,
					radius: 4
				},
				name: 'light', // Use the default light style
				width: resizeTooltip()  // Set the tooltip width
			},
			api: {
				onContentUpdate: function () {
					// When the content is updated, make sure that any iframe selects can update properly
					this.elements.content.find('select.bfox-iframe-select').change(bfox_blog_iframe_select_change);
				},
				onHide: function () {
					// HACK: Firefox has a bug that causes flickering when the iframe scroll position is not 0
					// See: http://craigsworks.com/projects/qtip/forum/topic/314/qtip-flicker-in-firefox/
					// Fix it by disabling scrolling on the iframes when we hide them
					this.elements.content.find('iframe').attr('scrolling', 'no');
				},
				onShow: function () {
					// Re-enable scrolling on the iframes
					this.elements.content.find('iframe').attr('scrolling', 'yes');
				}
			}
		});
		
		return false;
	});
});
