/* Shoutbox Reports and moderation system by Arne Van Daele - http://arnevandaele.com - https://blazor.nl */

$(document).ready(function () {
	dvz_reports.showReasonInput();
});

var dvz_reports = {
	showReasonInput: function () {
		$('#ban_reason').change(function () {
			var reason = $(this).val();
			if(reason === 'different') {
				$('#input_reason').show();
			} else {
				$('#input_reason').hide();
			}
		});
	}
}