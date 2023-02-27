define(["jquery", "easy-admin"], function ($, ea) {

    let init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'server.conflict/index',
        remover_url: 'server.conflict/remover',
        open_search: false,
    };

    var Controller = {

        index: function () {

            // 获取管理员列表
            let admin_list   = eval('(' + $('#admin_list').val() + ')');

            // 获取服务器列表
            let server_list  = eval('(' + $('#server_list').val() + ')');

            // 获取配置类型
            let config_types = eval('(' + $('#config_types').val() + ')');

            ea.table.render({
                init: init,
                cols: [[
                    {field: 'id', width: 80, title: 'ID<br/>id'},
                    {field: 'ctype', width: 200, title: '冲突配置<br/>ctype', search: 'select', selectList: config_types, selectDef: '全部配置', searchOp: '%*%'},
                    {field: 'remover', title: '清除内容<br/>remover'},
                    {field: 'admin_id', width: 200, title: '创建人<br/>admin_id', search: 'select', selectList: admin_list, selectDef: '全部管理员'},
                    {field: 'server_id', width: 200, title: '服务器<br/>server_id', search: 'select', selectList: server_list, selectDef: '全部服务器'},
                    {field: 'create_time', width: 200, title: '创建时间', search: 'range'},
                ]],
                toolbar: [ 
                    'refresh',
                    [{
                        text: '清除',
                        url: init.remover_url,
                        method: 'open',
                        auth: 'remover',
                        icon: 'fa fa-remove',
                        class: 'layui-btn layui-btn-sm layui-btn-danger',
                    }],
                ]
            });

            ea.listen();
        },
        remover: function () {
            ea.listen(function (data) {
                data['is_captcha'] = true;
                return data;
            }, function (res) {
                ea.msg.success(res.msg, function () {
                    ea.api.closeCurrentOpen({
                        refreshTable: init.table_render_id
                    });
                });
            }, function (err) {
                ea.msg.alert(err.msg, function () {
                    $('#clean_captcha').val('');
                    $('#refreshCaptcha').trigger('click');
                });
            });
        },
    };
    return Controller;
});