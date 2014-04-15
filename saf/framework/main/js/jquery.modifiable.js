window.modifiable_confirm  = false;
window.modifiable_dblclick = false;
window.modifiable_waiting  = false;

(function($)
{

	$.fn.modifiable = function(options)
	{

		//------------------------------------------------------------------------------------ settings
		var settings = $.extend({
			aliases: {},
			done:    undefined,
			target:  undefined
		}, options);

		//------------------------------------------------------------------------------------- click()
		this.click(function(event)
		{
			if (!window.modifiable_confirm) {
				var clickable = this;
				event.preventDefault();
				event.stopImmediatePropagation();
				if (!window.modifiable_waiting) {
					window.modifiable_waiting = true;
					setTimeout(
						function()
						{
							if (window.modifiable_dblclick) {
								window.modifiable_dblclick = false;
							}
							else {
								window.modifiable_confirm = true;
								$(clickable).click();
								window.modifiable_confirm = false;
							}
							window.modifiable_waiting = false;
						},
						200
					);
				}
			}
		});

		//---------------------------------------------------------------------------------- dblclick()
		this.dblclick(function(event)
		{
			window.modifiable_dblclick = true;
			event.preventDefault();
			event.stopImmediatePropagation();
			var $this = $(this);
			var $input = $('<input>').val($this.html());
			$input.data('old', $input.val());
			var done = function() {
				var done = settings.done;
				if (typeof(done) == 'string') {
					for(var alias in settings.aliases) if (settings.aliases.hasOwnProperty(alias)) {
						var value = settings.aliases[alias];
						if (typeof(value) == 'function') {
							value = value($this);
						}
						done = done.replace('{' + alias + '}', encodeURI(value));
					}
					done = done.replace('{value}', encodeURI($input.val()));
					$.ajax({
						url: done,
						target: settings.target,
						success: function(data, status, xhr)
						{
							var destination = xhr.target;
							$(destination).html(data);
						}
					}).target = settings.target;
				}
				$input.parent().html($input.val());
			};
			$this.html($input);
			$input.autowidth();
			$input.keydown(function(event) {
				if (event.keyCode == 13) {
					done();
				}
				if (event.keyCode == 27) {
					var $this = $(this);
					$this.val($this.data('old'));
					done();
				}
			});
			$input.blur(function() { done(); });
			$input.focus();
		});

		return this;
	};

})( jQuery );