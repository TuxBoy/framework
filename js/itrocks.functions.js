
//------------------------------------------------------------------------------- copyCssPropertyTo
copyCssPropertyTo = function(context, element)
{
	var tab = [
		'font-size',
		'font',
		'font-family',
		'font-size',
		'font-weight',
		'letter-spacing',
		'line-height',
		'border',
		'border-bottom-width',
		'border-left-width',
		'border-top-width',
		'border-right-width',
		'border-color',
		'margin',
		'margin-bottom',
		'margin-left',
		'margin-right',
		'margin-top',
		'text-rendering',
		'word-spacing',
		'word-wrap'
	];
	for (var i = 0; i < tab.length; i++) {
		element.css(tab[i], context.css(tab[i]));
	}
	element.css('width', context.css('width') ? context.css('width') : context.width() + 'px');
};

//-------------------------------------------------------------------------- dateFormatToDatepicker
dateFormatToDatepicker = function(text)
{
	return text.replace('d', 'dd').replace('m', 'mm').replace('Y', 'yy');
};

//------------------------------------------------------------------------------ getInputTextHeight
getInputTextHeight = function(context)
{
	return Math.max(20, getTextHeight(context, 16));
};

//------------------------------------------------------------------------------- getInputTextWidth
// TODO limit cache size as it could grow too much !
getInputTextWidth = function(context)
{
	return Math.max(40, getTextWidth(context, 16));
};

//----------------------------------------------------------------------------------- getTextHeight
getTextHeight = function(context, extraHeight)
{
	var $content = context.val().split("\n");
	// If the last element is empty, need put a character to prevent the browser ignores the character
	var $last_index = $content.length -1;
	if (!$content[$last_index]) {
		$content[$last_index] = '_';
	}
	var $height = $('<div>');
	$height.append($content.join('<br>')).appendTo(context.parent());
	copyCssPropertyTo(context, $height);
	$height.css('position', 'absolute');
	var $width = getInputTextWidth(context);
	$height.width($width);
	var height = $height.height() + extraHeight;
	$height.remove();
	 return height;
};

//------------------------------------------------------------------------------------ getTextWidth
get_text_width_cache = [];
getTextWidth = function(context, extraWidth)
{
	var width = get_text_width_cache[context.val()];
	if (width != undefined) {
		return width;
	}
	else {
		var $content = context.val().replace(' ', '_').split("\n");
		var $width = $('<span>');
		$width.append($content.join('<br>')).appendTo('body');
		copyCssPropertyTo(context, $width);
		$width.css('position', 'absolute');
		var $pos = context.position();
		$width.css('top', $pos.top);
		$width.css('left', $pos.left);
		width = $width.width() + extraWidth;
		var $parent = context.parent();
		var $margins = parseInt($parent.css('margin-right'))
			+ parseInt($parent.css('padding-right'))
			+ parseInt(context.css('margin-right'));
		var ending_right_parent = ($(window).width() - ($parent.offset().left + $parent.outerWidth()));
		ending_right_parent += $margins;
		var ending_right = ($(window).width() - ($width.offset().left + $width.outerWidth()) - extraWidth);
		if (ending_right < ending_right_parent) {
			width = width - (ending_right_parent - ending_right);
		}
		$width.remove();
		get_text_width_cache[context.val()] = width;
		return width;
	}
};

//---------------------------------------------------------------------------------------- redirect
/**
 * Load an URI into target
 *
 * @param uri
 * @param target
 */
redirect = function(uri, target)
{
	//noinspection JSUnresolvedVariable
	var app = window.app;
	var more = ((target != undefined) && (target != '') && (target[0] == '#')) ? '?as_widget' : '';
	if (uri.substr(0, app.uri_base.length) != app.uri_base) {
		uri = app.uri_base + uri;
	}
	if (!more) {
		window.location = app.addSID(uri);
	}
	else {
		$.ajax({
			url:     app.addSID(uri + more),
			success: function(data) {
				$(target).html(data).build();
				var title = $(target).find('h2').first().text();
				if (!title.length) {
					title = uri;
				}
				document.title = title;
				window.history.pushState({ reload: true }, title, uri);
			}
		});
	}
};
