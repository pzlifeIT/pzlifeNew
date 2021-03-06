<?php

namespace app\index\controller;

use app\index\MyController;

class Rights extends MyController {
    protected $beforeActionList = [
        // 'isLogin',//所有方法的前置操作
        'isLogin' => ['except' => 'IsGetDominos'], //除去getFirstCate其他方法都进行second前置操作
        //        'three'  => ['only' => 'hello,data'],//只有hello,data方法进行three前置操作
    ];

    /**
     * @api              {post} / 领取钻石会员（非二维码绑定）
     * @apiDescription   receiveDiamondvip
     * @apiGroup         index_rights
     * @apiName          receiveDiamondvip
     * @apiParam (入参) {String} con_id 用户登录con_id
     * @apiParam (入参) {String} parent_id 分享者id
     * @apiSuccess (返回) {String} code 200:成功 3000:没有该用户 / 3001:con_id长度不够32位 / 3002:con_id为空 / 3003:UID为空 / 3004:当前身份等级大于或等于钻石会员，无法领取 / 3005:分享用户没有分享机会 / 3006:该机会已领完
     * @apiSuccess (data) {String} address 用户添加的收货地址
     * @apiSampleRequest /index/rights/receiveDiamondvip
     * @return array
     * @author rzc
     */
    public function receiveDiamondvip() {
        $apiName   = classBasename($this) . '/' . __function__;
        $conId     = trim($this->request->post('con_id'));
        $parent_id = $this->request->post('parent_id');
        $parent_id = deUid($parent_id);
        if (empty($conId)) {
            return ['code' => '3002'];
        }
        if (strlen($conId) != 32) {
            return ['code' => '3001'];
        }
        $result = $this->app->rights->receiveDiamondvip($conId, $parent_id);
        $this->apiLog($apiName, [$conId, $parent_id], $result['code'], $conId);
        return $result;
    }

    /**
     * @api              {post} / 判断会员是否有分享钻石接龙的的资格
     * @apiDescription   IsGetDominos
     * @apiGroup         index_rights
     * @apiName          IsGetDominos
     * @apiParam (入参) {String} parent_id 分享者id
     * @apiSuccess (返回) {String} code 200:成功 3000:没有该用户 / 3001:parent_id长度只能是32位 / 3002:传入用户为空  / 3004:非BOSS无法开启分享钻石接龙资格（200名额）/ 3005:分享用户没有分享机会
     * @apiSuccess (data) {String} code
     * @apiSampleRequest /index/rights/IsGetDominos
     * @return array
     * @author rzc
     */
    public function IsGetDominos() {
        $apiName   = classBasename($this) . '/' . __function__;
        $parent_id = $this->request->post('parent_id');
        if (strlen($parent_id) < 1) {
            return ['code' => '3001'];
        }
        $parent_id = deUid($parent_id);
        if (empty($parent_id)) {
            return ['code' => '3002'];
        }
        $result = $this->app->rights->IsGetDominos($parent_id);
        $this->apiLog($apiName, [$parent_id], $result['code'], '');
        return $result;
    }

    /**
     * @api              {post} / 判断登录会员钻石接龙的的名额是否用完
     * @apiDescription   IsBossDominos
     * @apiGroup         index_rights
     * @apiName          IsBossDominos
     * @apiParam (入参) {String} con_id 分享者id
     * @apiSuccess (返回) {String} code 200:成功 3000:没有该用户 / 3001:con_id长度只能是32位 / 3002:缺少参数 / 3003:用户为空 / 3004:非BOSS无法开启分享钻石接龙资格（200名额） / 3005:分享用户没有分享机会
     * @apiSuccess (data) {String} code
     * @apiSampleRequest /index/rights/IsBossDominos
     * @return array
     * @author rzc
     */
    public function IsBossDominos() {
        $apiName = classBasename($this) . '/' . __function__;
        $conId   = trim($this->request->post('con_id'));
        if (empty($conId)) {
            return ['code' => '3002'];
        }
        if (strlen($conId) != 32) {
            return ['code' => '3001'];
        }
        $result = $this->app->rights->IsBossDominos($conId);
        $this->apiLog($apiName, [$conId], $result['code'], $conId);
        return $result;
    }

