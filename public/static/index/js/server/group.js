define(["jquery", "easy-admin", "vue"], function ($, ea, vue) {

    // Layui 组件
    let form  = layui.form,
        layer = layui.layer;

    // 初始化
    let init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'server.group/index',
        add_url: 'server.group/add',
        edit_url: 'server.group/edit',
        delete_url: 'server.group/delete',
        modify_url: 'server.group/modify',
        getConfigUrl: 'server.group/getConfig',
        getConfigData: {
            ctype: 'servers'
        },
    };

    var Controller = {

        index: function () {

            ea.table.render({
                init: init,
                cols: [[
                    {type: "checkbox"},
                    {field: 'id', width: 100, title: 'ID<br/>id'},
                    {field: 'group_key', width: 300, title: 'KEY<br/>group_key', edit: 'text'},
                    {field: 'name', width: 300, title: '名称<br/>name', edit: 'text'},
                    {
                        field: 'server',
                        title: '服务器<br/>server',
                        search: false,
                        templet: function (d) {

                            // 解析选择的服务器
                            let servers     = eval('(' + d.server + ')');

                            // 解析服务器列表
                            let server_list = eval('(' + $('#server_list').val() + ')');

                            // 服务器名称
                            let server_name = '';

                            servers.forEach(sid => {
                                
                                if (server_list[sid] != undefined) {
                                    
                                    server_name += server_list[sid] + '<br/>';
                                }
                            });

                            return server_name;
                        }
                    },
                    {field: 'expend', width: 100, title: '备注信息<br/>expend'},
                    {field: 'create_time', width: 200, title: '创建时间<br/>create_time', search: 'range'},
                    {
                        width: 120,
                        title: '操作',
                        templet: ea.table.tool,
                        operat: [
                            'edit',
                            'delete'
                        ]
                    }
                ]],
            });
            
            ea.listen();
        },
        add: function () {

            // 等UI加载完毕后执行
            layer.ready(function () {
                
                // 加载配置
                ea.api.getConfigData(init, function (data) {

                    // 赋值配置
                    server_vue.server_list = data.server_list;

                    // 初始化-服务器
                    server_vue.add_server($('#server_num').val());

                }, function (res) {

                    ea.msg.error(res.msg);
                });
            });

            ea.listen();
        },
        edit: function () {

            // 等UI加载完毕后执行
            layer.ready(function () {

                var thit = this;
                
                // 加载配置
                ea.api.getConfigData(init, function (data) {
                    
                    // 解析选择的服务器
                    let servers = eval('(' + $('#servers').val() + ')');

                    servers.forEach(function(element, rit) {
                        
                        rit++
                        var new_data = {
                            id: rit
                        };
    
                        server_vue.fields.forEach(fkey => {
                            var kname = 'key_' + fkey;
                            new_data[fkey]  = element;
                            new_data[kname] = kname + '_' + rit;
                        });
    
                        server_vue.list.push(new_data);
                    });

                    // 赋值配置
                    server_vue.server_list = data.server_list;

                    // 初始化-服务器
                    !server_vue.list && server_vue.add_server($('#server_num').val());

                    setTimeout(function () {
                        form.render();
                        $('#server_num').val(server_vue.list.length);// 重置设置服务器数量
                    }, 50);

                }, function (res) {
                    ea.msg.error(res.msg);
                });
            });

            ea.listen();
        },
    };

    // 监听选择框（添加服务器）
    form.on('select(add-server)', function (obj) {

        if (obj.value == '' || obj.value == 0) return false;

        // 添加-奖励
        return server_vue.add_server(obj.value);
    });

    /*初始化vue*/
    var server_vue = new vue({
        el: '.layui-form',
        data: {
            list: [],
            fields: [
                'serverid',
            ],
            server_list: []
        },
        methods: {
            delete_server: function (index) {

                if (this.list.length < 2) {

                    layer.msg('不能全部删除', {
                        anim: 6,
                        time: 3000
                    });
                    return false;
                }

                var thit = this;

                layer.msg('确认要删除吗？', {
                    time: 3000000, // 30s后自动关闭
                    btn: ['删除', '取消'],
                    yes: function (obj) {

                        thit.list.splice(index, 1);
                        layer.close(obj);

                        setTimeout(function () {
                            form.render();
                            $('#server_num').val(thit.list.length); // 修改奖励数量
                        }, 50);
                    }
                });
            },
            add_server: function (add_count) {

                let new_row    = Number(add_count);
                let rewit_lent = Number(server_vue.list.length);

                for (var rit = (rewit_lent + 1); rit <= (rewit_lent + new_row); rit++) {

                    var new_data = {
                        id: rit
                    };

                    this.fields.forEach(fkey => {
                        var kname = 'key_' + fkey;
                        new_data[kname] = kname + '_' + rit;
                    });

                    server_vue.list.push(new_data);
                }

                setTimeout(function () {
                    form.render();
                    $('#server_num').val(server_vue.list.length); // 修改奖励数量
                }, 50);

                return false;
            }
        }
    });

    return Controller;
});