<?php

namespace app\common\action\index;

use app\facade\DbGoods;
use app\facade\DbShops;
use function Qiniu\json_decode;
use Config;

class Recommend extends CommonIndex {
    private $redisCartUserKey = 'index:cart:user:';

    public function __construct() {
        parent::__construct();
        $this->indexShow = Config::get('redisKey.index.redisIndexShow');
    }
    /**
     * 查询首页显示内容
     * @param $uid
     * @author rzc
     */
    public function getRecommend(){
        // $this->redis = Phpredis::getConn();
        // $redisListKey = Config::get('redisKey.index.redisIndexShow');
        $weekday = date('w');
        // print_r($weekday);die;
        $indexShow = $this->redis->get($this->indexShow);
        if (empty($indexShow)) {
            return ['code' => 3000];
        }
        $indexShow = json_decode($indexShow,true);
       /*  foreach ($indexShow as $key => $value) {
            if ($value['son'] && $value['model_id'] == 5) {
               
                foreach ($value['son'] as $val => $son) {
                    if ($son['show_days'] != $weekday){
                        unset($indexShow[$key]['son'][$val]);
                    }
                }
                $indexShow[$key]['son'] = array_values($indexShow[$key]['son']);

            }
            // print_r($value);die;
        } */
        return ['code' => 200,'recommends' => $indexShow ];
    }
}
