<?php

namespace app\console\com;

use app\console\Pzlife;
use Config;
use Env;
use function Qiniu\json_decode;
use think\Db;
use cache\Phpredis;

class LocalScript extends Pzlife {
    private $redis;

    /**
     * 数据库连接
     *
     */
    public function db_connect($databasename) {
        if ($databasename == 'old') {
            return Db::connect(Config::get('database.db_config'));
        } else {
            return Db::connect(Config::get('database.'));
        }

    }

    /**
     * 临时脚本,查找关系表里不存在的用户
     */
    public function clearUser() {
        $otherUserSql = "select relation from pz_user_relation where uid!=1 and delete_time=0";
        $userOther    = Db::query($otherUserSql);
        $data         = [];
        foreach ($userOther as $uo) {
            $uids = explode(',', $uo['relation']);
            foreach ($uids as $uid) {
                $userSql = "select id from pz_users where id={$uid} and delete_time=0 limit 1";
                $user    = Db::query($userSql);
                if (empty($user)) {
                    array_push($data, $uid);
                }
            }
        }
        print_r(implode(',', array_unique($data)));
        die;
    }

    /**
     * 用户数据脚本转换
     *
     */
    public function user() {
        //连接数据库
        $mysql_connect = Db::connect(Config::get('database.db_config'));
        /*         $con1 = mysqli_connect("localhost", "root", "", "pzapi");//导入数据库
                    mysqli_query($con1, 'set names utf8');
                    //SQL查询语句

                    $query = mysqli_query($con1, $member);
                    while ($value = mysqli_fetch_assoc($query)) {
                    var_dump( $value );
                    exit;
                    } */
        ini_set('memory_limit', '1024M');
        $password   = hash_hmac('sha1', '123456', 'userpass');
        $member     = "SELECT `mw`.`unionid`,`m`.* FROM pre_member_wxunion AS mw LEFT JOIN pre_member AS m USING(`uid`) ";
        $memberdata = $mysql_connect->query($member);
        // print_r(count($memberdata));die;
        foreach ($memberdata as $key => $value) {
            /* 查出原用户关系 */
            $member_relationship = [];
            $member_relationship = $mysql_connect->query('SELECT * FROM pre_member_relationship WHERE `uid` = ' . $value['uid']);
            if (!$member_relationship) {
                continue;
            }
            // print_r($value);die;
            /* 用户关系数组初始化 */
            $user_relation        = [];
            $user_relation['uid'] = $value['uid'];
            $user_relation['pid'] = $member_relationship[0]['supuid'];
            $hierarchy            = json_decode($member_relationship[0]['hierarchy']);
            $new_relation         = [];
            if ($hierarchy) {
                // $user_relation['my_boss'] = $hierarchy[0];
                if ($mysql_connect->query('SELECT * FROM pre_shop_relationship WHERE `target_uid` = ' . $hierarchy[0])) {
                    /* do { */
                    // $relationship = $mysql_connect->query('SELECT * FROM pre_shop_relationship WHERE `target_uid` = ' . $hierarchy[0]);
                    // $new_relation[] = $relationship[0]['uid'];
                    /*  } while (!$relationship); */
                }
            } else {
                // $user_relation['my_boss'] = 0;
            }
            $hierarchy[] = $value['uid'];
            /* 用户信息初始化 */
            $new_user       = [];
            $new_user['id'] = $value['uid'];
            /* 当用户为BOSS时 */
            if ($value['boss'] == 1) {
                $user_relation['is_boss'] = 1;
                $shop                     = $mysql_connect->query('SELECT * FROM pre_shop WHERE `uid` = ' . $value['uid']);
                if ($value['mobile']) {
                    $new_user['mobile'] = $value['mobile'];
                }
                if ($shop) {
                    // $new_user['sex'] = $shop[0]['sex'];
                    // $new_user['idcard'] = $shop[0]['idcard'];
                    // $new_user['mobile'] = $shop[0]['mobile'];
                    $new_user['true_name'] = $shop[0]['linkman'];
                    if ($shop[0]['label'] == 'entrepreneur') {
                        $new_user['user_identity'] = 3;
                    } else {
                        $new_user['user_identity'] = 4;
                        $new_shop                  = [];
                        $new_shop['id']            = $shop[0]['shopid'];
                        $new_shop['uid']           = $shop[0]['uid'];
                        $new_shop['shop_name']     = $shop[0]['name'];
                        $new_shop['server_mobile'] = $shop[0]['service'];
                        $new_shop['status']        = $shop[0]['status'];
                        $new_shop['create_time']   = time();
                        $new_shop['shop_right']    = ' ';

                        // print_r($new_shop);die;
                        $new_shop = $this->delDataEmptyKey($new_shop);
                        Db::table('pz_shops')->insert($new_shop);

                    }
                }
                if ($mysql_connect->query('SELECT * FROM pre_shop_relationship WHERE `target_uid` = ' . $value['uid'])) {
                    /* do { */
                    $relationship = $mysql_connect->query('SELECT * FROM pre_shop_relationship WHERE `target_uid` = ' . $value['uid']);

                    if ($relationship) {
                        $new_relation[] = $relationship[0]['uid'];
                    }
                    /*  } while (!$relationship); */
                }

            } /* 当用户身份为钻石会员时 */
            elseif ($value['label'] == 'diamondvip') {
                $new_user['user_identity'] = 2;
                $new_user['mobile']        = $value['mobile'];
                $user_relation['is_boss']  = 2;
                $diamondvip                = $mysql_connect->query('SELECT * FROM pre_diamondvip_get WHERE `uid` = ' . $value['uid'] . ' AND `status`= 1 ORDER BY `gdid` DESC LIMIT 1');
                if ($diamondvip) {
                    $new_user['true_name'] = $diamondvip[0]['linkman'];
                }
            } /* 普通用户信息 */
            else {
                $new_user['user_identity'] = 1;
                $user_relation['is_boss']  = 2;
                if ($value['mobile']) {
                    $new_user['mobile'] = $value['mobile'];
                }
            }
            /* 新用户信息 */
            $new_user['passwd']    = $password;
            $new_user['user_type'] = 1;
            $new_user['nick_name'] = $value['nickname'];
            if ($value['avatar']) {
                $new_user['avatar'] = $value['avatar'];
            }
            $new_user['unionid']           = trim($value['unionid']);
            $new_user['bindshop']          = $value['bingshopid'];
            $new_user['commission_freeze'] = 2;
            /* 查询用户积分数据 */
            $member_count = $mysql_connect->query('SELECT * FROM pre_member_count WHERE `uid` = ' . $value['uid']);
            if ($member_count) {
                $new_user['balance']    = $member_count[0]['redmoney'];
                $new_user['commission'] = $member_count[0]['commission'];
                $new_user['integral']   = $member_count[0]['bonuspoints'];
            }
            $user_relation['relation'] = join(',', $hierarchy);
            if ($new_relation) {
                $user_relation['relation'] = join(',', $new_relation) . ',' . $user_relation['relation'];
            }
            $new_user      = $this->delDataEmptyKey($new_user);
            $user_relation = $this->delDataEmptyKey($user_relation);
            // print_r( $member_relationship );

            // print_r( $user_relation );die;
            // 启动事务
            Db::startTrans();
            try {
                Db::table('pz_users')->insert($new_user);
                Db::table('pz_user_relation')->insert($user_relation);

                // 提交事务
                Db::commit();
            } catch (\Exception $e) {
                // 回滚事务
                exception($e);
                die;
                Db::rollback();
                continue;
            }
            /* 事务提交 */
            // Db::transaction(function () {
            //     Db::table('pz_users')->insert($new_user);
            //     Db::table('pz_user_relation')->insert($user_relation);
            // });

        }
    }