    /**
     * @api              {post} / 获取用户红包提示
     * @apiDescription   getDominosBalanceHint
     * @apiGroup         index_rights
     * @apiName          getDominosBalanceHint
     * @apiParam (入参) {String} con_id 用户con_id
     * @apiSuccess (返回) {String} code 200:成功 3000:没有到账红包 / 3001:con_id长度只能是32位 / 3002:缺少参数 / 3003:用户为空 / 3004:非BOSS无法开启分享钻石接龙资格（200名额） / 3005:分享用户没有分享机会
     * @apiSuccess (data) {String} address 用户添加的收货地址
     * @apiSampleRequest /index/rights/getDominosBalanceHint
     * @return array
     * @author rzc
     */
    public function getDominosBalanceHint() {
        $apiName = classBasename($this) . '/' . __function__;
        $conId   = trim($this->request->post('con_id'));
        if (empty($conId)) {
            return ['code' => '3002'];
        }
        if (strlen($conId) != 32) {
            return ['code' => '3001'];
        }
        $result = $this->app->rights->getDominosBalanceHint($conId);
        $this->apiLog($apiName, [$conId], $result['code'], $conId);
        return $result;
    }

    /**
     * @api              {post} / 获取用户钻石会员领取机会记录
     * @apiDescription   getDominosChance
     * @apiGroup         index_rights
     * @apiName          getDominosChance
     * @apiParam (入参) {String} con_id 用户con_id
     * @apiSuccess (返回) {String} code 200:成功 3000:没有到账红包 / 3001:con_id长度只能是32位 / 3002:缺少参数 / 3003:用户为空 / 3004:非BOSS无法开启分享钻石接龙资格（200名额） / 3005:分享用户没有分享机会
     * @apiSuccess (Diamondvips) {String} id 主键
     * @apiSuccess (Diamondvips) {String} uid 用户UID
     * @apiSuccess (Diamondvips) {String} shopid 商店ID
     * @apiSuccess (Diamondvips) {String} linkman boss姓名
     * @apiSuccess (Diamondvips) {String} mobile 手机号
     * @apiSuccess (Diamondvips) {String} stock 库存
     * @apiSuccess (Diamondvips) {String} num 已领取数量
     * @apiSuccess (DiamondvipDominos) {String} DiamondvipDominos 购买100元数量
     * @apiSampleRequest /index/rights/getDominosChance
     * @return array
     * @author rzc
     */
    public function getDominosChance() {
        $apiName = classBasename($this) . '/' . __function__;
        $conId   = trim($this->request->post('con_id'));
        if (empty($conId)) {
            return ['code' => '3002'];
        }
        if (strlen($conId) != 32) {
            return ['code' => '3001'];
        }
        $result = $this->app->rights->getDominosChance($conId);
        $this->apiLog($apiName, [$conId], $result['code'], $conId);
        return $result;
    }

    /**
     * @api              {post} / 获取用户钻石会员领取详情
     * @apiDescription   getDominosReceive
     * @apiGroup         index_rights
     * @apiName          getDominosReceive
     * @apiParam (入参) {String} con_id 用户con_id
     * @apiParam (入参) {Number} diamondvips_id diamondvips_id（不传则查购买100元接龙信息）
     * @apiSuccess (返回) {String} code 200:成功 3000:没有到账红包 / 3001:con_id长度只能是32位 / 3002:缺少参数 / 3003:diamondvips_id必须为数字 / 3004:普通会员无法查看 / 3005:分享用户没有分享机会
     * @apiSuccess (data) {String} uid 用户id
     * @apiSuccess (data) {String} nick_name 用户昵称
     * @apiSuccess (data) {String} avatar 用户头像
     * @apiSampleRequest /index/rights/getDominosReceive
     * @return array
     * @author rzc
     */
    public function getDominosReceive() {
        $apiName        = classBasename($this) . '/' . __function__;
        $conId          = trim($this->request->post('con_id'));
        $diamondvips_id = trim($this->request->post('diamondvips_id'));
        if (empty($conId)) {
            return ['code' => '3002'];
        }
        if (strlen($conId) != 32) {
            return ['code' => '3001'];
        }
        if ($diamondvips_id) {
            if (!is_numeric($diamondvips_id)) {
                return ['code' => '3003'];
            }
        }
        $result = $this->app->rights->getDominosReceive($conId, $diamondvips_id);
        $this->apiLog($apiName, [$conId], $result['code'], $conId);
        return $result;
    }

