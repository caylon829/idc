define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'idc_engineroomlogvisitlog/index' + location.search,
                    add_url: 'idc_engineroomlogvisitlog/add',
                    edit_url: 'idc_engineroomlogvisitlog/edit',
                    del_url: 'idc_engineroomlogvisitlog/del',
                    multi_url: 'idc_engineroomlogvisitlog/multi',
                    table: 'idc_engineroomvisitlog',
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
                        {field: 'code', title: __('Code'),operate: 'LIKE'},
                        // {field: 'do_time', title: __('Do_time'), operate:'RANGE', addclass:'datetimerange'},
                        // {field: 'finish_time', title: __('Finish_time'), operate:'RANGE', addclass:'datetimerange'},
                        // {field: 'engineroom_name', title: __('Engineroom_name'),operate: 'LIKE'},
                        // {field: 'customer_unit', title: __('Customer_id'),operate: 'LIKE'},
                        // {field: 'type', title: __('Type'),operate: 'LIKE'},
                        {field: 'create_time', title: __('Create_time'), operate:'BETWEEN', addclass:'datetimerange',sortable: true},
                        {field: 'status', title: __('Status'),searchList:{0:'申请',1:'申请通过',2:'已进入',3:'已离开',4:'登记完成',5:'申请已撤销'}},
                        {field: 'times', title: __('Sms'),searchList:{0:'未发送',1:'已发送'}},
                        {field: 'user_name', title: __('Admin_id'), operate: 'LIKE'},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate,formatter: Table.api.formatter.operate, buttons:[{
                                title: __('详细'),
                                classname: 'btn btn-xs btn-primary btn-dialog',
                                text: '详细',
                                icon: 'fa fa-list',
                                url: 'idc_engineroomlogvisitlog/getlist',
                            },{
                                name: 'ajax',
                                title: '发送短信',
                                classname: 'btn btn-xs btn-primary btn-ajax',
                                text: '发送短信',
                                icon: 'fa fa-envelope-o',
                                url: 'idc_engineroomlogvisitlog/send',
                                confirm:'发送一条短信',
                                success: function (data, ret) {
                                    //Layer.alert(ret.msg + ",返回数据：" + JSON.stringify(data));
                                    Layer.alert(ret.msg);
                                    $(".btn-refresh").trigger("click");
                                    //如果需要阻止成功提示，则必须使用return false;
                                    //return false;
                                },
                                error: function (data, ret) {
                                    console.log(data, ret);
                                    Layer.alert(ret.msg);
                                    return false;
                                },
                                hidden:function(row){
                                    //console.log(row);
                                    //if(row.status!='进入'){
                                      //  return true;
                                   // }
                                }
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
        Layer.confirm("导出全部数据<form action='" + Fast.api.fixurl("idc_engineroomlogvisitlog/export") + "' method='post' target='_blank'><input type='hidden' name='starttime' value='' /><input type='hidden' name='endtime' value='' /></form>", {
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