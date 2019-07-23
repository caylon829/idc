<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use think\Db;
use fast\Random;
use PHPExcel_IOFactory;
use PHPExcel;
use PHPExcel_Shared_Date;
use PHPExcel_Style;
use PHPExcel_Style_Alignment;
use PHPExcel_Style_Border;
use PHPExcel_Style_Fill;
use PHPExcel_Style_NumberFormat;

/**
 * 机房资产
 *
 * @icon fa fa-circle-o
 */
class IdcEngineroomProperty extends Backend
{
    
    /**
     * IdcEngineroomProperty模型对象
     * @var \app\admin\model\IdcEngineroomProperty
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\IdcEngineroomProperty;

    }
    
    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */
    

    /**
     * 查看
     */
    public function index()
    {
        //当前是否为关联查询
        $this->relationSearch = false;
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax())
        {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField'))
            {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->alias('a')
                ->field('a.id,a.code,a.type,a.sn,a.brand,a.non,a.u_num,a.status,a.in_date,a.on_date,a.out_date,a.property_right,b.engineroom_name as engineroom_name')
                ->join('fa_idc_engineroom b','a.engineroom_id=b.id','LEFT')
                ->where($where)
                ->order('a.id', $order)
                    ->count();

            $list = $this->model
                ->alias('a')
                ->field('a.id,a.code,a.type,a.sn,a.brand,a.non,a.u_num,a.status,a.in_date,a.on_date,a.out_date,a.property_right,b.engineroom_name as engineroom_name')
                ->join('fa_idc_engineroom b','a.engineroom_id=b.id','LEFT')
                    ->where($where)
                    ->order('a.id', $order)
                    ->limit($offset, $limit)
                    ->select();

            foreach ($list as $row) {
                $row->visible(['id','code','engineroom_name','sn','type','brand','non','u_num','status','in_date','on_date','out_date','property_right']);
                
            }
            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }
    /**
     * analysis 对IDC机房来访人员情况做统计
     */
    public function analysis(){
//        var_dump($this->request->post('row/a'));
//        exit;
        $starttime=date('Y-01-01');
        $endtime=date('Y-m-d');
        if($this->request->request()){
            if($this->request->isPost()){
                $arrtime=$this->request->post('row/a');
            }
            if($this->request->isGet()){
                $arrtime['start_date']=$this->request->get('start_date');
                $arrtime['end_date']=$this->request->get('end_date');
            }
            $starttime=$arrtime['start_date']?$arrtime['start_date']:date('Y-01-01');
            $endtime=$arrtime['end_date']?$arrtime['end_date']:date('Y-m-d');
            $where="a.update_time>='$starttime' and a.update_time<='$endtime'";
        }else{
            $where="a.update_time>='".date('Y-01-01')."' and a.update_time<='".date('Y-m-d')."'";
        }
//        $result=$this->model->query('select visit_unit,count(id) as times,sum(visit_num) as num from fa_idc_engineroomvisit_log where '.$where.' group by visit_unit');
//        $count = count($result);
        $data = Db::table('fa_idc_engineroom_property')->alias('a')->field('b.engineroom_name,count(a.id) as sum,a.status')->join('fa_idc_engineroom b','a.engineroom_id=b.id','LEFT')->where($where)->group('b.engineroom_name,a.status')->select();
        $list=array();
        foreach ($data as $value){
            $value['engineroom_name']=$value['engineroom_name']?$value['engineroom_name']:'机房未记录';
            if(isset($list[$value['engineroom_name']]['total'])){
                $list[$value['engineroom_name']]['total']+=$value['sum'];
            }else{
                $list[$value['engineroom_name']]['total']=$value['sum'];
            }
            $list[$value['engineroom_name']]['engineroom_name']=$value['engineroom_name'];

            //设备状态0正常1损坏2作废3遗失
            switch ($value['status']){
                case '0':
                    $list[$value['engineroom_name']]['zc']=$value['sum'];
                    break;
                case '1':
                    $list[$value['engineroom_name']]['sh']=$value['sum'];
                    break;
                case '2':
                    $list[$value['engineroom_name']]['zf']=$value['sum'];
                    break;
                case '3':
                    $list[$value['engineroom_name']]['ys']=$value['sum'];
                    break;
                default:
                    break;
            }
        }
//        var_dump($list);
//        exit;
        foreach ($list as $key=> $value){
            if(!isset($value['zc'])){
                $list[$key]['zc']=0;
            }
            if(!isset($value['sh'])){
                $list[$key]['sh']=0;
            }
            if(!isset($value['zf'])){
                $list[$key]['zf']=0;
            }
            if(!isset($value['ys'])){
                $list[$key]['ys']=0;
            }
        }
        //var_dump($list);
        $this->view->assign('row',$list);
        //$this->view->assign('row',$result);
        $this->view->assign('starttime',$starttime);
        $this->view->assign('endtime',$endtime);
        //$this->assign('result',$result);

        return $this->fetch('analysis');
    }

    /**
     * 导出
     */

    public function export()
    {
        vendor("PHPExcel.PHPExcel.PHPExcel");
        vendor("PHPExcel.PHPExcel.IOFactory");
        vendor("PHPExcel.PHPExcel.Shared_Date");
        vendor("PHPExcel.PHPExcel.Style");
        vendor("PHPExcel.PHPExcel.Style.Alignment");
        vendor("PHPExcel.PHPExcel.Style.Border");
        vendor("PHPExcel.PHPExcel.Style.Fill");
        vendor("PHPExcel.PHPExcel.NumberFormat");
        if ($this->request->isPost()) {
            set_time_limit(5);
            $starttime = $this->request->post('starttime');
            $endtime = $this->request->post('endtime');

            $excel = new PHPExcel();

            $excel->getProperties()
                ->setCreator("IDC")
                ->setLastModifiedBy("IDC")
                ->setTitle("机房设备情况统计")
                ->setSubject("导出");
            $excel->getDefaultStyle()->getFont()->setName('Microsoft Yahei');
            $excel->getDefaultStyle()->getFont()->setSize(12);

            $this->sharedStyle = new PHPExcel_Style();
            $this->sharedStyle->applyFromArray(
                array(
                    'fill'      => array(
                        'type'  => PHPExcel_Style_Fill::FILL_SOLID,
                        'color' => array('rgb' => '000000')
                    ),
                    'font'      => array(
                        'color' => array('rgb' => "000000"),
                    ),
                    'alignment' => array(
                        'vertical'   => PHPExcel_Style_Alignment::VERTICAL_CENTER,
                        'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                        'indent'     => 1
                    ),
                    'borders'   => array(
                        'allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN),
                    )
                ));

            $worksheet = $excel->setActiveSheetIndex(0);
            $worksheet->setTitle('机房设备情况统计(统计时间'.str_replace('-','',$starttime).'至'.str_replace('-','',$endtime).')');

            $where=" a.update_time>='$starttime' and a.update_time<='$endtime'";
            //设备状态0正常1损坏2作废3遗失
            $str=['engineroom_name'=>'所属机房','zc'=>'正常','sh'=>'损坏','zf'=>'作废','ys'=>'遗失','total'=>'总计'];
            $resultarr=Db::table('fa_idc_engineroom_property')->query('select b.engineroom_name,count(a.id) as sum,a.status from fa_idc_engineroom_property a left join fa_idc_engineroom b on a.engineroom_id=b.id where '.$where.' group by b.engineroom_name,a.status');
            $list=array();
            foreach ($resultarr as $value){
                $value['engineroom_name']=$value['engineroom_name']?$value['engineroom_name']:'机房未记录';

                $list[$value['engineroom_name']]['engineroom_name']=$value['engineroom_name'];
                if(isset($list[$value['engineroom_name']]['total'])){
                    $list[$value['engineroom_name']]['total']+=$value['sum'];
                }else{
                    $list[$value['engineroom_name']]['total']=$value['sum'];
                }

                //设备状态0正常1损坏2作废3遗失
                switch ($value['status']){
                    case '0':
                        $list[$value['engineroom_name']]['zc']=$value['sum'];
                        break;
                    case '1':
                        $list[$value['engineroom_name']]['sh']=$value['sum'];
                        break;
                    case '2':
                        $list[$value['engineroom_name']]['zf']=$value['sum'];
                        break;
                    case '3':
                        $list[$value['engineroom_name']]['ys']=$value['sum'];
                        break;
                    default:
                        break;
                }
            }
//        var_dump($list);
//        exit;
            foreach ($list as $key=> $value){
                if(!isset($value['zc'])){
                    $list[$key]['zc']=0;
                }
                if(!isset($value['sh'])){
                    $list[$key]['sh']=0;
                }
                if(!isset($value['zf'])){
                    $list[$key]['zf']=0;
                }
                if(!isset($value['ys'])){
                    $list[$key]['ys']=0;
                }
            }
            $line = 1;
            //$list = ['0'=>['admin_id'=>1,'id'=>12,'idc_sign'=>'test'],'1'=>['admin_id'=>1,'id'=>12,'idc_sign'=>'test'],'2'=>['admin_id'=>1,'id'=>12,'idc_sign'=>'test']];
            $styleArray = array(
                'font' => array(
                    'bold'  => false,
                    'color' => array('rgb' => '000000'),
                    'size'  => 12,
                    'name'  => 'Verdana'
                ));
            $list = $items = collection($list)->toArray();
            foreach ($items as $index => $item) {
                $line++;
                $col = 0;
                foreach ($item as $field => $value) {

                    $worksheet->setCellValueByColumnAndRow($col, $line, $value);
                    $worksheet->getStyleByColumnAndRow($col, $line)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);
                    $worksheet->getCellByColumnAndRow($col, $line)->getStyle()->applyFromArray($styleArray);
                    $col++;
                }
            }

//                    var_dump($item);
//                    exit;
//            var_dump($list);
//            exit;
            //$first = ['engineroom_name','sq','sqcg','yjr','ylk','djwc','sqyqx','total'];
            $first = array_keys(reset($list));
            foreach ($first as $index => $item) {
                $worksheet->setCellValueByColumnAndRow($index, 1, $str[$item]);
            }

            $excel->createSheet();
            // Redirect output to a client’s web browser (Excel2007)
            $title = date("YmdHis").Random::alnum(6);
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $title . '.xlsx"');
            header('Cache-Control: max-age=0');
            // If you're serving to IE 9, then the following may be needed
            header('Cache-Control: max-age=1');

            // If you're serving to IE over SSL, then the following may be needed
            header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
            header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
            header('Pragma: public'); // HTTP/1.0

            $objWriter = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
            $objWriter->save('php://output');
            return 'success';
        }
    }

    /**
     * 生成查询所需要的条件,排序方式
     * @param mixed   $searchfields   快速查询的字段
     * @param boolean $relationSearch 是否关联查询
     * @return array
     */
    protected function buildparams($searchfields = null, $relationSearch = null)
    {
        $searchfields = is_null($searchfields) ? $this->searchFields : $searchfields;

        $relationSearch = is_null($relationSearch) ? $this->relationSearch : $relationSearch;

        $search = $this->request->get("search", '');

        $filter = $this->request->get("filter", '');

        $op = $this->request->get("op", '', 'trim');
        $sort = $this->request->get("sort", "id");
        $order = $this->request->get("order", "DESC");
        $offset = $this->request->get("offset", 0);
        $limit = $this->request->get("limit", 0);
        $filter = (array)\GuzzleHttp\json_decode($filter, true);
//        var_dump($filter);
//        exit;
        foreach ($filter as $key=>$value){
            switch ($key){
                case 'engineroom_name':
                    unset($filter[$key]);
                    $filter['b.'.$key]=$value;
                    break;
                default:
                    unset($filter[$key]);
                    $filter['a.'.$key]=$value;
                    break;
            }

        }
        $op = (array)\GuzzleHttp\json_decode($op, true);
        foreach ($op as $key=>$value){
            switch ($key){
//                case 'engineroom_name':
//                    unset($op[$key]);
//                    $op['c.'.$key]=$value;
//                    break;
//                case 'customer_unit':
//                    unset($op[$key]);
//                    $op['b.'.$key]=$value;
//                    break;
                case 'user_name':
                    unset($op[$key]);
                    $op['d.'.$key]=$value;
                    break;
                default:
                    unset($op[$key]);
                    $op['a.'.$key]=$value;
                    break;
            }

        }
        $filter = $filter ? $filter : [];
        $where = [];
        $tableName = '';
        if ($relationSearch) {
            if (!empty($this->model)) {
                $name = \think\Loader::parseName(basename(str_replace('\\', '/', get_class($this->model))));
                $tableName = $name . '.';
            }
            $sortArr = explode(',', $sort);
            foreach ($sortArr as $index => & $item) {
                $item = stripos($item, ".") === false ? $tableName . trim($item) : $item;
            }
            unset($item);
            $sort = implode(',', $sortArr);
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            $where[] = [$tableName . $this->dataLimitField, 'in', $adminIds];
        }
        if ($search) {
            $searcharr = is_array($searchfields) ? $searchfields : explode(',', $searchfields);
            foreach ($searcharr as $k => &$v) {
                $v = stripos($v, ".") === false ? $tableName . $v : $v;
            }
            unset($v);
            $where[] = [implode("|", $searcharr), "LIKE", "%{$search}%"];
        }
        foreach ($filter as $k => $v) {
            $sym = isset($op[$k]) ? $op[$k] : '=';
            if (stripos($k, ".") === false) {
                $k = $tableName . $k;
            }
            $v = !is_array($v) ? trim($v) : $v;
            $sym = strtoupper(isset($op[$k]) ? $op[$k] : $sym);
            switch ($sym) {
                case '=':
                case '<>':
                    $where[] = [$k, $sym, (string)$v];
                    break;
                case 'LIKE':
                case 'NOT LIKE':
                case 'LIKE %...%':
                case 'NOT LIKE %...%':
                    $where[] = [$k, trim(str_replace('%...%', '', $sym)), "%{$v}%"];
                    break;
                case '>':
                case '>=':
                case '<':
                case '<=':
                    $where[] = [$k, $sym, intval($v)];
                    break;
                case 'FINDIN':
                case 'FINDINSET':
                case 'FIND_IN_SET':
                    $where[] = "FIND_IN_SET('{$v}', " . ($relationSearch ? $k : '`' . str_replace('.', '`.`', $k) . '`') . ")";
                    break;
                case 'IN':
                case 'IN(...)':
                case 'NOT IN':
                case 'NOT IN(...)':
                    $where[] = [$k, str_replace('(...)', '', $sym), is_array($v) ? $v : explode(',', $v)];
                    break;
                case 'BETWEEN':
                case 'NOT BETWEEN':
                    $arr = array_slice(explode(',', $v), 0, 2);
                    if (stripos($v, ',') === false || !array_filter($arr)) {
                        continue 2;
                    }
                    //当出现一边为空时改变操作符
                    if ($arr[0] === '') {
                        $sym = $sym == 'BETWEEN' ? '<=' : '>';
                        $arr = $arr[1];
                    } elseif ($arr[1] === '') {
                        $sym = $sym == 'BETWEEN' ? '>=' : '<';
                        $arr = $arr[0];
                    }
                    $where[] = [$k, $sym, $arr];
                    break;
                case 'RANGE':
                case 'NOT RANGE':
                    $v = str_replace(' - ', ',', $v);
                    $arr = array_slice(explode(',', $v), 0, 2);
                    if (stripos($v, ',') === false || !array_filter($arr)) {
                        continue 2;
                    }
                    //当出现一边为空时改变操作符
                    if ($arr[0] === '') {
                        $sym = $sym == 'RANGE' ? '<=' : '>';
                        $arr = $arr[1];
                    } elseif ($arr[1] === '') {
                        $sym = $sym == 'RANGE' ? '>=' : '<';
                        $arr = $arr[0];
                    }
                    $where[] = [$k, str_replace('RANGE', 'BETWEEN', $sym) . ' time', $arr];
                    break;
                case 'LIKE':
                case 'LIKE %...%':
                    $where[] = [$k, 'LIKE', "%{$v}%"];
                    break;
                case 'NULL':
                case 'IS NULL':
                case 'NOT NULL':
                case 'IS NOT NULL':
                    $where[] = [$k, strtolower(str_replace('IS ', '', $sym))];
                    break;
                default:
                    break;
            }
        }
        $where = function ($query) use ($where) {
            foreach ($where as $k => $v) {
                if (is_array($v)) {
                    call_user_func_array([$query, 'where'], $v);
                } else {
                    $query->where($v);
                }
            }
        };
        return [$where, $sort, $order, $offset, $limit];
    }
}