    /**
     * @api              {post} / Boss邀请会员成为Boss
     * @apiDescription   shopApplyBoss
     * @apiGroup         index_rights
     * @apiName          shopApplyBoss
     * @apiParam (入参) {String} con_id 用户con_id
     * @apiParam (入参) {String} target_nickname 被邀请人姓名
     * @apiParam (入参) {Number} target_sex 被邀请人性别 1.男2.女
     * @apiParam (入参) {String} target_mobile 被邀请人手机号
     * @apiParam (入参) {String} target_idcard 被邀请人身份证号
     * @apiParam (入参) {Number} refe_type 被邀请成为店主类型1.创业店主2.boss合伙人
     * @apiParam (入参) {String} parent_id 邀请人id
     * @apiSuccess (返回) {String} code 200:成功 3000:用户为空 / 3001:con_id长度只能是32位 / 3002:缺少参数con_id / 3003:缺少参数parent_id / 3004:target_sex必须为数字 / 3005:target_nickname为空 / 3006:手机号校验失败 / 3007:身份证号码校验失败 / 3008:refe_type必须为数字 / 3009:已有在审核进度中或者审核通过的申请记录，无法再次申请 / 3010:已成为BOSS 无法再次申请 / 3011:此记录已存在 / 3012:邀请上级不是BOSS / 3013:boss正在申请中
     * @apiSuccess (data) {String} uid 用户id
     * @apiSuccess (data) {String} nick_name 用户昵称
     * @apiSuccess (data) {String} avatar 用户头像
     * @apiSampleRequest /index/rights/shopApplyBoss
     * @return array
     * @author rzc
     */
    public function shopApplyBoss() {
        $apiName         = classBasename($this) . '/' . __function__;
        $conId           = trim($this->request->post('con_id'));
        $target_nickname = $this->request->post('target_nickname');
        $target_sex      = $this->request->post('target_sex');
        $target_mobile   = $this->request->post('target_mobile');
        $target_idcard   = $this->request->post('target_idcard');
        $refe_type       = $this->request->post('refe_type');
        $parent_id       = $this->request->post('parent_id');
        $parent_id       = deUid($parent_id);
        $target_sex      = $target_sex ? 1 : 2;
        $refe_type       = $refe_type ? 1 : 2;
        if (empty($conId)) {
            return ['code' => '3002'];
        }
        if (strlen($conId) != 32) {
            return ['code' => '3001'];
        }
        if (empty($parent_id)) {
            return ['code' => '3003'];
            // $parent_id = 1;
        }
        // else{
        //     $parent_id = $parent_id;
        // }
        if (!is_numeric($target_sex)) {
            return ['code' => '3004'];
        }
        if (empty($target_nickname)) {
            return ['code' => '3005'];
        }
        if (checkMobile($target_mobile) == false) {
            return ['code' => '3006'];
        }
        if (checkIdcard($target_idcard) == false) {
            return ['code' => '3007'];
        }
        if (!is_numeric($refe_type)) {
            return ['code' => '3008'];
        }
        $result = $this->app->rights->shopApplyBoss($conId, $target_nickname, $target_sex, $target_mobile, $target_idcard, $refe_type, $parent_id);
        $this->apiLog($apiName, [$conId, $target_nickname, $target_sex, $target_mobile, $target_idcard, $refe_type, $parent_id], $result['code'], $conId);
        return $result;
    }

