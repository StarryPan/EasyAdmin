define(["jquery", "easy-admin"], function ($, ea) {

    var Controller = {

        index: function () {
            
            // 初始化
            let init = {
                done_idx: 0,
                done_url: 'status',
                done_data1: [],
                done_data2: [],
                done_data3: [],
                table_elem: '#currentTable',
                table_render_id: 'currentTableRenderId',
                index_url: 'server.lists/index',
                add_url: 'server.lists/add',
                edit_url: 'server.lists/edit',
                delete_url: 'server.lists/delete',
                modify_url: 'server.lists/modify',
                servercfg_url: 'server.lists/config',
                getServerStatus: function(da1, da2, da3) {
                    ea.request.config.is_loading = false;
                    ea.request.post({
                        url: init.done_url,
                        data: {
                            id: da1.sid
                        }
                    }, function(res) {
                        let data = res.data;

                        // 判断服务器状态
                        switch (parseInt(data.game_status)) {
                            case 1:
                                da1.obj.removeClass('layui-btn-disabled');
                                da1.obj.addClass('layui-bg-blue').html('全部可见');
                            break;
                            case 2:
                                da1.obj.removeClass('layui-btn-disabled');
                                da1.obj.addClass('layui-bg-warm').html('内部测试');
                            break;
                            case 3:
                                da1.obj.removeClass('layui-btn-disabled');
                                da1.obj.addClass('layui-bg-cyan').html('审核状态');
                            break;
                            default:
                                da1.obj.removeClass('layui-btn-disabled');
                                da1.obj.addClass('layui-bg-primary').html('未知状态' + data.game_status);
                            break;
                        }

                        // 判断长连接状态
                        switch (parseInt(data.commontask_status)) {
                            case 1:
                                da2.obj.removeClass('layui-btn-disabled');
                                da2.obj.addClass('layui-bg-blue').html('已启用');
                            break;
                            default:
                                da2.obj.removeClass('layui-btn-disabled');
                                da2.obj.addClass('layui-bg-primary').html('未开启');
                            break;
                        }

                        // 判断SVN版本号
                        switch (data.svn_ver) {
                            case 'nointerface':
                                da3.obj.removeClass('layui-btn-disabled');
                                da3.obj.addClass('layui-bg-primary').html('未知状态');
                            break;
                            default:
                                da3.obj.removeClass('layui-btn-disabled');
                                da3.obj.addClass('layui-bg-blue').html(data.svn_ver);
                            break;
                        }

                    }, function(err) {
                        da1.obj.removeClass('layui-btn-disabled');
                        da1.obj.addClass('layui-btn-danger').html(err.msg);
                        da2.obj.removeClass('layui-btn-disabled');
                        da2.obj.addClass('layui-btn-danger').html(err.msg);
                        da3.obj.removeClass('layui-btn-disabled');
                        da3.obj.addClass('layui-btn-danger').html(err.msg);
                    });

                    // 迭代
                    init.done_idx++;
                    if (typeof(init.done_data1[init.done_idx]) != 'undefined') {

                        init.getServerStatus(init.done_data1[init.done_idx], init.done_data2[init.done_idx], init.done_data3[init.done_idx]);
                    }else {
                        return true;
                    }
                },
            };

            ea.table.render({
                init: init,
                cols: [[
                    {type: "checkbox"},
                    {field: 'id', width: 80, title: 'ID<br/>id'},
                    {field: 'sort', width: 80, title: '排序<br/>sort', edit: 'text'},
                    {field: 'name', minWidth: 80, title: '名称<br/>name', edit: 'text'},
                    {field: 'shost', minWidth: 80, title: '短连接地址<br/>shost', templet: ea.table.url},
                    {field: 'lhost', minWidth: 80, title: '长连接地址<br/>lhost', templet: ea.table.url},
                    {field: 'is_lan', minWidth: 80, title: '多语言<br/>is_lan', templet: ea.table.switch},
                    {field: 'create_time', minWidth: 80, title: '创建时间<br/>create_time', search: 'range'},
                    {
                        field: 'status',
                        width: 120,
                        title: '状态<br/>status',
                        search: false,
                        templet: function (d) {
                            return '<div class="layui-badge-rim server_status" server-id="'+d.id+'"><i class="layui-icon layui-anim layui-anim-rotate layui-anim-loop layui-icon-loading"></i></div>';
                        }
                    },
                    {
                        field: 'web_socket',
                        width: 120,
                        title: '长连接<br/>web_socket',
                        search: false,
                        templet: function (d) {
                            return '<div class="layui-badge-rim server_web_socket" server-id="'+d.id+'"><i class="layui-icon layui-anim layui-anim-rotate layui-anim-loop layui-icon-loading"></i></div>';
                        }
                    },
                    {
                        field: 'svn_version',
                        width: 120,
                        title: 'SVN版本号<br/>svn_version',
                        search: false,
                        templet: function (d) {
                            return '<div class="layui-badge-rim server_svn_version" server-id="'+d.id+'"><i class="layui-icon layui-anim layui-anim-rotate layui-anim-loop layui-icon-loading"></i></div>';
                        }
                    },
                    {
                        width: 250,
                        title: '操作',
                        templet: ea.table.tool,
                        operat: [
                            'edit',
                            [{
                                text: '配置',
                                url: init.servercfg_url,
                                method: 'open',
                                auth: 'edit',
                                class: 'layui-btn layui-btn-normal layui-btn-xs',
                                extend: 'data-full="true"',
                            }],
                            'delete'
                        ]
                    }
                ]],
                //在表格加载完毕后执行的方法
                done: function (res, curr, count) {

                    // 服务器状态
                    $('td .server_status').each(function(){
                        let sid  = this.getAttribute('server-id');
                        if ( sid != '' && sid != null ) {
                            let obj = {
                                sid: sid,
                                obj: $(this)
                            };
                            init.done_data1.push( obj );
                        }
                    });

                    // 长连接状态
                    $('td .server_web_socket').each(function(){
                        let sid  = this.getAttribute('server-id');
                        if ( sid != '' && sid != null ) {
                            let obj = {
                                sid: sid,
                                obj: $(this)
                            };
                            init.done_data2.push( obj );
                        }
                    });

                    // SVN版本号
                    $('td .server_svn_version').each(function(){
                        let sid  = this.getAttribute('server-id');
                        if ( sid != '' && sid != null ) {
                            let obj = {
                                sid: sid,
                                obj: $(this)
                            };
                            init.done_data3.push( obj );
                        }
                    });

                    init.getServerStatus(init.done_data1[init.done_idx], init.done_data2[init.done_idx], init.done_data3[init.done_idx]);
                }
            });

            ea.listen();
        },
        add: function () {
            ea.listen();
        },
        edit: function () {
            ea.listen();
        },
        config: function () {

            let init = {
                add_url: 'server.lists/config_add',
                index_url: 'server.lists/config',
                delete_url: 'server.lists/config_delete',
                modify_url: 'server.lists/config_modify',
                table_elem: '#currentTable',
                edit_confirm: {// 编辑确认
                    title: '确认是需要修改？',
                    is_msg: true,
                    is_data: true,
                },
                table_render_id: 'currentTableRenderId',
            };

            // 服务器ID
            let server_id    = '?id=' + $(init.table_elem).attr('server-id');

            // 拼接url把服务器ID传进去
            init.add_url    += server_id;
            init.index_url  += server_id;
            init.modify_url += server_id;
            init.delete_url += server_id;

            ea.table.render({
                init: init,
                cols: [[
                    {field: 'g_key', title: 'KEY<br/>g_key'},
                    {field: 'g_value', title: 'VALUE<br/>g_value', edit: 'text'},
                    {field: 'remark', minWidth: 50, title: '注释<br/>remark', edit: 'text'},
                    {
                        width: 150,
                        title: '操作',
                        templet: ea.table.tool,
                        operat: [
                            [{
                                class: 'layui-btn layui-btn-danger layui-btn-xs',
                                method: 'get',
                                field: 'g_key',
                                icon: '',
                                text: '删除',
                                title: '确定删除？',
                                auth: 'delete',
                                url: init.delete_url,
                                extend: ""
                            }]
                        ]
                    }
                ]],
                toolbar: [ 'refresh', 'add' ]
            });

            ea.listen();
        },
        config_add: function () {
            ea.listen();
        },
    };
    return Controller;
});