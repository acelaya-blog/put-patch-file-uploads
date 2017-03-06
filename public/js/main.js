
$(document).ready(function () {
    $('.upload-form').submit(function (e) {
        e.preventDefault();

        var verb = $(this).find('[name=verb]:checked').val(),
            data = new FormData($(this)[0]);

        $.ajax({
            url : $(this).attr('action'),
            data : data,
            type : verb,
            cache : false,
            contentType : false,
            processData : false
        }).always(function (response) {
            $('.resp').html(response);
        })
    });
});
