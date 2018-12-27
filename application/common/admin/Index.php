<?php

namespace app\common\admin;

use app\common\model\Areas;
use third\PHPTree;

class Index {

    /**
     * 省市列表
     * @return array
     * @author zyr
     */
    public function getProvinceCity() {
        $result = Areas::where('level', 'in', [1, 2])->field('id,area_name,pid')->select()->toArray();
        if (empty($result)) {
            return ['code' => '3001'];
        }
        $phptree = new PHPTree($result);
        $phptree->setParam('pk', 'id');
        $result = $phptree->listTree();
        return ['code' => '200', 'data' => $result];
    }

    /**
     * 通过省id获取省下面的所有市
     * @param int $pid 上级id
     * @param int $level 层级 1.省 2.市 3.区
     * @return array
     * @author zyr
     */
    public function getArea($pid, $level) {
        $province = Areas::where('id', '=', $pid)->where('level', '=', ($level - 1))->field('id,area_name')->findOrEmpty()->toArray();
        if (empty($province)) {//判断省市是否存在
            return ['code' => '3001'];
        }
        $result = Areas::where('pid', '=', $pid)->where('level', '=', $level)->field('id,area_name,pid')->select()->toArray();
        if (empty($result)) {//获取下级列表
            return ['code' => '3000'];
        }
        return ['code' => '200', 'data' => $result];
    }
}