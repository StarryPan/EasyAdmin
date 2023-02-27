define(["jquery", "easy-admin"], function ($, ea) {

    var Controller = {

        index: function () {

            let init = {
                table_elem: '#currentTable',
                table_render_id: 'currentTableRenderId',
                index_url: 'user.lists/index',
                freeze_url: 'user.lists/freeze',
                modify_url: 'user.lists/modify',
                open_search: true,
            };

            // 获取服务器列表
            let server_list = eval('(' + $('#server_list').val() + ')');

            ea.table.config.switch_confirm = {title: '确认要'};
            ea.table.render({
                init: init,
                cols: [[
                    {type: "checkbox"},
                    {field: 'server_id', title: '服务器', hide: true, search: 'select', selectList: server_list, selectDef: '请选择服务器'},
                    {field: 'id', width: 80, title: 'ID<br/>id'},
                    {field: 'account', width: 80, title: '账号<br/>account'},
                    {field: 'nickname', minWidth: 80, title: '昵称<br/>nickname'},
                    {field: 'fhead', minWidth: 80, title: '头像框<br/>fhead', search: false},
                    {field: 'title', minWidth: 80, title: '称号<br/>title', search: false},
                    {field: 'lv', minWidth: 80, title: '等级<br/>lv'},
                    {field: 'exp', minWidth: 80, title: '等级经验<br/>exp', search: false},
                    {field: 'regtime', minWidth: 80, title: '注册时间<br/>regtime', search: 'range'},
                    {field: 'login_time', minWidth: 80, title: '登录时间<br/>login_time', search: 'range'},
                    {field: 'login_days', minWidth: 80, title: '登陆天数<br/>login_days'},
                    {field: 'tutorial', minWidth: 80, title: '新手引导<br/>tutorial', search: false},
                    {field: 'status', minWidth: 80, title: '状态<br/>status', search: 'select', selectList: {1: '正常', 2: '锁定', 3: '冻结'}, templet: ea.table.switch},
                ]],
                toolbar: [ 
                    'refresh', 
                    'freeze', 
                ]
            });
            
            ea.listen();
        },
    };
    return Controller;
});