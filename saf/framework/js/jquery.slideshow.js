(function($)
{

	$.fn.slideShow = function(options)
	{
		if (this.length < 2) return this;

		//------------------------------------------------------------------------------------ settings
		var settings = $.extend({}, options);

		var elements = this;
		var position = 0;
		var hover = 0;

		// hide all elements
		for (var i = 1; i < elements.length; i++) {
			$(elements[i]).hide();
		}

		//------------------------------------------------------------ previous/next : string to jquery
		var parent;
		var previous;
		var next;
		var truc = 0;
		if (settings.previous != undefined) {
			if (typeof settings.previous == 'string') {
				parent = elements.parent();
				previous = parent.children(settings.previous);
				while (parent.length && !previous.length && truc++ < 100) {
					parent = parent.parent();
					previous = parent.children(settings.previous);
				}
				settings.previous = previous;
			}
		}
		if (settings.next != undefined) {
			if (typeof settings.next == 'string') {
				parent = elements.parent();
				next = parent.children(settings.next);
				while (parent.length && !next.length && truc++ < 100) {
					parent = parent.parent();
					next = parent.children(settings.next);
				}
				settings.next = next;
			}
		}

		//---------------------------------------------------------------------------- elements.hover()
		elements.hover(function() { hover++; }, function() { hover--; });
		if (settings.previous != undefined) {
			settings.previous.hover(function() { hover++; }, function() { hover--; });
		}
		if (settings.next != undefined) {
			settings.next.hover(function() { hover++; }, function() { hover--; });
		}

		//----------------------------------------------------------------------- previous/next.click()
		if (settings.previous != undefined) {
			settings.previous.click(function() {
				$(elements[position]).fadeOut(200);
				position --;
				if (position < 0) {
					position = elements.length - 1;
				}
				$(elements[position]).fadeIn(200);
			});
		}
		if (settings.next != undefined) {
			settings.next.click(function() {
				$(elements[position]).fadeOut(200);
				position ++;
				if (position >= elements.length) {
					position = 0;
				}
				$(elements[position]).fadeIn(200);
			});
		}

		//----------------------------------------------------------------------------------- slideShow
		setInterval(function()
		{
			if (!hover) {
				$(elements[position]).fadeOut(1000);
				position ++;
				if (position >= elements.length) {
					position = 0;
				}
				$(elements[position]).fadeIn(1000);
			}
		}, 5000);

		return this;
	};

})( jQuery );
