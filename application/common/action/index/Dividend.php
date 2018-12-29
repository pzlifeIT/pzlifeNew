<?php

namespace app\common\action\index;

use app\common\model\UserRelation;
use app\common\model\Users;

/**
 * Class Dividend
 * 分利计算
 * @package app\common\profit
 */
class Dividend {
    private $firstRatio;//第一层比例75%
    private $secondRatio;//第二层比例,第一层的15%
    private $thirdRatio;//第三层比例,第二层的15%
    private $uid;//购买用户

    public function __construct($uid) {
        $this->firstRatio  = 75;
        $this->secondRatio = 15;
        $this->thirdRatio  = 15;
        $this->uid         = $uid;
    }

    /**
     * @param $marginPrice 毛利
     */
    public function getRes($marginPrice) {

    }
}