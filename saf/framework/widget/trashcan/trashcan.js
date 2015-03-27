$('document').ready(function()
{

	$('body').build(function()
	{

		this.inside('.object').draggable({

			appendTo: 'body',
			containment: 'body',
			cursorAt: { left: 10, top: 10 },
			delay: 500,
			scroll: false,

			helper: function()
			{
				var $this = $(this);
				var class_name = $this.data('class');
				var id = $this.data('id');
				var data_throw = $this.data('throw');
				if (class_name == undefined) {
					class_name = $this.closest('[data-class]').data('class');
				}
				if (id == undefined) {
					id = $this.closest('[data-id]').data('id');
				}
				if (data_throw == undefined) {
					data_throw = $this.closest('[data-throw]').data('throw');
				}
				var text = $this.find('h2').text();
				if (!text.length) {
					text = $this.text();
				}
				return $('<div>')
					.addClass('object')
					.attr('data-class', class_name)
					.attr('data-id', id)
					.attr('data-throw', data_throw)
					.html(text)
					.css('z-index', ++zindex_counter);
			}

		});

		// trash is droppable
		this.inside('#trashcan a').droppable({
			accept:     '.column label, .object, .objects, .property, .throwable',
			hoverClass: 'candrop',
			tolerance:  'touch',

			drop: function(event, ui)
			{
				var app = window.app;
				var href = event.target.href;
				// calculate destination href
				if (ui.helper.data('throw')) {
					event.target.href = app.uri_base + ui.helper.data('throw')
				}
				else {
					event.target.href = event.target.pathname + '/drop';
				}
				// after trash call is complete, the source window is reloaded to update displayed content
				var $window = ui.draggable.closest('.window');
				if ($window.length) {
					var data_class = $window.data('class');
					if (data_class != undefined) {
						$(event.target).data(
							'on-success', function () {
								var uri = '/' + data_class.replace('\\', '/') + '/' + $window.data('feature');
								$.ajax(
									{
										url:     app.uri_base + uri + '?as_widget' + app.andSID(),
										success: function (data) {
											var $parent = $window.parent();
											$parent.html(data);
											$parent.children().build();
										}
									}
								);
							}
						);
					}
				}
				event.target.href += '/' + ui.helper.data('class').replace('\\', '/');
				if (ui.helper.data('id')) {
					event.target.href += '/' + ui.helper.data('id');
				}
				if (ui.helper.data('feature')) {
					event.target.href += '/' + ui.helper.data('feature');
				}
				if (ui.helper.data('property')) {
					event.target.href += '/SAF/Framework/Property/' + ui.helper.data('property');
				}
				event.target.href += event.target.search + event.target.hash;
				event.target.click();
				// end
				event.target.href = href;
			}
		});

		// trash message can be hidden
		this.inside('#trashcan .delete.message').click(function() { $(this).remove(); });

	});

});
