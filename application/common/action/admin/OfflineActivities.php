<?php

namespace app\common\action\admin;

use app\facade\DbGoods;
use app\facade\DbImage;
use app\facade\DbOfflineActivities;
use app\facade\DbUser;
use Config;
use function Qiniu\json_decode;
use think\Db;

class OfflineActivities extends CommonIndex {
    /**
     * 线下活动列表
     * @param $page
     * @param $pagenum
     * @return array
     * @author rzc
     */
    public function getOfflineActivities($page, $pagenum, $id = 0) {
        $page    = $page ? $page : 1;
        $pagenum = $pagenum ? $pagenum : 10;

        if (!is_numeric($page) || !is_numeric($pagenum)) {
            return ['code' => '3002'];
        }
        $offset = $pagenum * ($page - 1);
        if ($offset < 0) {
            return ['code' => '3000'];
        }
        if ($id) {
            $result = DbOfflineActivities::getOfflineActivities(['id' => $id], '*', true);
            return ['code' => '200', 'result' => $result];
        } else {
            $result = DbOfflineActivities::getOfflineActivities([], '*', false, ['id' => 'desc'], $offset . ',' . $pagenum);
        }

        if (empty($result)) {
            return ['code' => 3000];
        }
        $total = DbOfflineActivities::countOfflineActivities([]);
        return ['code' => '200', 'total' => $total, 'result' => $result];
    }

    /**
     * 新建线下活动
     * @param $title
     * @param $image_path
     * @param $start_time
     * @param $stop_time
     * @return array
     * @author rzc
     */
    public function addOfflineActivities($title, $image_path, $start_time, $stop_time) {
        $data               = [];
        $data['title']      = $title;
        $data['start_time'] = $start_time;
        $data['stop_time']  = $stop_time;
        Db::startTrans();
        try {
            $image    = filtraImage(Config::get('qiniu.domain'), $image_path);
            $logImage = DbImage::getLogImage($image, 2); //判断时候有未完成的图片
            if (empty($logImage)) { //图片不存在
                return ['code' => '3010']; //图片没有上传过
            }
            DbImage::updateLogImageStatus($logImage, 1); //更新状态为已完成
            $data['image_path'] = $image;

            $add = DbOfflineActivities::addOfflineActivities($data);

            if ($add) {
                Db::commit();
                Db::startTrans();
                try {
                    $Upload = new Upload;
                    $result = $this->createQrcode('pages/events/events', $add);

                    if (strlen($result) > 100) {
                        $file = fopen(Config::get('conf.image_path') . 'offlineactivities' . date('Ymd') . $add . '.png', "w"); //打开文件准备写入
                        fwrite($file, $result); //写入
                        fclose($file); //关闭
                        $upload = $Upload->uploadUserImage('offlineactivities' . date('Ymd') . $add . '.png');

                        if ($upload['code'] == 200) {
                            $logImage = DbImage::getLogImage($upload, 2); //判断时候有未完成的图片
                            if ($logImage) { //图片不存在
                                $save = DbOfflineActivities::updateOfflineActivitiesGoods(['qrcode_path' => $logImage], $add);
                                if ($save) {
                                    DbImage::updateLogImageStatus($logImage, 1); //更新状态为已完成
                                    Db::commit();
                                }
                            }

                        }
                    }
                } catch (\Exception $e) {
                    Db::rollback();

                }

                return ['code' => '200', 'add_id' => $add];
            }
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3011']; //添加失败
        }
    }

