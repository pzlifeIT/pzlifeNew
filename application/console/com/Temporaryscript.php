<?php

namespace app\console\com;

use app\console\Pzlife;
use Config;
use Env;
use Overtrue\Pinyin\Pinyin;
use think\Db;
use cache\Phpredis;

class TemporaryScript extends Pzlife {
    private $redis;

    public function __construct() {
        parent::__construct();
        $this->redis = Phpredis::getConn();
    }

    /**
     * 修改数据库脚本
     *
     */
    public function ModifyDataScript() {
       Db::startTrans();
       try {
//            /* 将大鲨鱼(13122511746)钻石购买关系挂在黄甍(13381867868)下面  2019/04/12 */
//
//            $user    = Db::query("SELECT * FROM pz_users WHERE mobile = 13122511746 AND delete_time=0 ");
//            $up_user = Db::query("SELECT * FROM pz_users WHERE mobile = 13381867868 AND delete_time=0 ");
//            Db::table('pz_diamondvip_get')->where('uid', $user[0]['id'])->update(['share_uid' => $up_user[0]['id']]);
//
//            /* 画(18033698601)钻石购买关系挂在葛小薇(13280730253)下面  2019/04/12 */
//            $user    = Db::query("SELECT * FROM pz_users WHERE mobile = 18033698601 AND delete_time=0 ");
//            $up_user = Db::query("SELECT * FROM pz_users WHERE mobile = 13280730253 AND delete_time=0 ");
//            Db::table('pz_diamondvip_get')->where('uid', $user[0]['id'])->update(['share_uid' => $up_user[0]['id']]);
//
//            /* 画(18033698601)层级关系挂在葛小薇(13280730253)下面  2019/04/12 */
//            $user    = Db::query("SELECT * FROM pz_users WHERE mobile = 18033698601 AND delete_time=0 ");
//            $up_user = Db::query("SELECT * FROM pz_users WHERE mobile = 13280730253 AND delete_time=0 ");
//            Db::table('pz_user_relation')->where('uid', $user[0]['id'])->update(['relation' => $up_user[0]['id'] . ',' . $user[0]['id'], 'pid' => $up_user[0]['id']]);
//
//            /* 开店关系修正
//            应江总（江胜）要求开店邀请未接收到邀请者ID造成的数据出错
//            王恒念(13914041717)店铺开通关系关系归属于张学军(13606221728)  2019/04/15 */
//            Db::table('pz_shop_apply')->where('id', 3)->update(['refe_uid' => 15122, 'refe_uname' => '张学军', 'create_time' => '1555218828']);
//            Db::table('pz_user_relation')->where('uid', '26379')->update(['relation' => '15122,26379', 'pid' => '15122']);
//            Db::table('pz_log_invest')->where('id', 75)->update(['uid' => 15122]);
//            Db::table('pz_shop_apply')->where('id', 2)->update(['create_time' => '1555210837']);
//            Db::table('pz_shop_apply')->where('id', 1)->update(['create_time' => '1555139928']);
//
//            /* 老商城相关账户明细  2019/04/15 */
//
//            $mysql_connect = Db::connect(Config::get('database.db_config'));
//            $memberdata    = $mysql_connect->query("SELECT `mw`.`unionid`,`m`.* FROM pre_member_wxunion AS mw LEFT JOIN pre_member AS m USING(`uid`) ");
//            foreach ($memberdata as $key => $value) {
//                $member_count = $mysql_connect->query('SELECT * FROM pre_member_count WHERE `uid` = ' . $value['uid']);
//                $new_user     = [];
//                if ($member_count) {
//                    $new_user['balance']    = $member_count[0]['redmoney'];
//                    $new_user['commission'] = $member_count[0]['commission'];
//                    $new_user['integral']   = $member_count[0]['bonuspoints'];
//                    if ($member_count[0]['bonuspoints'] > 0) { //积分
//                        Db::table('pz_log_integral')->insert(
//                            [
//                                'order_no'        => '',
//                                'uid'             => $value['uid'],
//                                'result_integral' => $member_count[0]['bonuspoints'],
//                                'stype'           => 3,
//                                'status'          => 2,
//                                'message'         => '老商城转入',
//                                'create_time'     => 1552147200,
//                                'delete_time'     => 0,
//                            ]
//                        );
//                    }
//                    //佣金
//                    if ($member_count[0]['commission'] > 0) {
//                        Db::table('pz_log_trading')->insert(
//                            [
//                                'uid'          => $value['uid'],
//                                'trading_type' => 2,
//                                'change_type'  => 11,
//                                'money'        => $member_count[0]['commission'],
//                                'befor_money'  => 0,
//                                'after_money'  => $member_count[0]['commission'],
//                                'change_type'  => 11,
//                                'message'      => '老商城转入',
//                                'create_time'  => 1552147200,
//                            ]
//                        );
//                    }
//                    //商票
//                    if ($member_count[0]['redmoney'] > 0) {
//                        Db::table('pz_log_trading')->insert(
//                            [
//                                'uid'          => $value['uid'],
//                                'trading_type' => 1,
//                                'change_type'  => 11,
//                                'money'        => $member_count[0]['redmoney'],
//                                'befor_money'  => 0,
//                                'after_money'  => $member_count[0]['redmoney'],
//                                'change_type'  => 11,
//                                'message'      => '老商城转入',
//                                'create_time'  => 1552147200,
//                            ]
//                        );
//                    }
//                }
//            }
            // 提交事务

            /* 将支改改(13661691673)登录手机号改为(17891936793)  2019/04/23 */
            $user    = Db::query("SELECT * FROM pz_users WHERE mobile = 13661691673 AND delete_time=0 ");
            Db::table('pz_users')->where('id', $user[0]['id'])->update(['mobile' => '17891936793']);

            
            Db::commit();
       } catch (\Exception $e) {
           // 回滚事务
           exception($e);
           Db::rollback();
       }
       exit('ok!!');
    }

