define(["jquery", "easy-admin", "vue"], function ($, ea, vue) {

    // Layui 组件
    let form  = layui.form,
        layer = layui.layer;

    // 初始化
    let init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'server.channel/index',
        add_url: 'server.channel/add',
        edit_url: 'server.channel/edit',
        delete_url: 'server.channel/delete',
        getConfigUrl: 'server.channel/getConfig',
        getConfigData: {
            ctype: 'servers'
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


    /**
     * 
     * 渠道-控制类
     * 
     */
    var Controller = {

        index: function () {

            ea.table.render({
                init: init,
                cols: [[
                    {type: "checkbox"},
                    {field: 'id', width: 100, title: 'ID<br/>id'},
                    {field: 'sort', width: 80, title: '排序<br/>sort', edit: 'text'},
                    {field: 'channel_key', width: 300, title: 'KEY<br/>channel_key', edit: 'text'},
                    {field: 'channel_name', title: '名称<br/>channel_name', edit: 'text'},
                    {field: 'expend', width: 100, title: '备注信息<br/>expend'},
                    {field: 'create_time', width: 200, title: '创建时间<br/>create_time', search: 'range'},
                    {field: 'status', minWidth: 80, title: '状态<br/>status', search: 'select', selectList: {1: '正常', 2: '关闭'}, templet: ea.table.switch},
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
        config: function () {

            // 表单赋值
            layui.$('.channel-search').on('click', function(){
                ea.msg.loading();
            });

            setTimeout(function () {
                $('.layui-form-pane').fadeIn(500);
                $('.tr-loading').fadeOut(500, function() {
                    form.render();
                    $('.channel-config').fadeIn(500);
                });
            }, 50);
            
            ea.listen(function(data) {
                return data;
            }, function(res) {
                ea.msg.success(res.msg, function () {
                    window.location.reload();
                });
            });
        },
    };

    return Controller;
});