    /**
     * 修改线下活动
     * @param $title
     * @param $image_path
     * @param $start_time
     * @param $stop_time
     * @param $id
     * @return array
     * @author rzc
     */
    public function updateOfflineActivities($title = '', $image_path = '', $start_time = 0, $stop_time = 0, $id) {
        $result = DbOfflineActivities::getOfflineActivities(['id' => $id], '*', true);
        if (empty($result)) {
            return ['code' => 3000];
        }
        if ($start_time && !$stop_time) {
            if ($start_time > strtotime($result['stop_time'])) {
                return ['code' => '3003'];
            }
        }
        if ($stop_time && !$start_time) {
            if ($stop_time < strtotime($result['start_time'])) {
                return ['code' => '3003'];
            }
        }
        if ($stop_time < $start_time) {
            return ['code' => '3003'];
        }
        $data = [];
        if ($title) {
            array_push($data, ['title' => $title]);
        }
        if ($image_path) {
            array_push($data, ['image_path' => $image_path]);
        }
        if ($stop_time) {
            array_push($data, ['stop_time' => $stop_time]);
        }
        if ($start_time) {
            array_push($data, ['start_time' => $start_time]);
        }
        Db::startTrans();
        try {

            if (!empty($data['image_path'])) {
                $oldImage = $result['image_path'];

                $oldImage = filtraImage(Config::get('qiniu.domain'), $oldImage);

                if (!empty($oldImage)) {

                    $oldImage_id = DbImage::getLogImage($oldImage, 1);
                    DbImage::updateLogImageStatus($oldImage_id, 3); //更新状态为弃用

                }
                $image = filtraImage(Config::get('qiniu.domain'), $data['image_path']);

                $logImage = DbImage::getLogImage($image, 2); //判断时候有未完成的图片

                if (empty($logImage)) { //图片不存在
                    return ['code' => '3010']; //图片没有上传过
                }
                DbImage::updateLogImageStatus($logImage, 1); //更新状态为已完成
                $data['image_path'] = $image;
            }
            DbOfflineActivities::updateOfflineActivities($data, $id);

            Db::commit();
            return ['code' => '200'];

        } catch (\Exception $e) {
            // exception($e);
            Db::rollback();
            return ['code' => '3011']; //添加失败
        }
    }

    /**
     * 获取线下活动商品
     * @param $page
     * @param $pagenum
     * @param $active_id
     * @return array
     * @author rzc
     */
    public function getOfflineActivitiesGoods($page, $pagenum, $active_id, $id) {
        $offset = $pagenum * ($page - 1);
        if ($offset < 0) {
            return ['code' => '3000'];
        }
        if ($id) {
            $result = DbOfflineActivities::getOfflineActivitiesGoods(['id' => $id], '*', true);
            return ['code' => '200', 'result' => $result];
        } else {
            $result = DbOfflineActivities::getOfflineActivitiesGoods(['active_id' => $active_id], '*', false, ['id' => 'desc'], $offset . ',' . $pagenum);
        }

        if (empty($result)) {
            return ['code' => '3000'];
        }
        foreach ($result as $key => $value) {
            $result[$key]['goods'] = $this->getGoods($value['goods_id']);
        }
        $total = DbOfflineActivities::countOfflineActivitiesGoods(['active_id' => $active_id]);
        return ['code' => '200', 'total' => $total, 'result' => $result];
    }

    function getGoods($goodsid) {
        /* 返回商品基本信息 （从商品库中直接查询）*/
        $where      = [["id", "=", $goodsid], ["status", "=", 1]];
        $field      = "id,supplier_id,cate_id,goods_name,goods_type,title,subtitle,image,status";
        $goods_data = DbGoods::getOneGoods($where, $field);
        if (empty($goods_data)) {
            return [];
        }
        list($goods_spec, $goods_sku) = $this->getGoodsSku($goodsid);
        if ($goods_sku) {
            foreach ($goods_sku as $goods => $sku) {

                $retail_price[$sku['id']]    = $sku['retail_price'];
                $brokerage[$sku['id']]       = $sku['brokerage'];
                $integral_active[$sku['id']] = $sku['integral_active'];
            }
            $goods_data['retail_price']        = min($retail_price);
            $goods_data['min_brokerage']       = $brokerage[array_search(min($retail_price), $retail_price)];
            $goods_data['min_integral_active'] = $integral_active[array_search(min($retail_price), $retail_price)];
        } else {
            $goods_data['min_brokerage']       = 0;
            $goods_data['min_integral_active'] = 0;
            $goods_data['retail_price']        = 0;
        }
        return $goods_data;
    }