    /**
     * 商品数据脚本
     *
     */
    public function goods() {
        ini_set('memory_limit', '1024M');
        $mysql_connect = Db::connect(Config::get('database.db_config'));
        /* 查询供应商 */
        $suppliersql  = "SELECT * FROM pre_supplier ";
        $supplierdata = $mysql_connect->query($suppliersql);
        Db::startTrans();
        try {
            foreach ($supplierdata as $key => $value) {
                // print_r($value);
                $supplier          = [];
                $supplier['id']    = $value['supid'];
                $supplier['tel']   = trim($value['service']);
                $supplier['name']  = $value['name'];
                $supplier['image'] = $value['image'];
                $supplier['title'] = trim($value['description']);
                $supplier['desc']  = $value['expresstxt'];
                $supplier          = $this->delDataEmptyKey($supplier);
                // print_r($supplier);
                Db::table('pz_supplier')->insert($supplier);
                /* 查询供应商商品 */
                $goodsSql  = "SELECT * FROM pre_commodity WHERE `supid` ='{$value['supid']}'  ";
                $goodsdata = $mysql_connect->query($goodsSql);

                foreach ($goodsdata as $goods => $data) {
                    $goods                = [];
                    $goods['id']          = $data['comid'];
                    $goods['supplier_id'] = $data['supid'];
                    $goods['goods_name']  = $data['title'];
                    $goods['goods_type']  = 1;
                    $goods['title']       = $data['title'];
                    $goods['subtitle']    = $data['subtitle'];
                    $goods['image']       = $data['image'];
                    $goods['status']      = 0;
                    $goods                = $this->delDataEmptyKey($goods);
                    Db::table('pz_goods')->insert($goods);
                    // print_r($goods);
                    /*  $goods_images = str_replace("<p>",'',$data['content']);
                     $goods_images = str_replace("</p>",'',$goods_images);
                     $goods_images = str_replace("/><",'/>,<',$goods_images);
                     $goods_images = explode(',',$goods_images);
                     foreach ($goods_images as $gi => $gimage) {
                         preg_match_all('#"(.*?)"#', $gimage,$newimage);
                         $goods_image = [];
                         $goods_image['goods_id'] = $data['comid'];
                         $goods_image['source_type'] = 4;
                         $goods_image['image_type'] = 1;
                         $goods_image['image_path'] = $newimage[0][0];
                         // print_r($goods_image);
                         Db::table('pz_goods_image')->insert($goods_image);
                     } */
                    // die;
                    $goods_banner = $mysql_connect->query("SELECT * FROM pre_commodity_image WHERE `comid`=" . $data['comid']);

                    foreach ($goods_banner as $gb => $banner) {
                        $new_goodsbanner                = [];
                        $new_goodsbanner['goods_id']    = $banner['comid'];
                        $new_goodsbanner['source_type'] = 4;
                        $new_goodsbanner['image_type']  = 2;
                        $new_goodsbanner['image_path']  = $banner['image'];
                        // print_r($new_goodsbanner);die;
                        Db::table('pz_goods_image')->insert($new_goodsbanner);
                    }
                }
                // exit;
            }
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
        }
    }

