<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use fast\Random;
use think\Db;
use PHPExcel_IOFactory;
use PHPExcel;
use PHPExcel_Shared_Date;
use PHPExcel_Style;
use PHPExcel_Style_Alignment;
use PHPExcel_Style_Border;
use PHPExcel_Style_Fill;
use PHPExcel_Style_NumberFormat;

/**
 * IDC设备管理
 *
 * @icon fa fa-circle-o
 */
class IdcEquipmentLog extends Backend
{
    
    /**
     * IdcEquipmentLog模型对象
     * @var \app\admin\model\IdcEquipmentLog
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\IdcEquipmentLog;

    }

    /**
     * analysis 对IDC机房设备出入情况做统计
     */
    public function analysis(){
        $starttime=date('Y-m-01');
        $endtime=date('Y-m-d');
//        var_dump($this->request->post('row/a'));
//        exit;

        if($this->request->request()){
            if($this->request->isPost()){
                $arrtime=$this->request->post('row/a');
            }
            if($this->request->isGet()){
                $arrtime['start_date']=$this->request->get('start_date');
                $arrtime['end_date']=$this->request->get('end_date');
            }
            $starttime=$arrtime['start_date']?$arrtime['start_date']:date('Y-m-01');
            $endtime=$arrtime['end_date']?$arrtime['end_date']:date('Y-m-d');
            $where="date>='$starttime' and date<='$endtime'";
        }else{
            $where="date>='".date('Y-m-01')."' and date<='".date('Y-m-d')."'";;
        }
        //分公司
        if($this->auth->id!=1){
            $where.=" and unit_id=".$_SESSION['think']['unit_id'];
        }
//        $result=$this->model->query('select unit_name,customer_unit,status,count(id) as times,sum(num) as num from fa_idc_equipment_log where '.$where.' group by unit_id,customer_id,status');
//        $count = count($result);
        $data = $this->model->alias('a')->field('b.unit_name,a.customer_unit,a.status,count(a.id) as times,sum(a.num) as num')->join('fa_idc_unit b','a.unit_id=b.id','LEFT')->where($where)->group('a.unit_id,a.customer_id,a.status')->paginate(10,false,['query' => ['start_date'=>$starttime,'end_date'=>$endtime]]);
        $this->view->assign('row',$data);
        $this->view->assign('starttime',$starttime);
        $this->view->assign('endtime',$endtime);
        //$this->assign('result',$result);

        return $this->fetch('analysis');
    }

