define(["jquery", "easy-admin", "vue"], function ($, ea, vue) {

    /**
     * 用户信息-控制类
     */
    var Controller = {

        index: function () {

            // 调用监听
            ea.listen(function(data) {
                return data;
            }, function(res) {

                let data = res.data;

                // 清除之前的数据
                info_vue.tab_title = [];

                // 遍历标签头
                $.each(data, function(key, element) {
                    info_vue.tab_title.push({tkey: key, tname: element.name});

                    // 初始化
                    setTimeout(function () {
                        info_vue.initTabTable(element, key);
                    }, 500);
                });
                
                $('.layui-tab').removeClass('layui-hide');
            });
        },
    };

    /*初始化vue*/
    var info_vue = new vue({
        el: '.layui-tab',
        data: {
            tab_title: []
        },
        methods: {
            initTabTable: function(element, tkey) {

                let data = element.data || [];

                // 表格初始化-参数
                let init = {
                    table_elem: '#'+tkey,
                    table_render_id: tkey,
                    open_search: false,
                };
                
                let comment = element.comment;

                if (data.length > 0) {

                    let cols = [];

                    for (const field in comment) {
                        if (Object.hasOwnProperty.call(comment, field)) {
                            let fname    = comment[field];
                            let cols_val = {field: field, minWidth: 150};

                            // 设置标题
                            cols_val['title'] = (fname || field) + '<br/>' + field;
                            cols.push(cols_val);
                        }
                    }

                    // 初始化Table
                    ea.table.render({
                        init: init,
                        cols: [cols],
                        data: data,
                        toolbar: [],
                        defaultToolbar: false
                    });

                }else {

                    let browse = [];
                    
                    $.each(data, function(field, value) {
                        let title      = comment[field] || field;
                        let browse_val = {field: field, title: title, value: value};
                        browse.push(browse_val);
                    });

                    // 初始化Table
                    ea.browseTable.render({
                        init: init,
                        head: [{title: '字段', width: 150}, {title: '名称', width: 150}, {title: '值'}],
                        data: browse,
                    });
                }
            }
        }
    });

    return Controller;
});