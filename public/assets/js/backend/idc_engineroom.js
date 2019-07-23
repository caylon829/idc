define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'idc_engineroom/index' + location.search,
                    add_url: 'idc_engineroom/add',
                    edit_url: 'idc_engineroom/edit',
                    del_url: 'idc_engineroom/del',
                    multi_url: 'idc_engineroom/multi',
                    table: 'idc_engineroom',
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
                        // {field: 'address', title: __('Address')},
                        // {field: 'phone', title: __('Phone')},
                        // {field: 'qq', title: __('Qq')},
                        // {field: 'remark', title: __('Remark'),formatter: function(value){
                        //     if(value.length>20){
                        //         return value.toString().substr(0, 20)+'...';
                        //     }
                        //     return value.toString();
                        // }},
                        {field: 'update_time', title: __('Update_time'), operate:'BETWEEN', addclass:'datetimerange',sortable: true},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate,formatter: Table.api.formatter.operate, buttons:[{
                                title: __('详细'),
                                classname: 'btn btn-xs btn-primary btn-dialog',
                                text: '详细',
                                icon: 'fa fa-list',
                                url: 'idc_engineroom/getlist',
                            }]},
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
        getlist:function(){
            Controller.api.bindevent();
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