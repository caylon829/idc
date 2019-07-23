define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'idc_customer/index' + location.search,
                    add_url: 'idc_customer/add',
                    edit_url: 'idc_customer/edit',
                    del_url: 'idc_customer/del',
                    multi_url: 'idc_customer/multi',
                    table: 'idc_customer',
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
                        {field: 'id', title: __('Id')},
                        {field: 'customer_unit', title: __('Customer_unit')},
                        {field: 'short_customer_unit', title: __('Short_customer_unit')},
                        {field: 'contact_person', title: __('Contact_person')},
                        {field: 'contact_phone', title: __('Contact_phone')},
                        {field: 'contact_address', title: __('Contact_address')},
                        {field: 'is_vip', title: __('Is_vip'),searchList:{0:'否',1:'是'}},
                        {field: 'email', title: __('Email')},
                        {field: 'code', title: __('Code')},
                        {field: 'area', title: __('Area')},
                        {field: 'create_time', title: __('Create_time'), operate:'RANGE', addclass:'datetimerange'},
                        {field: 'remark', title: __('Remark'),formatter: function(value){
                                if(value.length>20){
                                    return value.toString().substr(0, 20)+'...';
                                }
                                return value.toString();
                            }},
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