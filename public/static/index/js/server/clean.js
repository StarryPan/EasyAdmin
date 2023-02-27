define(["jquery", "easy-admin"], function ($, ea) {

    var Controller = {

        index: function () {

            ea.listen(function (data) {
                data['is_captcha'] = true;
                return data;
            }, function (res) {
                ea.msg.success(res.msg);
            }, function (err) {
                ea.msg.alert(err.msg, function () {
                    $('#clean_captcha').val('');
                    $('#refreshCaptcha').trigger("click");
                });
            });
        },

    };

    return Controller;
});