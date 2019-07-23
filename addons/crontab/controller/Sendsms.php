<?php
namespace addons\crontab\controller;

use think\Controller;
use app\common\library\token\driver\Redis;
use think\Db;
class Sendsms extends Controller
{

    /**
     * 初始化方法,最前且始终执行
     */
    public function _initialize()
    {
        // 只可以以cli方式执行
        //if (!$this->request->isCli())
        //$this->error('Autotask script only work at client!');

        parent::_initialize();

        // 清除错误
        error_reporting(0);

        // 设置永不超时
        set_time_limit(0);
    }
    /**
     * 执行定时任务
     */
    public function index()
    {
        $redis=new Redis();

        $result=$redis->pop('list-pid',3);
        if(!empty($result)){
            $ret = $this->send($result);
        }
        return 'Execute completed!' . "\r\n" . date("h:i:sa") . "\r\n".\GuzzleHttp\json_encode($result);
    }
    /**
     * 立即发送短信
     * @return_boolean
     */
    public function send($pid)
    {
        //http://www.91600.com.cn/send?smsaddr=gdrjy&pass=gdrjy2016&phonenum=186xxxxxxxx&content=测试
        $post_data = array();
        $post_data['smsaddr'] = 'gdrjy';
        $post_data['pass'] = 'gdrjy2016';
//        $post_data['sign'] = '';
        $content=Db::table('fa_idc_sms')->where('id=1')->find();
        $msg=Db::table('fa_idc_engineroomvisitlog')->field('status,code')->where('pid='.$pid)->find();
//status(0申请，1申请成功，2已进入，3已离开，4登记完成，5申请已撤销)
        $strarr=[0=>'申请',1=>'申请通过',2=>'已进入',3=>'已离开',4=>'登记完成',5=>'申请已撤销'];
        $content['content']=str_replace('{1}',$msg['code'],$content['content']);
        $content['content']=str_replace('{2}',$strarr[$msg['status']],$content['content']);
        $post_data['phonenum'] = '18802590396';
        $post_data['content'] = $content['sign'].$content['content'].date('Y-m-d H:i:s'); //短信内容
        $url='http://www.91600.com.cn/send?';
        $o='';
        foreach ($post_data as $k=>$v)
        {
            $o.="$k=".urlencode($v).'&';
        }
        $post_data=substr($o,0,-1);
//        var_dump($post_data);
//        exit;
        //请求示例
        //smsaddr=gdrjy&pass=gdrjy2016&phonenum=186xxxxxxxx&content=测试
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //如果需要将结果直接返回到变量里，那加上这句。
        $result = curl_exec($ch);
        //$result = \fast\Http::sendRequest('http://sms10692.com/sms.aspx?action=send', json_encode($postArr), 'POST', $options);
        if ($result)
        {
            return true;
        }
        else
        {
            $this->error = $result;
        }
    }
}

?>
