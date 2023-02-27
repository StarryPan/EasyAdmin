define(["jquery", "easy-admin", "vue"], function ($, ea, Vue) {

    var form = layui.form;

    var Controller = {
        index: function () {

            var app = new Vue({
                el: '#app',
                data: {
                    upload_type: 0
                }
            });

            form.on("radio(upload_type)", function (data) {
                console.log('value：', this.value);
                app.upload_type = this.value;
            });

            ea.listen();
        }
    };
    return Controller;
});