    /**
     * 标签库redis缓存脚本
     */
    public function labelScript() {
        $goodsRelation = Db::query('select lgr.label_lib_id from pz_label_goods_relation as lgr join pz_goods as g on lgr.goods_id=g.id where g.delete_time=0 and lgr.delete_time=0 and g.status=1');
        $labelIdList   = array_values(array_unique(array_column($goodsRelation, 'label_lib_id')));
//        print_r($labelIdList);die;
        $list = Db::query('select id,label_name,the_heat from pz_label_library where delete_time=0 and id in (' . implode(',', $labelIdList) . ')');
        foreach ($list as $l) {
            $this->setTransform($this->getTransformPinyin($l['label_name']), $l['id']);
            $this->setLabelLibrary($l['id'], $l['label_name']);
            $this->setLabelHeat($l['id'], $l['the_heat']);
        }
    }

    /**
     * 标签转换后存储
     * @param $trans 标签转换后的列表
     * @param $labelLibId 标签库id
     * @author zyr
     */
    private function setTransform($trans, $labelLibId) {
        $redisKey = Config::get('rediskey.label.redisLabelTransform');
        foreach ($trans as $t) {
            if (!$this->redis->hSetNx($redisKey, $t, json_encode([$labelLibId]))) {
                $transLabel = json_decode($this->redis->hGet($redisKey, $t), true);
                if (!in_array($labelLibId, $transLabel)) {
                    array_push($transLabel, $labelLibId);
                    $this->redis->hSet($redisKey, $t, json_encode($transLabel));
                }
            }
        }
    }

    /**
     * @description:
     * @param $labelLibId 标签库id
     * @param $name 标签名
     * @author zyr
     */
    private function setLabelLibrary($labelLibId, $name) {
        $redisKey = Config::get('rediskey.label.redisLabelLibrary');
        $this->redis->hSetNx($redisKey, $labelLibId, $name);
    }

    private function setLabelHeat($labelLibId, $heat) {
        $redisKey = Config::get('rediskey.label.redisLabelLibraryHeat');
        $this->redis->zAdd($redisKey, $heat, $labelLibId);
    }

    private function getTransformPinyin($name) {
        if (empty($name)) {
            return [];
        }
        $pinyin       = new Pinyin('Overtrue\Pinyin\MemoryFileDictLoader');
        $withoutTone2 = implode('', $pinyin->convert($name, PINYIN_UMLAUT_V));
        $withoutTone  = $pinyin->permalink($name, '', PINYIN_UMLAUT_V);
        $ucWord       = $pinyin->abbr($name, '');
        $ucWord2      = $pinyin->abbr($name, '', PINYIN_KEEP_NUMBER);
        $ucWord3      = $pinyin->abbr($name, '', PINYIN_KEEP_ENGLISH);
        $data         = [
            strtolower($name), //全名
            strtolower($withoutTone), //包含非中文的全拼音
            strtolower($withoutTone2), //不包含非中文的全拼音
            strtolower($ucWord3), //拼音首字母,包含字母
            strtolower($ucWord2), //拼音首字母,包含数字
            strtolower($ucWord), //拼音首字母,不包含非汉字内容
        ];
        return array_filter(array_unique($data));
    }
}