    /**
     * 去除数组中空值的键值对
     *
     */
    public function delDataEmptyKey($data) {
        foreach ($data as $key => $value) {
            if (!$value) {
                unset($data[$key]);
            }
        }
        return $data;
    }

    /**
     * 定时清理redis无效的con_id
     */
    public function clearConId() {
        $this->redis    = Phpredis::getConn();
        $lastTime       = time() - 2592000;//30天前
        $redisConIdTime = Config::get('rediskey.user.redisConIdTime');
        $redisConIdUid  = Config::get('rediskey.user.redisConIdUid');
        $res            = $this->redis->zRangeByScore($redisConIdTime, 0, $lastTime);
        if (empty($res)) {
            exit('info_is_null');
        }
        foreach ($res as $r) {
            $this->redis->zDelete($redisConIdTime, $r);
            $this->redis->hDel($redisConIdUid, $r);
        }
    }

    /**
     * 定时清理redis无效的cms_con_id
     */
    public function clearCmsConId() {
        $this->redis       = Phpredis::getConn();
        $lastTime          = time() - 172800;//2天前
        $redisCmsConIdTime = Config::get('rediskey.user.redisCmsConIdTime');
        $redisCmsConIdUid  = Config::get('rediskey.user.redisCmsConIdUid');
        $res               = $this->redis->zRangeByScore($redisCmsConIdTime, 0, $lastTime);
        if (empty($res)) {
            exit('info_is_null');
        }
        foreach ($res as $r) {
            $this->redis->zDelete($redisCmsConIdTime, $r);
            $this->redis->hDel($redisCmsConIdUid, $r);
        }
    }

