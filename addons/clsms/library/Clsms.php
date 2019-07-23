<?php

namespace addons\clsms\library;

/**
 * 创蓝SMS短信发送
 * 如有问题，请加微信  andiff424  QQ:165607361
 */
class Clsms
{

    private $_params = [];
    protected $error = '';
    protected $config = [];

    public function __construct($options = [])
    {
        if ($config = get_addon_config('clsms'))
        {
            $this->config = array_merge($this->config, $config);
        }
        $this->config = array_merge($this->config, is_array($options) ? $options : []);
    }

    /**
     * 单例
     * @param array $options 参数
     * @return Clsms
     */
    public static function instance($options = [])
    {
        if (is_null(self::$instance))
        {
            self::$instance = new static($options);
        }
        return self::$instance;
    }

    /**
     * 立即发送短信
     *
     * @return boolean
     */
    public function send()
    {
        $post_data = array();
        $post_data['userid'] = 3994;
        $post_data['account'] = 'caylon829';
        $post_data['password'] = '123qwe';
//        $post_data['sign'] = '';
        $post_data['content'] = '【银禾科技】测试php提交12333'; //短信内容
        $post_data['mobile'] = '18802590396';
        $post_data['sendtime'] = ''; //时定时发送，输入格式YYYY-MM-DD HH:mm:ss的日期值
        $url='http://sms10692.com/sms.aspx?action=send';
        $o='';
        foreach ($post_data as $k=>$v)
        {
            $o.="$k=".urlencode($v).'&';
        }
        $post_data=substr($o,0,-1);
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
        return FALSE;
    }

    private function _params()
    {
        $smstype = isset($this->_params['smstype']) ? $this->_params['smstype'] : 0;
        return array_merge([
            'smstype'  => $smstype,
            'account'  => ($smstype ? $this->config['key1'] : $this->config['key']),
            'password' => ($smstype ? $this->config['secret1'] : $this->config['secret']),
            'sign'     => $this->config['sign'],
            'report'   => true,
        ], $this->_params);
    }

    /**
     * 获取错误信息
     * @return array
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * 短信类型
     * @param   string    $st       0验证码1会员营销短信（会员营销短信不能测试）
     * @return Clsms
     */
    public function smstype($st = 0)
    {
        $this->_params['smstype'] = $st;
        return $this;
    }

    /**
     * 接收手机
     * @param   string  $mobile     手机号码
     * @return Clsms
     */
    public function mobile($mobile = '')
    {
        $this->_params['mobile'] = $mobile;
        return $this;
    }

    /**
     * 短信内容
     * @param   string  $msg        短信内容
     * @return Clsms
     */
    public function msg($msg = '')
    {
        $this->_params['msg'] = $msg;
        return $this;
    }

}