    /**
     * 获取商品SKU及规格名称等
     * @param $goods_id
     * @param $source
     * @return array
     * @author rzc
     */
    public function getGoodsSku($goods_id) {
        $field            = 'goods_id,spec_id';
        $where            = [["goods_id", "=", $goods_id]];
        $goods_first_spec = DbGoods::getOneGoodsSpec($where, $field, 1);
        $goods_spec       = [];
        if ($goods_first_spec) {
            $field = 'id,spe_name';
            foreach ($goods_first_spec as $key => $value) {
                $where  = ['id' => $value['spec_id']];
                $result = DbGoods::getOneSpec($where, $field);

                $goods_attr_field = 'attr_id';
                $goods_attr_where = ['goods_id' => $goods_id, 'spec_id' => $value['spec_id']];
                $goods_first_attr = DbGoods::getOneGoodsSpec($goods_attr_where, $goods_attr_field);
                $attr_where       = [];
                foreach ($goods_first_attr as $goods => $attr) {
                    $attr_where[] = $attr['attr_id'];
                }

                $attr_field = 'id,spec_id,attr_name';
                $attr_where = [['id', 'in', $attr_where], ['spec_id', '=', $value['spec_id']]];

                $result['list'] = DbGoods::getAttrList($attr_where, $attr_field);

                $goods_spec[] = $result;
            }

        }

        $field = 'id,goods_id,stock,market_price,retail_price,presell_start_time,presell_end_time,presell_price,active_price,active_start_time,active_end_time,margin_price,cost_price,integral_price,spec,sku_image';
        // $where = [["goods_id", "=", $goods_id],["status", "=",1],['retail_price','<>', 0]];
        $where     = [["goods_id", "=", $goods_id], ["status", "=", 1]];
        $goods_sku = DbGoods::getOneGoodsSku($where, $field);
        /* brokerage：佣金；计算公式：(商品售价-商品进价-其它运费成本)*0.9*(钻石返利：0.75) */
        /* integral_active：积分；计算公式：(商品售价-商品进价-其它运费成本)*2 */
        foreach ($goods_sku as $goods => $sku) {
            $goods_sku[$goods]['brokerage']       = bcmul(getDistrProfits($sku['retail_price'], $sku['cost_price'], $sku['margin_price']), 0.75, 2);
            $goods_sku[$goods]['integral_active'] = bcmul(bcsub(bcsub($sku['retail_price'], $sku['cost_price'], 4), $sku['margin_price'], 2), 2, 0);
            $sku_json                             = DbGoods::getAttrList([['id', 'in', $sku['spec']]], 'attr_name');
            $sku_name                             = [];
            if ($sku_json) {
                foreach ($sku_json as $sj => $json) {
                    $sku_name[] = $json['attr_name'];
                }
            }
            $goods_sku[$goods]['sku_name'] = $sku_name;

        }
        return [$goods_spec, $goods_sku];
    }

    public function addOfflineActivitiesGoods($active_id, $goods_id) {
        $offlineactivities = DbOfflineActivities::getOfflineActivities(['id' => $active_id], '*', true);
        if (empty($offlineactivities)) {
            return ['code' => '3000'];
        }
        if (strtotime($offlineactivities['stop_time']) < time()) {
            return ['code' => '3001'];
        }
        $goods = DbGoods::getOneGoods([["id", "=", $goods_id], ["status", "=", 1]], 'id');
        if (empty($goods)) {
            return ['code' => '3002'];
        }
        if (DbOfflineActivities::getOfflineActivitiesGoods(['active_id' => $active_id, 'goods_id' => $goods_id], 'id')) {
            return ['code' => '3003'];
        }
        $data              = [];
        $data['active_id'] = $active_id;
        $data['goods_id']  = $goods_id;
        DbOfflineActivities::addOfflineActivitiesGoods($data);
        return ['code' => '200'];
    }

