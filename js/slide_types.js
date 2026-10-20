const fieldSelect = $('#fieldSelect');

$(function () {
	if (fieldSelect.length) {
		fieldSelect.on('change', function (e) {
			e.preventDefault();
			const $this = $(e.currentTarget);
			const value = $this.val();

			if (value == '') return;

			var $element = $('#ccm-dialog-' + $this.attr('data-dialog'));
			$element.find('#attributeTypeId').val(value);
			$this.prop('selectedIndex', 0);
			if ($(this).attr('data-dialog-title')) {
				var title = $this.attr('data-dialog-title');
			} else {
				var title = $this.text();
			}
			jQuery.fn.dialog.open({
				element: $element,
				modal: true,
				width: 320,
				title: title,
				height: 'auto'
			});
		});
	}

	$('.field-dialogs').on('click', function (e) {
		e.preventDefault();
		const $this = $(e.currentTarget);

		var $element = $(
			'#ccm-dialog-' + $this.attr('data-dialog') + '-' + $this.attr('data-dialog-id')
		);
		if ($(this).attr('data-dialog-title')) {
			var title = $this.attr('data-dialog-title');
		} else {
			var title = $this.text();
		}
		jQuery.fn.dialog.open({
			element: $element,
			modal: true,
			width: 320,
			title: title,
			height: 'auto'
		});
	});
});
