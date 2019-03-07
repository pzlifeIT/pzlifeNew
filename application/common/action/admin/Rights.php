<?php
namespace app\common\action\admin;

use app\facade\DbUser;
use app\facade\DbShops;
use app\facade\DbRights;
use think\Db;

class Rights{
    /**
     * 创建分享钻石会员机会
     * @param $mobile
     * @param $linkman
     * @param $stock
     * @param $redmoney_status
     * @param $type
     * @param $coupon_money
     * @return array
     * @author rzc
     */
    public function creatBossShareDiamondvip($mobile,$linkman,$stock,$redmoney_status,$type,$coupon_money){
        
        $user = DbUser::getUserOne(['mobile'=>$mobile,'user_identity'=>4], 'id');
        if (!$user) {
            return ['code' => '3003','msg' => '该用户不存在'];
        }
        $shop = DbShops::getShopInfo('id', ['uid'=>$user['id']]);
        if (!$shop) {
            return ['code' => '3004','msg' => '非BOSS无法添加机会'];
        }
        if ($type == 2) {
            if ($stock>500){
                return ['code' => 3005,'msg' => '绑定二维码链接设置最大库存为每次500名额'];
            }
        }
        
        $data = [];
        $data = [
            'uid'=> $user['id'],
            'shopid' => $shop['id'],
            'linkman' => $linkman,
            'stock' => $stock,
            'redmoney_status' => $redmoney_status,
            'type' => $type,
            'coupon_money' => $coupon_money,
        ];
        $result = DbRights::creatDiamondvip($data);
        if ($result) {
            return ['code' => '200','msg' => '创建成功'];
        }else{
            return ['code' => '3006','msg' => '创建失败'];
        }
    }

    /**
     * 列表查询分享钻石会员机会
     * @param $page
     * @param $pagenum
     * @return array
     * @author rzc
     */
    public function getBossShareDiamondvip($page,$pagenum){
        $offect = ($page-1)* $pagenum;
        
        $result = DbRights::getDiamondvips([['1','=','1']],'*',false,'id','desc',$offect.','.$pagenum);
        $totle = DbRights::getCountDiamondvip();
        if (empty($result)) {
            return ['code' => 3000];
        }
        return ['code' => 200 ,'totle' => $totle,'data' => $result];
    }

    /**
     * 审核分享钻石会员机会
     * @param $id
     * @return array
     * @author rzc
     */
    public function passBossShareDiamondvip($id,$status){
        $diamondvips = DbRights::getDiamondvips([['id','=',$id]],'*',true);
        if (!$diamondvips) {
            return ['code' => 3000];
        }
        if ($diamondvips['status'] != 0) {
            return ['code' => 3002,'msg' => '已审核过的申请无法再次申请'];
        }
        if ($status == $diamondvips['status']) {
            return ['code' => '3003','msg' => '无法再次进行当前状态审核'];
        }
        Db::startTrans();
            try {
                if ($status == 1) {
                    if ($diamondvips['type'] == 2) {
                        $binding = [];
                        $binding['diamondvips_id'] = $id;
                        $binding['coupon_money'] = $diamondvips['coupon_money'];
                        $binding['status'] = 1;
                        $i = 0;
                        do {
                            $code = hash_hmac('sha1', $i . 'diamondvip' . $id . 'TIME' . time(), 'diamondvipbinding');
                            $binding['code'] = $code;
                            DbRights::creatDiamondvipBinding($binding);
                            $i++;
                        } while ($i < $diamondvips['stock']);
                    }
                    DbRights::updateDiamondvip(['status'=>1],$id);
                }elseif ($status == 2){
                    DbRights::updateDiamondvip(['status'=>2],$id);
                }
                // 提交事务
                Db::commit();
                return ['code' => '200','msg' => '审核通过'];
            } catch (\Exception $e) {
                // 回滚事务
                
                Db::rollback();
                return ['code' => '3004','msg' => '插入数据出错'];
            }
    }
}