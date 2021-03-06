define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'idc_engineroom_part/index' + location.search,
                    add_url: 'idc_engineroom_part/add',
                    edit_url: 'idc_engineroom_part/edit',
                    del_url: 'idc_engineroom_part/del',
                    multi_url: 'idc_engineroom_part/multi',
                    table: 'idc_engineroom_part',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {field: 'id', title: __('Id'),operate:'LIKE'},
                        {field: 'engineroom_name', title: __('Engineroom_name'),operate:'LIKE'},
                        {field: 'type', title: __('Type'),operate:'LIKE'},
                        {field: 'brand', title: __('Brand'),operate:'LIKE'},
                        {field: 'non', title: __('Non'),operate:'LIKE'},
                        {field: 'property', title: __('Property'),operate:false},
                        {field: 'capacity', title: __('Capacity'),operate:false},
                        {field: 'remark', title: __('Remark'),operate:false},
                        {field: 'update_time', title: __('Update_time'), operate:'BETWEEN', addclass:'datetimerange',sortable: true},
                    ]
                ],
                //禁用默认搜索
                search: false,
                //启用普通表单搜索
                commonSearch: true,
                //可以控制是否默认显示搜索单表,false则隐藏,默认为false
                searchFormVisible: true,
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});