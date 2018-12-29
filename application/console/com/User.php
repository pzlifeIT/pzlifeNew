<?php

namespace app\console\com;

use app\console\Pzlife;
use Config;
use Env;
use function Qiniu\json_decode;
use think\Db;

class User extends Pzlife
{
    /**
     * 数据库连接
     *
     */
    public function db_connect($databasename)
    {
        if ($databasename == 'old') {
            return Db::connect(Config::get('database.db_config'));
        } else {
            return Db::connect(Config::get('database.'));
        }

    }

    /**
     * 用户数据脚本转换
     *
     */
    public function user()
    {
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
        $password = hash_hmac('sha1', '123456', 'userpass');

        $member = "SELECT * FROM pre_member   ";

        $memberdata = $mysql_connect->query($member);
        // print_r($memberdata);die;
        foreach ($memberdata as $key => $value) {
            /* 查出原用户关系 */
            
            $member_relationship = [];
            $member_relationship = $mysql_connect->query('SELECT * FROM pre_member_relationship WHERE `uid` = ' . $value['uid']);
            if(!$member_relationship){
                continue;
            }
           
            
            /* 用户关系数组初始化 */
            $user_relation = [];
            $user_relation['uid'] = $value['uid'];
            $user_relation['pid'] = $member_relationship[0]['supuid'];
            $hierarchy = json_decode($member_relationship[0]['hierarchy']);
            $new_relation = [];
            if ($hierarchy) {
                // $user_relation['my_boss'] = $hierarchy[0];
                if ($mysql_connect->query('SELECT * FROM pre_shop_relationship WHERE `target_uid` = ' . $hierarchy[0])) {
                    /* do { */
                        $relationship = $mysql_connect->query('SELECT * FROM pre_shop_relationship WHERE `target_uid` = ' . $hierarchy[0]);
                        $new_relation[] = $relationship[0]['uid'];
                   /*  } while (!$relationship); */
                }

            } else {
                // $user_relation['my_boss'] = 0;
            }
            $hierarchy[] = $value['uid'];

            /* 用户信息初始化 */
            $new_user = [];
            $new_user['id'] = $value['uid'];

            /* 当用户为BOSS时 */
            if ($value['boss'] == 1) {
                $user_relation['is_boss'] = 1;
                $shop = $mysql_connect->query('SELECT * FROM pre_shop WHERE `uid` = ' . $value['uid']);

                if ($shop) {
                    // $new_user['sex'] = $shop[0]['sex'];
                    // $new_user['idcard'] = $shop[0]['idcard'];
                    // $new_user['mobile'] = $shop[0]['mobile'];
                    $new_user['true_name'] = $shop[0]['linkman'];
                    if ($shop[0]['label'] == 'entrepreneur') {
                        $new_user['user_identity'] = 3;
                    } else {
                        $new_user['user_identity'] = 4;
                    }
                }
                // if ($mysql_connect->query('SELECT * FROM pre_shop_relationship WHERE `target_uid` = ' . $value['uid'])) {
                //     /* do { */
                //         $relationship = $mysql_connect->query('SELECT * FROM pre_shop_relationship WHERE `target_uid` = ' . $value['uid']);

                //         if ($relationship) {
                //             $new_relation[] = $relationship[0]['uid'];
                //         }

                //    /*  } while (!$relationship); */
                // }

            }

            /* 当用户身份为钻石会员时 */
            elseif ($value['label'] == 'diamondvip') {
                $new_user['user_identity'] = 2;
                $new_user['mobile'] = $value['mobile'];
                $diamondvip = $mysql_connect->query('SELECT * FROM pre_diamondvip_get WHERE `uid` = ' . $value['uid'] . ' AND `status`= 1 ORDER BY `gdid` DESC LIMIT 1');
                if ($diamondvip) {
                    $new_user['true_name'] = $diamondvip[0]['linkman'];
                }

            }
            /* 普通用户信息 */
            else {
                $new_user['user_identity'] = 1;
            }

            /* 新用户信息 */
            $new_user['passwd'] = $password;
            $new_user['user_type'] = 1;
            $new_user['nick_name'] = $value['nickname'];
            if($value['avatar']){
                $new_user['avatar'] = $value['avatar'];
            }
            
            $new_user['openid'] = trim($member_relationship[0]['wx_openid']);
            $new_user['bindshop'] = $value['bingshopid'];
            $new_user['commission_freeze'] = 2;

            /* 查询用户积分数据 */
            $member_count = $mysql_connect->query('SELECT * FROM pre_member_count WHERE `uid` = ' . $value['uid']);

            if ($member_count) {
                $new_user['balance'] = $member_count[0]['redmoney'];
                $new_user['commission'] = $member_count[0]['commission'];
                $new_user['integral'] = $member_count[0]['bonuspoints'];
            }

            $user_relation['relation'] = join(',', $hierarchy);
            if ($new_relation) {
                $user_relation['relation'] = join(',', $new_relation) . ',' . $user_relation['relation'];
            }
            $new_user = $this->delDataEmptyKey($new_user);
            $user_relation = $this->delDataEmptyKey($user_relation);
            // print_r( $member_relationship );
            // print_r( $user_relation );
    
           // 启动事务
            Db::startTrans();
            try {
                Db::table('pz_users')->insert($new_user);
                Db::table('pz_user_relation')->insert($user_relation);
                // 提交事务
                Db::commit();
            } catch (\Exception $e) {
                // 回滚事务
                print_r($e);die;
                Db::rollback();
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
    public function goods()
    {
        ini_set('memory_limit', '1024M');
        $mysql_connect = Db::connect(Config::get('database.db_config'));

        /* 查询供应商 */
        $suppliersql = "SELECT * FROM pre_supplier WHERE `supid`>8 ";
        $supplierdata = $mysql_connect->query($suppliersql);
        Db::startTrans();
            try {
              
                foreach ($supplierdata as $key => $value) {
                    // print_r($value);
                    $supplier = [];
                    $supplier['id'] = $value['supid'];
                    $supplier['tel'] = $value['service'];
                    $supplier['name'] = $value['name'];
                    $supplier['image'] = $value['image'];
                    $supplier['title'] = trim($value['description']);
                    $supplier['desc'] = $value['expresstxt'];
                    $supplier = $this->delDataEmptyKey($supplier);
                    // print_r($supplier);
                    Db::table('pz_supplier')->insert($supplier);
                    
                    /* 查询供应商商品 */
                    $goodsSql = "SELECT * FROM pre_commodity WHERE `supid` ='{$value['supid']}'  ";
                    $goodsdata = $mysql_connect->query($goodsSql);
                    
                    foreach ($goodsdata as $goods => $data) {
                        $goods = [];
                        $goods['id'] = $data['comid'];
                        $goods['supplier_id'] = $data['supid'];
                        $goods['goods_name'] = $data['title'];
                        $goods['goods_type'] = 1;
                        $goods['title'] = $data['title'];
                        $goods['subtitle'] = $data['subtitle'];
                        $goods['image'] = $data['image'];
                        $goods['status'] = 0;

                        $goods = $this->delDataEmptyKey($goods);
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
                        $goods_banner = $mysql_connect->query("SELECT * FROM pre_commodity_image WHERE `comid`=".$data['comid']);

                        foreach ($goods_banner as $gb => $banner) {
                            $new_goodsbanner = [];
                            $new_goodsbanner['goods_id'] = $banner['comid'];
                            $new_goodsbanner['source_type'] = 4;
                            $new_goodsbanner['image_type'] = 2;
                            $new_goodsbanner['image_path'] = $banner['image'];
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
            print_r($e);
            Db::rollback();
    }

    }

    /**
     * 去除数组中空值的键值对
     *
     */
    public function delDataEmptyKey($data)
    {
        foreach ($data as $key => $value) {
            if(!$value){
                unset($data[$key]);
            }
        }
        return $data;
    }

}
