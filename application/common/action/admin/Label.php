<?php

namespace app\common\action\admin;

use app\facade\DbGoods;
use app\facade\DbLabel;
use Config;
use pinyin\Pinyin;
use think\Db;

class Label extends CommonIndex {
    private $transformRedisKey;
    private $labelLibraryRedisKey;

    public function __construct() {
        parent::__construct();
        $this->transformRedisKey    = Config::get('rediskey.label.redisLabelTransform');
        $this->labelLibraryRedisKey = Config::get('rediskey.label.redisLabelLibrary');
    }

    /**
     * 商品标签列表
     * @param $goodsId
     * @return array
     * @author zyr
     */
    public function goodsLabelList($goodsId) {
        $labelGoodsRelation = DbLabel::getLabelGoodsRelation(['goods_id' => $goodsId], 'label_lib_id');
        if (empty($labelGoodsRelation)) {
            return ['code' => '3000'];
        }
        $labelIdList = array_column($labelGoodsRelation, 'label_lib_id');
        $restlt      = $this->getLabelLibrary($labelIdList);
        return ['code' => '200', 'data' => $this->labelProcess($restlt)];
    }

    /**
     * 标签搜索
     * @param $searchContent
     * @return array
     * @author zyr
     */
    public function searchLabel($searchContent) {
        $data     = [];
        $iterator = null;
        while (true) {
            $keys = $this->redis->hScan($this->transformRedisKey, $iterator, $searchContent . '*');
            if ($keys === false) { //迭代结束，未找到匹配pattern的key
                break;
            }
            foreach ($keys as $key) {
                $data = array_merge($data, json_decode($key, true));
            }
        }
        if (empty($data)) {
            return ['code' => '3000'];
        }
        $data   = array_unique($data);
        $result = $this->getLabelLibrary($data);
        return ['code' => '200', 'data' => $this->labelProcess($result)];
    }

    /**
     * 打标签
     * @param $labelName
     * @param $goodsId
     * @return array
     * @author zyr
     */
    public function addLabelToGoods($labelName, $goodsId) {
        $goods = DbGoods::getOneGoods(['id' => $goodsId], 'id');
        if (empty($goods)) {
            return ['code' => '3003']; //商品不存在
        }
        $labeLibId = 0;
        $labeLib   = DbLabel::getLabelLibrary(['label_name' => $labelName], 'id', true);
        if (!empty($labeLib)) { //标签库有该标签
            $labeLibId          = $labeLib['id'];
            $labelGoodsRelation = DbLabel::getLabelGoodsRelation(['label_lib_id' => $labeLibId, 'goods_id' => $goodsId], 'id', true);
            if (!empty($labelGoodsRelation)) {
                return ['code' => '3004']; //标签已关联该商品
            }
        }
        $flag = false;
        Db::startTrans();
        try {
            if (empty($labeLibId)) { //标签库没有就添加
                $labeLibId = DbLabel::addLabelLibrary(['label_name' => $labelName]);
                $flag      = true;
            }
            DbLabel::addLabelGoodsRelation(['goods_id' => $goodsId, 'label_lib_id' => $labeLibId]); //添加标签商品关联
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3006']; //添加失败
        }
        if ($flag) {
            $this->setTransform($this->getTransformPinyin($labelName), $labeLibId);
            $this->setLabelLibrary($labeLibId, $labelName);
        }
        return ['code' => '200'];
    }

    /**
     * 删除商品标签
     * @param $labeLibId
     * @param $goodsId
     * @return array
     * @author zyr
     */
    public function labelDel($labeLibId, $goodsId) {
        $labelGoodsRelation = DbLabel::getLabelGoodsRelation(['label_lib_id' => $labeLibId, 'goods_id' => $goodsId], 'id', true); //要删除的商品标签关联
        if (empty($labelGoodsRelation)) {
            return ['code' => '3003']; //商品标签不存在
        }
        $delLabelGoodsRelationId = $labelGoodsRelation['id'];
        $labelGoodsRelationList  = DbLabel::getLabelGoodsRelation([
            ['label_lib_id', '=', $labeLibId],
            ['goods_id', '<>', $goodsId],
        ], 'id');
        $delLabelLibraryId       = 0;
        if (empty($labelGoodsRelationList)) { //没有其他商品关联这个标签
            $delLabelLibraryId = $labeLibId;
            $labelLibName      = DbLabel::getLabelLibrary(['id' => $delLabelLibraryId], 'label_name', true);
            $labelLibName      = $labelLibName['label_name'];
        }
        $flag = false;
        Db::startTrans();
        try {
            DbLabel::delLabelGoodsRelation($delLabelGoodsRelationId);
            if (!empty($delLabelLibraryId)) {
                DbLabel::delLabelLibrary($delLabelLibraryId);
                $flag = true;
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3005']; //删除失败
        }
        if ($flag === true) {
            $transList = $this->getTransformPinyin($labelLibName);
            foreach ($transList as $tlk => $tl) {
                $labelLibraryIdList = json_decode($this->redis->hGet($this->transformRedisKey, $tl), true);
                if (!in_array($delLabelLibraryId, $labelLibraryIdList)) {
                    continue;
                }
                $indexKey = array_search($delLabelLibraryId, $labelLibraryIdList);
                if ($indexKey !== false) {
                    array_splice($labelLibraryIdList, $indexKey, 1);
                }
                if (!empty($labelLibraryIdList)) {
                    $this->redis->hSet($this->transformRedisKey, $tl, json_encode($labelLibraryIdList));
                } else {
                    $this->redis->hDel($this->transformRedisKey, $tl);
                }
            }
            $this->redis->hDel($this->labelLibraryRedisKey, $delLabelLibraryId);
        }
        return ['code' => '200'];
    }

    private function getTransformPinyin($name) {
        $pinyin       = new Pinyin();
        $ucWord       = $pinyin->transformUcwords($name); //拼音首字母,包含非汉字内容
        $ucWord2      = $pinyin->transformUcwords($name, ' ', true); //拼音首字母,不包含非汉字内容
        $withoutTone  = $pinyin->transformWithoutTone($name, '', false); //包含非中文的全拼音
        $withoutTone2 = $pinyin->transformWithoutTone($name, '', true); //不包含非中文的全拼音
        $data         = [
            $name, //全名
            $withoutTone, //包含非中文的全拼音
            $withoutTone2, //不包含非中文的全拼音
            $ucWord, //拼音首字母,包含非汉字内容
            $ucWord2, //拼音首字母,不包含非汉字内容
        ];
        return array_unique($data);
    }

    /**
     * 通过标签库的id列表获取标签列表
     * @param $labeLibIdList array
     * @return array
     * @author zyr
     */
    private function getLabelLibrary($labeLibIdList) {
        return $this->redis->hMGet($this->labelLibraryRedisKey, $labeLibIdList);
    }

    /**
     * 标签转换后存储
     * @param $trans 标签转换后的列表
     * @param $labelLibId 标签库id
     * @author zyr
     */
    private function setTransform($trans, $labelLibId) {
        $redisKey = $this->transformRedisKey;
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
        $redisKey = $this->labelLibraryRedisKey;
        $this->redis->hSetNx($redisKey, $labelLibId, $name);
    }

    private function labelProcess($result) {
        $data = [];
        foreach ($result as $k => $v) {
            $arr = ['label_id' => $k, 'label_name' => $v];
            array_push($data, $arr);
        }
        return $data;
    }
}