    public function userAddress() {
        $addressdata = file_get_contents('./addressdata.json');//读取地址文件

        $addressdata     = json_decode($addressdata, true);
        $mysql_connect   = Db::connect(Config::get('database.db_config'));
        $old_address_sql = 'SELECT * FROM pre_member_address';
        $old_address     = $mysql_connect->query($old_address_sql);
        // print_r($old_address);die;
        foreach ($old_address as $old => $address) {
            $user = $this->getUserInfo($address['uid']);
            // print_r($address);die;
            // print_r($user);die;
            if (empty($user)) {
                continue;
            }
            $province = $addressdata['0'][$address['province']];
            $city     = $addressdata['0,' . $address['province']][$address['city']];
            if ($city == '市辖区') {
                $city = $province;
            }
            $district = $addressdata['0,' . $address['province'] . ',' . $address['city']][$address['district']];

            $province_id = $this->getArea($province, 1)['id'];
            $city_id     = $this->getArea($city, 2)['id'];
            $area_id     = $this->getArea($district, 3)['id'];

            $user_address                = [];
            $user_address['uid']         = $address['uid'];
            $user_address['province_id'] = $province_id;
            $user_address['city_id']     = $city_id;
            $user_address['area_id']     = $area_id;
            $user_address['address']     = $address['address'];
            $user_address['mobile']      = $address['linkphone'];
            $user_address['name']        = $address['linkman'];
            $user_address['default']     = 2;
            $user_address['create_time'] = time();
            // print_r($user_address);die;
            Db::startTrans();
            try {
                Db::table('pz_user_address')->insert($user_address);

                // 提交事务
                Db::commit();
            } catch (\Exception $e) {
                // 回滚事务

                Db::rollback();
                print_r($e);
                die;
            }
        }
    }

    public function getCommissionShopRel(){
        Db::startTrans();
        try {
           
            $mysql_connect = Db::connect(Config::get('database.db_config'));

            $commission     = "SELECT * FROM pre_commission_order ";
            $commissiondata = $mysql_connect->query($commission);
            foreach($commissiondata as $key => $value){
                //已发放
                if ($value['status'] == 1) {
                    if ( $value['type']== 'boss') {
                        $member_relationship = $mysql_connect->query('SELECT * FROM pre_shop_relshop WHERE `target_uid` = ' . $value['uid']);
                        $target_nickname = $mysql_connect->query('SELECT `nickname` FROM pre_member WHERE `uid` = ' . $value['uid'])[0]['nickname'];
                        if ($member_relationship) {
                            $shop_rel_uid = $member_relationship[0]['uid'];
                            $shop_rel_user = $mysql_connect->query('SELECT `nickname` FROM pre_member WHERE `uid` = ' . $shop_rel_uid);
                            if ($shop_rel_user) {
                                $nickname = $shop_rel_user[0]['nickname'];
                            }
                        }else{
                            $shop_rel_uid = 1;
                            $nickname = '公司总店';
                        }
                        $month =  date('Ym',strtotime($value['datetime']));
                        $shop_real = Db::connect(Config::get('database.db_pzlifelog'))->query('SELECT * FROM pz_commission_relshop where `target_uid` = '.$value['uid']. ' AND timekey = '.$month);
                        if ($shop_real) {
                            $cost = $shop_real[0]['cost'] + bcmul($value['cost'],0.15,2);
                            Db::connect(Config::get('database.db_pzlifelog'))->table('pz_commission_relshop')->where('id', $shop_real[0]['id'])->update(['cost' => $cost]);
                        }else{
                            Db::connect(Config::get('database.db_pzlifelog'))->table('pz_commission_relshop')->insert(
                                [
                                    'target_uid' => $value['uid'],
                                    'target_nickname' => $target_nickname,
                                    'uid' => $shop_rel_uid,
                                    'nickname' => $nickname,
                                    'cost' => bcmul($value['cost'],0.15,2),
                                    'timekey' => $month,
                                    'datetime' => date('Y-m-d H:i:s',time()),
                                ]
                            );
                        }
                    }
                }
            }
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            exception($e);
            Db::rollback();
            
            die;
        }
       
    }

    function getArea($name, $level) {
        $areaSql  = "select * from pz_areas where delete_time=0 and area_name = '" . $name . "' and level =  " . $level;
        $areaInfo = Db::query($areaSql);
        return $areaInfo[0];
    }

    /**
     * @param $uid
     */
    private function getUserInfo($uid) {
        $getUserSql = sprintf("select id,user_type,user_identity,sex,nick_name,balance,commission from pz_users where delete_time=0 and id = %d", $uid);
        $userInfo   = Db::query($getUserSql);
        return $userInfo;
    }