    /**
     * @api              {post} / 用户升级
     * @apiDescription   userUpgrade
     * @apiGroup         index_rights
     * @apiName          userUpgrade
     * @apiParam (入参) {String} con_id 用户con_id
     * @apiParam (入参) {Number} refe_type 升级类型1.创业店主2.兼职市场经理 3 兼职市场总监
     * @apiParam (入参) {String} parent_id 邀请人id 空视为自己升级，传值视为邀请升级
     * @apiSuccess (返回) {String} code 200:成功 / 3001:邀请类型错误 / 3002:只有钻石才能升级为创业店主 /3003:只有创业店主2才能升级为兼职市场经理 / 3004:只有合伙人才能升级为兼职市场总监 / 3005:升级失败 / 3006:已经是兼职市场经理，无法再次升级 / 3007:正在冷却期内无法升级 / 3008:已经是总监身份无需再次升级
     * @apiSuccess (data) {String} uid 用户id
     * @apiSampleRequest /index/rights/userUpgrade
     * @return array
     * @author rzc
     */
    public function userUpgrade() {
        $refe_type  = trim($this->request->post('refe_type'));
        $parent_id  = trim($this->request->post('parent_id'));
        $apiName    = classBasename($this) . '/' . __function__;
        $conId      = trim($this->request->post('con_id'));
        $refe_types = [1, 2, 3];
        if (!in_array($refe_type, $refe_types)) {
            return ['code' => '3001'];
        }
        $result = $this->app->rights->userUpgrade($conId, $refe_type, $parent_id);
        $this->apiLog($apiName, [$conId, $refe_type, $parent_id], $result['code'], $conId);
        return $result;
    }

    /**
     * @api              {post} / 用户任务列表
     * @apiDescription   userTask
     * @apiGroup         index_rights
     * @apiName          userTask
     * @apiParam (入参) {String} con_id 用户con_id
     * @apiParam (入参) {String} [taskid] 任务ID，传值视为查询单条详情
     * @apiParam (入参) {Number} page 页码
     * @apiParam (入参) {String} page_num 查询记录条数
     * @apiSuccess (返回) {String} code 200:成功 / 3000:用户不存在 / 3001:page错误 /
     * @apiSuccess (返回) {String} had_bonus 已结算奖金
     * @apiSuccess (返回) {String} no_bonus 未结算奖金
     * @apiSuccess (返回) {Array} usertask 任务记录
     * @apiSuccess (usertask) {String} id 任务id
     * @apiSuccess (usertask) {String} title 任务名称
     * @apiSuccess (usertask) {String} type 任务类型
     * @apiSuccess (usertask) {String} target 任务总目标
     * @apiSuccess (usertask) {String} has_target 任务已完成目标
     * @apiSuccess (usertask) {String} status 任务状态
     * @apiSuccess (usertask) {String} bonus_status 任务奖金状态
     * @apiSuccess (usertask) {String} start_time 任务开始时间
     * @apiSuccess (usertask) {String} end_time 任务结束时间
     * @apiSampleRequest /index/rights/userTask
     * @return array
     * @author rzc
     */
    public function userTask() {
        $conId   = trim($this->request->post('con_id'));
        $taskid  = trim($this->request->post('taskid'));
        $page    = trim($this->request->post('page'));
        $pageNum = trim($this->request->post('page_num'));
        if (!is_numeric($page) || $page < 1) {
            return ['code' => '3001']; //page错误
        }
        if (!is_numeric($pageNum) || $pageNum < 1) {
            $pageNum = 10;
        }
        $page    = intval($page);
        $pageNum = intval($pageNum);
        if (!empty($taskid)) {
            if (!is_numeric($taskid)) {
                return ['code' => '3003'];
            }
            intval($taskid);
        }
        $result = $this->app->rights->userTask($conId, $page, $pageNum, $taskid);
        return $result;
    }

    /**
     * @api              {post} / 用户任务进度
     * @apiDescription   userTaskProgress
     * @apiGroup         index_rights
     * @apiName          userTaskProgress
     * @apiParam (入参) {String} con_id 用户con_id
     * @apiSuccess (返回) {String} code 200:成功 / 3000:用户不存在 / 3001:无任务 /
     * @apiSuccess (返回) {String} taskprogress
     * @apiSampleRequest /index/rights/userTaskProgress
     * @return array
     * @author rzc
     */
    public function userTaskProgress() {
        $conId  = trim($this->request->post('con_id'));
        $result = $this->app->rights->userTaskProgress($conId);
        return $result;
    }

