define(["jquery", "easy-admin", "jquery-json-viewer"], function ($, ea, jsonV) {
    var form = layui.form;

    // 取消监听文件上传
    ea.config.listen_upload = false;

    var Controller = {

        index: function () {

            let init = {
                table_elem: '#currentTable',
                table_render_id: 'currentTableRenderId',
                index_url: 'server.uploadcfg/index',
                add_url: 'server.uploadcfg/add',
                edit_url: 'server.uploadcfg/edit',
                delete_url: 'server.uploadcfg/delete',
                modify_url: 'server.uploadcfg/modify',
            };

            ea.table.render({
                init: init,
                cols: [[
                    {type: "checkbox"},
                    {field: 'id', width: 80, title: 'ID<br/>id'},
                    {field: 'cfg_key', minWidth: 100, title: 'KEY<br/>cfg_key'},
                    {field: 'cfg_name', minWidth: 80, title: '名称<br/>cfg_name'},
                    {field: 'cfg_data', minWidth: 80, title: '配置数据<br/>cfg_data'},
                    {field: 'diff_data', minWidth: 80, title: '差异配置<br/>diff_data'},
                    {field: 'update_time', minWidth: 80, title: '修改时间<br/>update_time', search: 'range'},
                    {field: 'create_time', minWidth: 80, title: '创建时间<br/>create_time', search: 'range'},
                    {
                        width: 250,
                        title: '操作',
                        templet: ea.table.tool,
                        operat: [
                            [{
                                text: '更新',
                                url: init.edit_url,
                                method: 'open',
                                auth: 'edit',
                                class: 'layui-btn layui-btn-normal layui-btn-xs',
                            }],
                            'delete'
                        ]
                    }
                ]],
                toolbar: [ 'refresh', 'add', 'delete' ]
            });

            // 设置打开窗口大小（全屏）
            ea.config.open_full = true;
            ea.listen();
        },
        add: function () {
            // 监听上传
            ea.api.upload(function(obj) {
                if (!obj.original_name) {
                    ea.msg.error('上传失败，文件名不能为空！');
                    return false;
                }

                let file_data = obj.file_data || '';
                $('.json-viewer-pre').attr('json-data', file_data.replace(/\s*/g,""));
                $('input[name="cfg_key"]').val(obj.original_name.replace('.json', ''));

                // Json可视化插件
                jsonV.render('.json-viewer-pre');
                
                return true;
            });
            ea.listen();
        },
        edit: function () {

            // Json可视化插件
            jsonV.render('.json-viewer-pre');

            // 监听上传
            ea.api.upload(function(obj) {
                if (!obj.original_name) {
                    ea.msg.error('上传失败，文件名不能为空！');
                    return false;
                }

                let cfg_key = $('input[name="cfg_key"]').val();
                let original_name = obj.original_name.replace('.json', '');

                if (cfg_key != original_name) {
                    ea.msg.alert('上传失败，必须上传同名文件 <b style="color: red;">'+cfg_key+'.json</b>');
                    return false;
                }

                let file_data = obj.file_data || '';
                $('.json-viewer-pre').attr('json-data', file_data.replace(/\s*/g,""));

                // Json可视化插件
                jsonV.render('.json-viewer-pre');
                
                return true;
            });
            ea.listen();
        },
    };
    return Controller;
});