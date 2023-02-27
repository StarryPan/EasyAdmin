define(["jquery", "easy-admin"], function ($, ea) {

    var Controller = {

        index: function () {

            let init = {
                api_name: 'index',
            };

            // 监听按钮
            $('.layui-btn-sm').click(function(){

                // 验证表单是否为必填项
                if (!ea.api.formRequiredVerify()) {
                    return false;
                }

                let content   = $(this).html();
                let ref_type  = $(this).attr('ref-type');
                let server_id = $('#server_id').val();

                // 设置弹框配置
                ea.msg.config.anim = $(this).attr('ref-anim');

                // 确认弹框
                ea.msg.confirm('确认要<span class="color-red">'+content+'</span>吗？', function() {
                    
                    // 请求接口
                    ea.request.post({
                        url: init.api_name,
                        data: {
                            ref_type: ref_type,
                            server_id: server_id
                        },
                    }, function(res) {
                        ea.msg.success(res.msg);
                    });
                });

                return false;
            });

            ea.listen();
        },

    };

    return Controller;
});