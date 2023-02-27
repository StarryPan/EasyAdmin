define(["jquery", "easy-admin"], function ($, ea) {
    
    /**
     * 
     * 公告-控制类
     * 
     */
    var Controller = {

        index: function () {

            // 初始化
            let init = {
                table_elem: '#currentTable',
                table_render_id: 'currentTableRenderId',
                index_url: 'server.notice/index',
                add_url: 'server.notice/add',
                edit_url: 'server.notice/edit',
                delete_url: 'server.notice/delete',
                export_url: 'server.notice/export',
                import_url: 'server.notice/import',
            };

            // 获取管理员列表
            let admin_list   = eval('(' + $('#admin_list').val() + ')');

            // 服务器渠道列表
            let channel_list = eval('(' + $('#channel_list').val() + ')');
            
            ea.table.render({
                init: init,
                cols: [[
                    {type: "checkbox", width: 50, fixed: 'left'},
                    {field: 'id', width: 80, title: 'ID<br/>id', fixed: 'left'},
                    {field: 'sort', width: 80, title: '排序<br/>sort', edit: 'text', search: false},
                    {field: 'channel_key', width: 200, title: '渠道<br/>channel_key', search: 'select', selectList: channel_list},
                    {field: 'pre_state', width: 100, title: '环境<br/>pre_state', search: 'select', selectList: {0: '外网', 1: '内网'}},
                    {field: 'tag', width: 100, title: '标签<br/>tag', edit: 'text', search: false},
                    {field: 'title', width: 150, title: '标题<br/>title', edit: 'text', search: false},
                    {field: 'content', width: 600, title: '内容<br/>content', edit: 'text', search: false},
                    {field: 'image', width: 100, title: '图片<br/>image', search: false},
                    {field: 'start_date', width: 240, title: '开始时间<br/>start_date', search: false},
                    {field: 'end_date', width: 240, title: '结束时间<br/>end_date', search: false},
                    {field: 'admin_id', width: 240, title: '发布人员<br/>admin_id', search: 'select', selectList: admin_list},
                    {field: 'create_time', width: 200, title: '创建时间<br/>create_time', search: 'range'},
                    {
                        fixed: 'right',
                        field: 'status',
                        width: 80,
                        title: '状态<br/>status',
                        search: 'select', 
                        selectList: {1: '正常', 2: '关闭'},
                        templet: function (d) {
                            let now_time   = ea.api.dateTime();
                            let end_time   = ea.api.dateTime( d.end_date );
                            let start_time = ea.api.dateTime( d.start_date );
                            if (d.status == 2) {
                                return '<span class="layui-badge-rim">关闭</span>';
                            }else if (start_time > now_time || end_time < now_time) {
                                return '<span class="layui-badge">过期</span>';
                            }else {
                                return '<span class="layui-badge layui-bg-blue">正常</span>';
                            }
                        }
                    },
                    {
                        fixed: 'right',
                        width: 120,
                        title: '操作<br/>-',
                        templet: ea.table.tool,
                        operat: [
                            'edit',
                            'delete'
                        ]
                    }
                ]],
            });

            // 设置打开窗口大小（全屏）
            ea.config.open_full = true;
            ea.listen();
        },
        add: function () {
            ea.api.rangeDate();
            ea.listen();
        },
        edit: function () {
            ea.api.rangeDate();
            ea.listen();
        },
    };

    return Controller;
});