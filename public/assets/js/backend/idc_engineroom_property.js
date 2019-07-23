define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'idc_engineroom_property/index' + location.search,
                    add_url: 'idc_engineroom_property/add',
                    edit_url: 'idc_engineroom_property/edit',
                    del_url: 'idc_engineroom_property/del',
                    multi_url: 'idc_engineroom_property/multi',
                    table: 'idc_engineroom_property',
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
                        {field: 'code', title: __('Code'),operate:'LIKE'},
                        {field: 'engineroom_name', title: __('Engineroom_id'),operate:'LIKE'},
                        {field: 'sn', title: __('Sn'),operate:'LIKE'},
                        {field: 'type', title: __('Type'),operate:'LIKE'},
                        {field: 'brand', title: __('Brand'),operate:'LIKE'},
                        {field: 'non', title: __('Non'),operate:false},
                        {field: 'u_num', title: __('U_num'),operate:false},
                        {field: 'status', title: __('Status'),searchList:{0:'正常',1:'损坏',2:'作废',3:'遗失'}},
                        {field: 'in_date', title: __('In_date'),operate:'BETWEEN',sortable: true},
                        {field: 'on_date', title: __('On_date'),operate:'BETWEEN',sortable: true},
                        {field: 'out_date', title: __('Out_date'),operate:'BETWEEN',sortable: true},
                        {field: 'property_right', title: __('Property_right'),operate:'LIKE'},
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
    var table = $("#table2");
    var submitForm = function (ids, layero) {
        var starttime = $("input[id=start_date]").val();
        var endtime = $("input[id=end_date]").val();
        $("input[name=starttime]", layero).val(starttime);
        $("input[name=endtime]", layero).val(endtime);
        $("form", layero).submit();
    };
    $(".btn-export").click(function () {
        var ids = Table.api.selectedids(table);
        // console.log(ids, page, all);
        // return false;
        Layer.confirm("导出全部数据<form action='" + Fast.api.fixurl("idc_engineroom_property/export") + "' method='post' target='_blank'><input type='hidden' name='starttime' value='' /><input type='hidden' name='endtime' value='' /></form>", {
            title: '导出数据',
            btn: ["确认"],
            success: function (layero, index) {
                console.log('confirm');
            }
            , yes: function (index, layero) {
                submitForm(ids.join(","), layero);
                Layer.closeAll();
            }
        })
    });
    return Controller;
});