    public function getDiamondvip(){
        $mysql_connect = Db::connect(Config::get('database.db_config'));
        $sql = "SELECT id,user_type,user_identity,sex,nick_name,balance,commission FROM pz_users WHERE user_identity = 2 AND delete_time=0 " ;
        $users = Db::query($sql);
        foreach ($users as $key => $value) {
            $diamondvip_dominos_get = [];
            $get_diamondvip = [];
            $get_diamondvip_sql = " SELECT * FROM pre_diamondvip_get WHERE `uid` = ".$value['id']." LIMIT 1";
            $get_diamondvip = $mysql_connect->query($get_diamondvip_sql);
            $add_diamondvip = [];
           
            if (!empty($get_diamondvip)) {
                // print_r($diamondvip_dominos_get);
                
                if ($get_diamondvip[0]['sdid']) {
                    $get_sql = 'SELECT id FROM pz_diamondvips WHERE `uid`= '.$get_diamondvip[0]['share_uid'];
                    $new_get_diamondvip = Db::query($get_sql);
                   
                    if ($new_get_diamondvip) {
                        $add_diamondvip['diamondvips_id'] = $new_get_diamondvip[0]['id'];
                    }
                }
                $add_diamondvip['uid'] = $get_diamondvip[0]['uid'];
                $add_diamondvip['share_uid'] = $get_diamondvip[0]['share_uid'];
                $add_diamondvip['redmoney'] = $get_diamondvip[0]['coupon_money'];
                $add_diamondvip['redmoney_status'] = 1;
                $add_diamondvip['create_time'] = time();
            }else{
                $diamondvip_dominos_get_sql = " SELECT * FROM pre_diamondvip_dominos_get WHERE `uid` = ".$value['id']." LIMIT 1";
                $diamondvip_dominos_get = $mysql_connect->query($diamondvip_dominos_get_sql);
                if (!empty($diamondvip_dominos_get)) {
                    if ($diamondvip_dominos_get[0]['redmoney_status'] == 1) {
                        $diamondvip_dominos_get['redmoney'] = $diamondvip_dominos_get[0]['redmoney'];
                    }
                    // print_r($diamondvip_dominos_get);die;
                    if ($diamondvip_dominos_get[0]['ddid']) {
                        $get_sql = 'SELECT id FROM pz_diamondvips WHERE `uid`= '.$diamondvip_dominos_get[0]['share_uid'];
                        $new_get_diamondvip = Db::query($get_sql);
                        if ($new_get_diamondvip) {
                            $add_diamondvip['diamondvips_id'] = $new_get_diamondvip[0]['id'];
                        }
                    }
                    $add_diamondvip['uid'] = $diamondvip_dominos_get[0]['uid'];
                    $add_diamondvip['share_uid'] = $diamondvip_dominos_get[0]['share_uid'];
                    $add_diamondvip['redmoney_status'] = 1;
                    $add_diamondvip['create_time'] = time();
                }
            }
           
            if ($add_diamondvip) {
                $new_sql = "SELECT id,share_uid FROM pz_diamondvip_get WHERE `uid` = ".$value['id'];
                $new_diamondvip = Db::query($new_sql);
                // print_r($new_diamondvip);
                if (empty($new_diamondvip)) {
                    Db::startTrans();
                    try {
                        Db::table('pz_diamondvip_get')->insert($add_diamondvip);

                        // 提交事务
                        Db::commit();
                    } catch (\Exception $e) {
                        // 回滚事务

                        Db::rollback();
                        print_r($e);
                        die;
                    }
                }else{ 
                    if ($new_diamondvip[0]['share_uid']>1) {
                        continue;
                    }
                    $updiamondvip = [];
                    
                    // $updiamondvip['uid'] = $diamondvip_dominos_get[0]['uid'];
                    
                    Db::startTrans();
                    try {
                        if (!empty($diamondvip_dominos_get)) {
                            $updiamondvip['share_uid'] = $diamondvip_dominos_get[0]['share_uid'];
                            Db::table('pz_diamondvip_get')->where('uid', $value['id'])->update($updiamondvip);
                        }elseif (!empty($get_diamondvip)) {
                            $updiamondvip['share_uid'] = $get_diamondvip[0]['share_uid'];
                            Db::table('pz_diamondvip_get')->where('uid', $value['id'])->update($updiamondvip);
                        }
                       

                        // 提交事务
                        Db::commit();
                    } catch (\Exception $e) {
                        // 回滚事务

                        Db::rollback();
                        print_r($e);
                        die;
                    }
                }
            }
        }
    }
}