    public function updateOfflineActivitiesGoods($active_id, $goods_id, $id) {
        $offlineactivities = DbOfflineActivities::getOfflineActivities(['id' => $active_id], '*', true);
        if (empty($offlineactivities)) {
            return ['code' => '3000'];
        }
        if (strtotime($offlineactivities['stop_time']) < time()) {
            return ['code' => '3001'];
        }
        $goods = DbGoods::getOneGoods([["id", "=", $goods_id], ["status", "=", 1]], 'id');
        if (empty($goods)) {
            return ['code' => '3002'];
        }
        if (DbOfflineActivities::getOfflineActivitiesGoods([['active_id', '=', $active_id], ['goods_id', '=', $goods_id], ['id', '<>', $id]], 'id')) {
            return ['code' => '3003'];
        }
        $data              = [];
        $data['active_id'] = $active_id;
        $data['goods_id']  = $goods_id;
        DbOfflineActivities::updateOfflineActivitiesGoods($data, $id);
        return ['code' => '200'];
    }

    public function resetOfflineActivitiesQrcode($id, $uid) {
        $Qrcode = DbOfflineActivities::getOfflineActivities(['id' => $id], 'qrcode_path', true);
        if (empty($Qrcode)) {
            return ['code' => '3000'];
        }
        if (empty($Qrcode['qrcode_path'])) { //重新生成
            if ($uid) {
                $user = DbUser::getUserOne(['id' => $uid], 'id,passwd');
                if (empty($user)) {
                    return ['code' => '30012'];
                }
                $uid   = enUid($uid);
                $scene = 'id=' . $id . '&pid=' . $uid;
            }
           
            $Upload = new Upload;
            $result = $this->createQrcode('pages/events/events', $scene);
            if (strlen($result) > 100) {
                $file = fopen(Config::get('conf.image_path') . 'offlineactivities' . date('Ymd') . $scene . '.png', "w"); //打开文件准备写入
                fwrite($file, $result); //写入
                fclose($file); //关闭
                $upload = $Upload->uploadUserImage('offlineactivities' . date('Ymd') . $scene . '.png');
                if ($upload['code'] == 200) {
                    $logImage = DbImage::getLogImage($upload, 2); //判断时候有未完成的图片
                    // print_r($logImage);die;
                    if (empty($logImage)) { //图片不存在
                        return ['code' => '3010']; //图片没有上传过
                    }
                    
                    Db::startTrans();
                    try {
                        $save = DbOfflineActivities::updateOfflineActivities(['qrcode_path' => $upload['image_path']], $id);
                        // print_r($save);die;
                        if (!$save) {
                            return ['code' => '3011'];
                        }
                        DbImage::updateLogImageStatus($logImage, 1); //更新状态为已完成
                        Db::commit();
                        $new_Qrcode = Config::get('qiniu.domain') . '/' . $upload['image_path'];
                        return ['code' => '200', 'Qrcode' => $new_Qrcode];
                    } catch (\Exception $e) {
                        print_r($e);
                        Db::rollback();
                        return ['code' => '3011']; //添加失败
                    }
                } else {
                    return ['code' => 3011];
                }
            } else {
                return ['code' => 3009, 'error_data' => json_decode($result, true)];
            }
        } else {
            return ['code' => '200', 'Qrcode' => Config::get('qiniu.domain') . '/'.$Qrcode['qrcode_path']];
        }
    }

    public function createQrcode($page, $scene) {
        $appid = Config::get('conf.weixin_miniprogram_appid');
        // $appid         = 'wx1771b2e93c87e22c';
        $secret = Config::get('conf.weixin_miniprogram_appsecret');
        // $secret        = '1566dc764f46b71b33085ba098f58317';
        $requestUrl = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=' . $appid . '&secret=' . $secret;
        if (!$requestUrl) {
            return ['code' => '3004'];
        }
        $requsest_subject = json_decode(sendRequest($requestUrl), true);
        $access_token     = $requsest_subject['access_token'];
        if (!$access_token) {
            return ['code' => '3005'];
        }
        $requestUrl = 'https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=' . $access_token;
        // print_r($link);die;
        $result = $this->sendRequest2($requestUrl, ['scene' => $scene, 'page' => $page]);
        return $result;

    }

    function sendRequest2($requestUrl, $data = []) {
        $curl = curl_init();
        $data = json_encode($data);
        curl_setopt($curl, CURLOPT_URL, $requestUrl);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json; charset=utf-8', 'Content-Length:' . strlen($data)]);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $res = curl_exec($curl);
        curl_close($curl);
        return $res;
    }
}