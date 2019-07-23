define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'idc_equipment_log/index' + location.search,
                    add_url: 'idc_equipment_log/add',
                    edit_url: 'idc_equipment_log/edit',
                    del_url: 'idc_equipment_log/del',
                    multi_url: 'idc_equipment_log/multi',
                    table: 'idc_equipment_log',
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
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate},
                        {field: 'date', title: __('Date'), operate:'RANGE', addclass:'datetimerange'},
                        {field: 'unit_name', title: __('Unit_name')},
                        {field: 'engineroom_name', title: __('Engineroom_name')},
                        {field: 'customer_unit', title: __('Customer_unit')},
                        {field: 'num', title: __('Num')},
                        {field: 'equipment_code', title: __('Equipment_code')},
                        {field: 'serial_code', title: __('Serial_code')},
                        {field: 'status', title: __('Status'), formatter: Table.api.formatter.status},
                        {field: 'cause', title: __('Cause')},
                        {field: 'customer_sign', title: __('Customer_sign')},
                        {field: 'idc_sign', title: __('Idc_sign')},
                    ]
                ]
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
        Layer.confirm("导出全部数据<form action='" + Fast.api.fixurl("idc_equipment_log/export") + "' method='post' target='_blank'><input type='hidden' name='starttime' value='' /><input type='hidden' name='endtime' value='' /></form>", {
            title: '导出数据',
            btn: ["确认"],
            success: function (layero, index) {
                $(".layui-layer-btn a", layero).addClass("layui-layer-btn0");
            }
            , yes: function (index, layero) {
                submitForm(ids.join(","), layero);
                return false;
            }
            ,
            btn2: function (index, layero) {
                var ids = [];
                $.each(page, function (i, j) {
                    ids.push(j.id);
                });
                submitForm(ids.join(","), layero);
                return false;
            }
            ,
            btn3: function (index, layero) {
                submitForm("all", layero);
                return false;
            }
        })
    });
    return Controller;
});