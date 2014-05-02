$('document').ready(function()
{

	$('body').build(function()
	{
		// sort and decoration
		this.in('ul.property_tree').sortcontent();
		this.in('.property_select').prepend($('<span>').addClass('joint'));

		// search
		this.in('.property_select>input[name=search]').each(function()
		{
			console.log('set last_search = -');
			var last_search = '';
			var search_step = 0;
			$(this).keyup(function()
			{
				var $this = $(this);
				var new_search = $this.val();
				if ((last_search != new_search) && !search_step) {
					console.log('- changed from ' + last_search + ' to ' + new_search);
					search_step = 1;
					last_search = new_search;
					console.log('- SEARCH ' + new_search);
					$.ajax(
						window.app.uri_base + '/SAF/Framework/Property/search'
							+ '/' + $this.closest('[data-class]').data('class').replace('/', '\\')
							+ '?search=' + encodeURI(new_search)
							+ '&as_widget' + window.app.andSID(),
						{
							success: function(data) {
								search_step = 2;
								console.log('- find ' + new_search);
								$this.parent().children('.property_tree').html(data);
							}
						}
					);
					var retry = function() {
						if (search_step == 1) {
							console.log('. will retry');
							setTimeout(retry, 200);
						}
						else {
							console.log('- done');
							search_step = 0;
							if ($this.val() != last_search) {
								console.log('- search changed from ' + last_search + ' to ' + $this.val());
								$this.keyup();
							}
						}
					};
					setTimeout(retry, 500);
				}
			});
		});

		// create tree
		this.in('ul.property_tree>li a').click(function(event)
		{
			var $this = $(this);
			var $li = $(this).closest('li');
			if ($li.children('section').length) {
				if ($li.children('section:visible').length) {
					$this.removeClass('expanded');
					$li.children('section:visible').hide();
				}
				else {
					$this.addClass('expanded');
					$li.children('section:not(:visible)').show();
				}
				event.stopImmediatePropagation();
				event.preventDefault();
			}
			else {
				$this.addClass('expanded');
			}
		});

		// draggable items
		this.in('.property, fieldset>div[id]>label').draggable({
			appendTo:    'body',
			containment: 'body',
			cursorAt:    { left: 10, top: 10 },
			delay:       500,
			scroll:      false,

			helper: function()
			{
				var $this = $(this);
				return $('<div>')
					.addClass('property')
					.attr('data-class',    $this.closest('.window').data('class'))
					.attr('data-feature',  $this.closest('.window').data('feature'))
					.attr('data-property', $this.data('property'))
					.css('z-index', ++zindex_counter)
					.html($this.text());
			},

			drag: function(event, ui)
			{
				var $droppable = $(this).data('over-droppable');
				if ($droppable != undefined) {
					var draggable_left = ui.offset.left;
					var count = 0;
					var found = 0;
					$droppable.find('thead>tr:first>th:not(:first)').each(function() {
						count ++;
						var $this = $(this);
						var $prev = $this.prev('th');
						var left = $prev.offset().left + $prev.width();
						var right = $this.offset().left + $this.width();
						if ((draggable_left > left) && (draggable_left <= right)) {
							found = (draggable_left <= ((left + right) / 2)) ? count : (count + 1);
							var old = $droppable.data('insert-after');
							if (found != old) {
								if (old != undefined) {
									$droppable.find('colgroup>col:nth-child(' + old + ')').removeClass('insert_after');
								}
								if (found > 1) {
									$droppable.find('colgroup>col:nth-child(' + found + ')').addClass('insert_after');
									$droppable.data('insert-after', found);
								}
							}
						}
					});
				}
			}

		});

	});

});
