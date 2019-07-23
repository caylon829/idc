<?php
namespace addons\crontab\controller;

use app\common\model\Crontab;
use Cron\CronExpression;
use fast\Http;
use think\Controller;
use think\Db;
use think\Exception;
use think\Log;

/**
 * 定时任务接口
 *
 * 以Crontab方式每分钟定时执行,且只可以Cli方式运行
 * @internal
 */
class Autotask extends Controller
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
        ini_set('memory_limit', '-1');
        $dbConfig1 = [
            // 数据库类型
            'type' => 'mysql',
            // 数据库连接DSN配置
            'dsn' => '',
            // 服务器地址
            'hostname' => 'localhost',
            // 数据库名
            'database' => 'idc',
            // 数据库用户名
            'username' => 'root',
            // 数据库密码
            'password' => 'root',
            // 数据库连接端口
            'hostport' => '3306',
            // 数据库连接参数
            'params' => [],
            // 数据库编码默认采用utf8
            'charset' => 'utf8',
            // 数据库表前缀
            'prefix' => '',
        ];
        $dbConfig2 = [
            // 数据库类型
            'type' => 'mysql',
            // 数据库连接DSN配置
            'dsn' => '',
            // 服务器地址
            'hostname' => '121.201.75.243',
            // 数据库名
            'database' => 'ibss',
            // 数据库用户名
            'username' => 'ibss',
            // 数据库密码
            'password' => '4DRHS62MGBJzLDep',
            // 数据库连接端口
            'hostport' => '3306',
            // 数据库连接参数
            'params' => [],
            // 数据库编码默认采用utf8
            'charset' => 'utf8',
            // 数据库表前缀
            'prefix' => '',
        ];
        $time = time();
        $logDir = LOG_PATH . 'crontab/';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755);
        }
        //筛选未过期且未完成的任务

        $limit=20;
        $execTime = time();
        if(date("YmdHi", $execTime)===date("Ymd0000", $execTime)){
            $limit=7;
        }

        $crontabList = Crontab::where('status', '=', 'normal')->order('weigh desc,id desc')->limit($limit)->select();

        foreach ($crontabList as $crontab) {
            if ($crontab['title'] == 'wlist') {
                $maxid = Db::table('fa_idc_engineroomvisitlog')->max('pid');
            } elseif ($crontab['title'] == 'user') {
                $maxid = Db::table('fa_idc_user')->max('user_id');
            } else {
                $maxid = Db::table('fa_idc_' . $crontab['title'])->max('id');
            }
            $update = [];
            $execute = FALSE;
            if ($time < $crontab['begintime']) {
                //任务未开始
                continue;
            }
            if ($crontab['maximums'] && $crontab['executes'] > $crontab['maximums']) {
                //任务已超过最大执行次数
                $update['status'] = 'completed';
            } else if ($crontab['endtime'] > 0 && $time > $crontab['endtime']) {
                //任务已过期
                $update['status'] = 'expired';
            } else {
                //重复执行
                //如果未到执行时间则继续循环
                $cron = CronExpression::factory($crontab['schedule']);
                if (!$cron->isDue(date("YmdHi", $execTime)) || date("YmdHi", $execTime) === date("YmdHi", $crontab['executetime']))
                    continue;
                $execute = TRUE;
            }

            // 如果允许执行
            if ($execute) {
                $update['executetime'] = $time;
                $update['executes'] = $crontab['executes'] + 1;
                $update['status'] = ($crontab['maximums'] > 0 && $update['executes'] >= $crontab['maximums']) ? 'completed' : 'normal';
            }

            // 如果需要更新状态
            if (!$update)
                continue;
            // 更新状态
            $crontab->save($update);

//            var_dump($crontab['type']);
//            var_dump($execute);
            // 将执行放在后面是为了避免超时导致多次执行
            if (!$execute)
                continue;
            try {
                if ($crontab['type'] == 'url') {
                    if (substr($crontab['content'], 0, 1) == "/") {
                        // 本地项目URL
                        exec('nohup php ' . ROOT_PATH . 'public/index.php ' . $crontab['content'] . ' >> ' . $logDir . date("Y-m-d") . '.log 2>&1 &');
                    } else {
                        // 远程异步调用URL
                        Http::sendAsyncRequest($crontab['content']);
                    }
                } else if ($crontab['type'] == 'sql') {
                    //这里需要强制重连数据库,使用已有的连接会报2014错误
                    $connect2 = Db::connect($dbConfig2, true);
                    if ($crontab['weigh'] == 5) {
                        if($crontab['title']=='wlist'){
                            $resultarr2=$connect2->query($crontab['content'].' where ID>'.$maxid.' and Type="技术随工" limit 10');
                        }else{
                            if($crontab['title']=='user'){
                                $resultarr2=$connect2->query($crontab['content'].' where UserID>'.$maxid.' limit 10');
                            }else{
                                $resultarr2=$connect2->query($crontab['content'].' where ID>'.$maxid.' limit 10');
                            }
                        }
                    } else {
                        if($crontab['title']=='wlist'){
                            $resultarr2 = $connect2->query($crontab['content'].' where Type="技术随工" ');
                        }else{
                            $resultarr2 = $connect2->query($crontab['content']);
                        }

                    }
                    Db::close($connect2);
                    if (empty($resultarr2)) {
                        echo $crontab['title'] . ' is finish!' . "\r\n";
                        continue;
                    }
                    $connect = Db::connect($dbConfig1, true);
                    $sum = count($resultarr2);
//                    var_dump($sum);
//                    exit;
                    Db::startTrans();
                    try {
                        switch ($crontab['title']) {
                            case 'customer':
                                if ($crontab['weigh'] != 5) {
                                    $connect->table('fa_idc_customer')->execute("truncate table fa_idc_customer");
                                }
                                for ($i = 0; $i < $sum; $i++) {
                                    $connect->table('fa_idc_customer')->insert($resultarr2[$i]);
                                    if ($i % 10000 == 0) {
                                        Db::commit();
                                        Db::startTrans();
                                    }
                                }
                                break;
                            //fa_idc_engineroomvisitlog 由wlist（权重配置999）和worklist组成，需要分开导入两次sql
                            case 'wlist':
                                if ($crontab['weigh'] != 5) {
                                    $connect->table('fa_idc_engineroomvisitlog')->execute("truncate table fa_idc_engineroomvisitlog");
                                }
                                for ($i = 0; $i < $sum; $i++) {
                                    $connect->table('fa_idc_engineroomvisitlog')->insert($resultarr2[$i]);
                                    if ($i % 10000 == 0) {
                                        Db::commit();
                                        Db::startTrans();
                                    }
                                }
                                break;
                            case  'worklist':
                                if ($crontab['weigh'] != 5) {
                                    $connect->table('fa_idc_engineroomvisitlog')->where('qid>1')->delete();
                                }
                                for ($i = 0; $i < $sum; $i++) {
                                    $connect->table('fa_idc_engineroomvisitlog')->insert($resultarr2[$i]);
                                    if ($i % 10000 == 0) {
                                        Db::commit();
                                        Db::startTrans();
                                    }
                                }
                                break;
                            case 'user':
                                if ($crontab['weigh'] != 5) {
                                    $connect->table('fa_idc_user')->execute("truncate table fa_idc_user");
                                }
                                for ($i = 0; $i < $sum; $i++) {
                                    $connect->table('fa_idc_user')->insert($resultarr2[$i]);
                                    if ($i % 10000 == 0) {
                                        Db::commit();
                                        Db::startTrans();
                                    }
                                }
                                break;
                            case 'engineroom':
                                if ($crontab['weigh'] != 5) {
                                    $connect->table('fa_idc_engineroom')->execute("truncate table fa_idc_engineroom");
                                }
                                for ($i = 0; $i < $sum; $i++) {
                                    $connect->table('fa_idc_engineroom')->insert($resultarr2[$i]);
                                    if ($i % 10000 == 0) {
                                        Db::commit();
                                        Db::startTrans();
                                    }
                                }
                                break;
                            case 'enginecabinet':
                                if ($crontab['weigh'] != 5) {
                                    $connect->table('fa_idc_enginecabinet')->execute("truncate table fa_idc_enginecabinet");
                                }
                                for ($i = 0; $i < $sum; $i++) {
                                    $connect->table('fa_idc_enginecabinet')->insert($resultarr2[$i]);
                                    if ($i % 10000 == 0) {
                                        Db::commit();
                                        Db::startTrans();
                                    }
                                }
                                break;
                            case 'engineroom_property':
                                if ($crontab['weigh'] != 5) {
                                    $connect->table('fa_idc_engineroom_property')->execute("truncate table fa_idc_engineroom_property");
                                }
                                for ($i = 0; $i < $sum; $i++) {
                                    $connect->table('fa_idc_engineroom_property')->insert($resultarr2[$i]);
                                    if ($i % 10000 == 0) {
                                        Db::commit();
                                        Db::startTrans();
                                    }
                                }
                                break;
                            case 'engineroom_part':
                                if ($crontab['weigh'] != 5) {
                                    $connect->table('fa_idc_engineroom_part')->execute("truncate table fa_idc_engineroom_part");
                                }
                                for ($i = 0; $i < $sum; $i++) {
                                    $connect->table('fa_idc_engineroom_part')->insert($resultarr2[$i]);
                                    if ($i % 10000 == 0) {
                                        Db::commit();
                                        Db::startTrans();
                                    }
                                }
                                break;
                            default:
                                break;
                        }
                        Db::commit();
                    } catch (\Exception $e) {
                        return \GuzzleHttp\json_encode($e->getMessage());
                        Db::rollback();
                    }
                } else if ($crontab['type'] == 'shell') {
                    // 执行Shell
                    exec($crontab['content'] . ' >> ' . $logDir . date("Y-m-d") . '.log 2>&1 &');
                }
            } catch (Exception $e) {
                Log::record($e->getMessage());
            }
        }
        return 'Execute completed!' . "\r\n" . date("h:i:sa") . "\r\n";
    }

}