    /**
     * 查看
     */
    public function index()
    {
        $adminid['id']=$this->auth->id;
        if($this->auth->id==1){
            $unitid=array();
        }else{
            $unitid=Db::table('fa_admin')->field('unit_id')->where($adminid)->find();
            session('unit_id',$unitid['unit_id']);
        }
//        var_dump($_SESSION['think']['admin']['id']);
//        exit;
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->where($where)
                ->where($unitid)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->alias('a')
                ->field('a.id,a.date,b.unit_name,c.engineroom_name,d.customer_unit,a.num,a.equipment_code,a.serial_code,a.status,a.cause,a.customer_sign,a.idc_sign')
                ->join('fa_idc_unit b','a.unit_id=b.id','LEFT')
                ->join('fa_idc_engineroom c','a.engineroom_id=c.id','LEFT')
                ->join('fa_idc_customer d','a.customer_id=d.id','LEFT')
                ->where($where)
                ->where($unitid)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 添加
     */

    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);

                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                $params['admin_id']=$this->auth->id;
                $params['unit_id']=$params['unit_name'];
                //$params['unit_name']=$_SESSION['think']['group_unit_id'][$params['unit_name']];
                $params['engineroom_id']=$params['engineroom_name'];
                //$params['engineroom_name']=$_SESSION['think']['engineroom'][$params['engineroom_name']];
                $params['customer_id']=$params['customer_unit'];
                //$params['customer_unit']=$_SESSION['think']['customer'][$params['customer_unit']];
                $params['idc_sign']=$_SESSION['think']['admin']['nickname'];
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                        $this->model->validateFailException(true)->validate($validate);
                    }
                    $result = $this->model->allowField(true)->save($params);
                    Db::commit();
                } catch (ValidateException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                }
                if ($result !== false) {
                    $this->success();
                } else {
                    $this->error(__('No rows were inserted'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }else{
            $where=array();
            $where2=array();
            $where3=array();
            $adminid['id']=$this->auth->id;
            if($this->auth->id==1){
                $unitid=array();
            }else{
                $unitid=Db::table('fa_admin')->field('unit_id')->where($adminid)->find();
                session('unit_id',$unitid['unit_id']);
                $where['id']=$unitid['unit_id'];
                $where2['group_unit_id']=$unitid['unit_id'];
                $where3['unit_id']=$unitid['unit_id'];
            }
            $unit=array();
            $temp=Db::table('fa_idc_unit')->field('id,unit_name')->where($where)->select();
            foreach ($temp as $value){
                $unit[$value['id']]=$value['unit_name'];
            }
            $engine=array();
            $temp2=Db::table('fa_idc_engineroom')->field('id,engineroom_name')->where($where2)->select();
            foreach ($temp2 as $value){
                $engine[$value['id']]=$value['engineroom_name'];
            }
            $customer=array();
            $temp3=Db::table('fa_idc_customer')->field('id,customer_unit')->where($where3)->select();
            foreach ($temp3 as $value){
                $customer[$value['id']]=$value['customer_unit'];
            }
            $this->view->assign('group_unit_id',$unit);
            $this->view->assign('customer',$customer);
            $this->view->assign('engineroom',$engine);
//            session('customer',$customer);
//            session('engineroom',$engine);
//            session('group_unit_id',$unit);
        }
        return $this->view->fetch();
    }

    /**
     * 编辑
     */
    public function edit($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                $params['admin_id']=$this->auth->id;
                $params['idc_sign']=$_SESSION['think']['admin']['nickname'];
//                $params['unit_id']=$params['unit_name'];
//                $params['unit_name']=$_SESSION['think']['group_unit_id'][$params['unit_name']];
//                $params['engineroom_id']=$params['engineroom_name'];
//                $params['engineroom_name']=$_SESSION['think']['engineroom'][$params['engineroom_name']];
//                $params['customer_id']=$params['customer_unit'];
//                $params['customer_unit']=$_SESSION['think']['customer'][$params['customer_unit']];
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                        $row->validateFailException(true)->validate($validate);
                    }
                    $result = $row->allowField(true)->save($params);
                    Db::commit();
                } catch (ValidateException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                }
                if ($result !== false) {
                    $this->success();
                } else {
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }else{
            $where=array();
            $where2=array();
            $where3=array();
            $adminid['id']=$this->auth->id;
            if($this->auth->id==1){
                $unitid=array();
            }else{
                $unitid=Db::table('fa_admin')->field('unit_id')->where($adminid)->find();
                session('unit_id',$unitid['unit_id']);
                $where['id']=$unitid['unit_id'];
                $where2['group_unit_id']=$unitid['unit_id'];
                $where3['unit_id']=$unitid['unit_id'];
            }
            $unit=array();
            $temp=Db::table('fa_idc_unit')->field('id,unit_name')->where($where)->select();
            foreach ($temp as $value){
                $unit[$value['id']]=$value['unit_name'];
            }
            $engine=array();
            $temp2=Db::table('fa_idc_engineroom')->field('id,engineroom_name')->where($where2)->select();
            foreach ($temp2 as $value){
                $engine[$value['id']]=$value['engineroom_name'];
            }
            $customer=array();
            $temp3=Db::table('fa_idc_customer')->field('id,customer_unit')->where($where3)->select();
            foreach ($temp3 as $value){
                $customer[$value['id']]=$value['customer_unit'];
            }
            $this->view->assign('group_unit_id',$unit);
            $this->view->assign('customer',$customer);
            $this->view->assign('engineroom',$engine);
        }
        $this->view->assign("row", $row);
        return $this->view->fetch();
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
                ->setTitle("机房设备统计")
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
            $worksheet->setTitle('机房设备统计(统计时间'.str_replace('-','',$starttime).'至'.str_replace('-','',$endtime).')');

            $where="a.date>='$starttime' and a.date<='$endtime'";
            if($this->auth->id!=1){
                $where.=" and a.unit_id=".$_SESSION['think']['unit_id'];
            }
            $str=['unit_name'=>'所属公司','customer_unit'=>'客户单位','status'=>'类型','times'=>'记录数','num'=>'设备数量情况统计'];
            $resultarr=$this->model->query('select b.unit_name,a.customer_unit,a.status,count(a.id) as times,sum(a.num) as num from fa_idc_equipment_log a left join fa_idc_unit b on a.unit_id=b.id where '.$where.' group by a.unit_id,a.customer_id,a.status');
            //$resultarr=$this->model->query('select status,count(id) as times,sum(num) as num from fa_idc_equipment_log where '.$where.' group by status');
            $line = 1;
            //$list = ['0'=>['admin_id'=>1,'id'=>12,'idc_sign'=>'test'],'1'=>['admin_id'=>1,'id'=>12,'idc_sign'=>'test'],'2'=>['admin_id'=>1,'id'=>12,'idc_sign'=>'test']];
            $styleArray = array(
                'font' => array(
                    'bold'  => false,
                    'color' => array('rgb' => '000000'),
                    'size'  => 12,
                    'name'  => 'Verdana'
                ));
            $list = $items = collection($resultarr)->toArray();
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
//            var_dump($item);
//            exit;
            $first = array_keys($list[0]);
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
            return;
        }
    }

}
