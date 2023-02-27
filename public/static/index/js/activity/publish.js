define(["jquery", "easy-admin"], function ($, ea) {

    var Controller = {

        index: function () {
            
            // 初始化
            let init = {
                done_idx: 0,
                done_url: 'status',
                done_data1: [],
                done_data2: [],
                server_objs: {},
                done_publish: {},
                index_url: 'activity.publish/index',
                publish_url: 'activity.publish/publishToServer',
                table_elem: '#currentTable',
                table_render_id: 'currentTableRenderId',
                getServerStatus: function(da1, da2) {

                    // 重新设置Loading
                    da1.obj.removeClass('layui-btn-disabled');
                    da1.obj.html('<i class="layui-icon layui-anim layui-anim-rotate layui-anim-loop layui-icon-loading"></i>');
                    da2.obj.removeClass('layui-btn-disabled');
                    da2.obj.html('<i class="layui-icon layui-anim layui-anim-rotate layui-anim-loop layui-icon-loading"></i>');

                    // 关闭请求服务器，显示loading弹框
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
                                da1.obj.addClass('layui-bg-green').html('全部可见');
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

                        data.act_ver = data.act_ver || 0;

                        // 判断活动版本号
                        switch (data.act_ver) {
                            case 0:
                                da2.obj.removeClass('layui-btn-disabled');
                                da2.obj.addClass('layui-bg-primary').html('未知状态');
                            break;
                            default:
                                da2.obj.removeClass('layui-btn-disabled');
                                da2.obj.addClass('layui-bg-green').html(data.act_ver);
                            break;
                        }
                       
                    }, function(err) {
                        
                        da1.obj.removeClass('layui-btn-disabled');
                        da1.obj.addClass('layui-btn-danger').html(err.msg);
                        da2.obj.removeClass('layui-btn-disabled');
                        da2.obj.addClass('layui-btn-danger').html(err.msg);
                    });

                    // 迭代
                    init.done_idx++;

                    if (typeof(init.done_data1[init.done_idx]) != 'undefined') {

                        init.getServerStatus(init.done_data1[init.done_idx], init.done_data2[init.done_idx]);
                    }else {
                        return true;
                    }
                },
                publishToServer: function(sid) {

                    let pobj = init.done_publish[sid] || false;

                    if (!pobj) {

                        return false;
                    }

                    pobj.removeClass('layui-bg-green');
                    pobj.removeClass('layui-btn-danger');
                    pobj.html('<i class="layui-icon layui-anim layui-anim-rotate layui-anim-loop layui-icon-loading"></i>');

                    // 请求发布接口
                    ea.request.post({
                        url:  ea.url(init.publish_url),
                        data: {
                            id: sid,
                            act_id: $('#act_id').val(),
                        },
                    }, function (res) {

                        ea.msg.success('发布成功');
                        pobj.removeClass('layui-btn-disabled');
                        pobj.addClass('layui-bg-green').html('发布成功');

                        let ser_boj = init.server_objs[sid];
                        init.getServerStatus(ser_boj.status, ser_boj.act_ver);

                    }, function (err) {

                        pobj.removeClass('layui-btn-disabled');
                        pobj.addClass('layui-btn-danger').html(err.msg);
                    });

                    return true;
                }
            };

            ea.table.render({
                init: init,
                cols: [[
                    {type: "checkbox"},
                    {field: 'id', width: 80, title: 'ID<br/>id'},
                    {field: 'name', minWidth: 80, title: '名称<br/>name'},
                    {field: 'shost', minWidth: 80, title: '短连接地址<br/>shost', templet: ea.table.url},
                    {
                        field: 'status',
                        minWidth: 120,
                        title: '状态<br/>status',
                        search: false,
                        templet: function (d) {
                            return '<div class="layui-badge-rim server_status" server-id="'+d.id+'"><i class="layui-icon layui-anim layui-anim-rotate layui-anim-loop layui-icon-loading"></i></div>';
                        }
                    },
                    {
                        field: 'act_ver',
                        minWidth: 120,
                        title: '活动版本号<br/>act_ver',
                        search: false,
                        templet: function (d) {
                            return '<div class="layui-badge-rim act_ver" server-id="'+d.id+'"><i class="layui-icon layui-anim layui-anim-rotate layui-anim-loop layui-icon-loading"></i></div>';
                        }
                    },
                    {field: 'create_time', minWidth: 80, title: '创建时间<br/>create_time', search: 'range'},
                    {
                        field: 'publish',
                        minWidth: 120,
                        title: '发布状态<br/>publish',
                        search: false,
                        templet: function (d) {
                            return '<div class="layui-badge-rim publish_status" publish-id="'+d.id+'">-</div>';
                        }
                    }
                ]],
                // 在表格加载完毕后执行的方法
                done: function (res, curr, count) {

                    // 活动版本号
                    $('td .act_ver').each(function(){
                        let sid  = this.getAttribute('server-id');
                        if ( sid != '' && sid != null ) {
                            let obj = {
                                sid: sid,
                                obj: $(this)
                            };
                            init.done_data2.push( obj );

                            // 设置服务器对象
                            let ser_obj = init.server_objs[sid] || {};
                            ser_obj['act_ver'] = obj;
                            init.server_objs[sid] = ser_obj;
                        }
                    });

                    // 服务器状态
                    $('td .server_status').each(function(){
                        let sid  = this.getAttribute('server-id');
                        if ( sid != '' && sid != null ) {
                            let obj = {
                                sid: sid,
                                obj: $(this)
                            };
                            init.done_data1.push( obj );

                            // 设置服务器对象
                            let ser_obj = init.server_objs[sid] || {};
                            ser_obj['status'] = obj;
                            init.server_objs[sid] = ser_obj;
                        }
                    });

                    // 发布状态
                    $('td .publish_status').each(function(){
                        let sid  = this.getAttribute('publish-id');
                        if ( sid != '' && sid != null ) {

                            init.done_publish[sid] = $(this);
                        }
                    });

                    init.getServerStatus(init.done_data1[init.done_idx], init.done_data2[init.done_idx]);
                },
                toolbar: '#toolbar',
            });

            // 监听发布操作
            $('body').on('click', '[data-publish]', function () {
                var table = layui.table;
                var checkStatus = table.checkStatus(init.table_render_id), data = checkStatus.data;
                if (data.length <= 0) {
                    ea.msg.error('请选择需要发布的服务器');
                    return false;
                }

                var title = '';
                // 遍历选择的服务器
                $.each(data, function (i, v) {

                    title += v.id + '. ' + v.name + '<br/>';
                });
                
                // 确认提示
                ea.msg.confirm(title, function () {

                    ea.msg.success('开始发布，请关注发布状态');

                    // 遍历选择的服务器
                    $.each(data, function (i, v) {

                        let sid = v.id;
                        init.publishToServer(sid);
                    });
                });
            });

            ea.listen();
        }
    };
    return Controller;
});