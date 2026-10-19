let typeSelect;
let target;

$(function () {
	typeSelect = $('#slideType');
	target = $('#form-fields');

	typeSelect.on('change', function (e) {
		const $this = $(e.currentTarget);
		const value = $this.val();
		loadControls(value);
	});

	if (typeSelect.val()) {
		loadControls(typeSelect.val());
	}
});

function loadControls(value) {
	$.ajax({
		url: CCM_DISPATCHER_FILENAME + '/dashboard/banner_manager/slides/get_type_controls',
		dataType: 'json',
		data: {
			slideTypeId: value,
			expressEntry: target.data('values')
		},
		success: function (response) {
			if (response.html.length) {
				target.html(response.html);
			}
		},
		error: function (response) {
			const error = response.responseJSON ? response.responseJSON.error : false;
			if (error) {
				target
					.empty()
					.append($('<div class="alert alert-danger"></div>').text(error.message));
			}
		},
		complete: function () {
			const richText = target.find('.dynamic-richtext');
			if (richText.length) {
				launchEditor(richText);
			}
			toggleButtonLinkFields();
		}
	});
}

// Show/hide the "Button Page", "Button URL" and "Open Link in New Tab" fields
// on the Standard slide type's form based on the "Button Link Type" select
// value. Fields are wrapped in a ".mb-3" div with a "<label>" matching the
// attribute's name (see concrete/elements/form/bootstrap5.php) - there's no
// attribute-handle hook to select on, so fields are located by their label
// text instead. The select's rendered option values are numeric option IDs,
// not the option text, so the selected option's text is what's compared.
function toggleButtonLinkFields() {
	const findFieldByLabel = function (text) {
		return target
			.find('label')
			.filter(function () {
				return $(this).text().trim() === text;
			})
			.first()
			.closest('.mb-3');
	};

	const $linkTypeField = findFieldByLabel('Button Link Type');
	const $pageField = findFieldByLabel('Button Page');
	const $urlField = findFieldByLabel('Button URL');
	const $newTabField = findFieldByLabel('Open Link in New Tab');

	if (!$linkTypeField.length || !$pageField.length || !$urlField.length) {
		return;
	}

	const $select = $linkTypeField.find('select');

	function update() {
		const selectedText = $select.find('option:selected').text().trim();
		$pageField.toggle(selectedText === 'Internal Page');
		$urlField.toggle(selectedText === 'External URL');
		// External URLs always open in a new tab already - the checkbox only
		// has an effect for Internal Page links.
		$newTabField.toggle(selectedText === 'Internal Page');
	}

	$select.off('change.buttonLinkType').on('change.buttonLinkType', update);
	update();
}