    /**
     * @api              {post} / 用户任务详情
     * @apiDescription   userTaskInfo
     * @apiGroup         index_rights
     * @apiName          userTaskInfo
     * @apiParam (入参) {String} con_id 用户con_id
     * @apiParam (入参) {String} taskid 任务ID
     * @apiParam (入参) {Number} page 页码
     * @apiParam (入参) {Number} page_num 查询记录条数
     * @apiSuccess (返回) {String} code 200:成功 / 3000:用户不存在 / 3001:taskid必须是数字 / 3002:该任务不存在 / 3003:page错误
     * @apiSuccess (返回) {Array} task_invited
     * @apiSuccess (task_invited) {String} id
     * @apiSuccess (task_invited) {String} utask_id 任务ID
     * @apiSuccess (task_invited) {String} user_identity 邀请时身份
     * @apiSuccess (task_invited) {String} timekey 关联时间索引
     * @apiSuccess (task_invited) {String} bonus 奖励金
     * @apiSuccess (task_invited) {String} create_time 记录时间
     * @apiSuccess (task_invited[user]) {String} nick_name 用户昵称
     * @apiSuccess (task_invited[user]) {String} avatar 用户头像
     * @apiSampleRequest /index/rights/userTaskInfo
     * @return array
     * @author rzc
     */
    public function userTaskInfo() {
        $conId   = trim($this->request->post('con_id'));
        $taskid  = trim($this->request->post('taskid'));
        $page    = trim($this->request->post('page'));
        $pageNum = trim($this->request->post('page_num'));
        if (!is_numeric($taskid)) {
            return ['code' => '3001'];
        }
        if (!is_numeric($page) || $page < 1) {
            return ['code' => '3003']; //page错误
        }
        if (!is_numeric($pageNum) || $pageNum < 1) {
            $pageNum = 10;
        }
        $page    = intval($page);
        $pageNum = intval($pageNum);
        $result = $this->app->rights->userTaskInfo($conId, $taskid, $page, $pageNum);
        return $result;
    }

    /**
     * @api              {post} / 查询创业圈
     * @apiDescription   getUserBusinessCircle
     * @apiGroup         index_rights
     * @apiName          getUserBusinessCircle
     * @apiParam (入参) {String} con_id 用户con_id
     * @apiParam (入参) {String} type 查询类型 1.创业店主 2.钻石会员 3.普通会员
     * @apiParam (入参) {Number} page 页码
     * @apiParam (入参) {String} page_num 查询记录条数
     * @apiSuccess (返回) {String} code 200:成功 / 3000:用户不存在 / 3001:type类型错误 / 3002:该用户无权限查看 / 3003:无效查询
     * @apiSuccess (返回) {String} total 此次第一排查询累计数据
     * @apiSuccess (返回) {Array} business 返回数据
     * @apiSuccess (business) {String} uid 加密后uid
     * @apiSuccess (business) {String} nick_name 昵称
     * @apiSuccess (business) {String} avatar 头像
     * @apiSuccess (business) {String} user_identity 会员身份1.普通,2.钻石会员3.创业店主4.boss合伙人
     * @apiSuccess (business) {String} count 下级人数
     * @apiSampleRequest /index/rights/getUserBusinessCircle
     * @return array
     * @author rzc
     */
    public function getUserBusinessCircle(){
        $conId   = trim($this->request->post('con_id'));
        $type   = trim($this->request->post('type'));
        $page    = trim($this->request->post('page'));
        $pageNum = trim($this->request->post('page_num'));

        $typeData = [1, 2, 3];
        if (!is_numeric($type) || !in_array($type, $typeData)) {
            return ['code' => '3001'];
        }
        if (!is_numeric($page) || $page < 1) {
            return ['code' => '3003']; //page错误
        }
        if (!is_numeric($pageNum) || $pageNum < 1) {
            $pageNum = 10;
        }
        $page    = intval($page);
        $pageNum = intval($pageNum);
        $result = $this->app->rights->getUserBusinessCircle($conId, $type, $page, $pageNum);
        return $result;
    }
}