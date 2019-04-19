<?php

namespace app\common\action\admin;

use app\facade\DbGoods;
use app\facade\DbLabel;
use Config;
use Overtrue\Pinyin\Pinyin;
use think\Db;

class Label extends CommonIndex {
    private $transformRedisKey;
    private $labelLibraryRedisKey;
    private $labelLibraryHeatRedisKey;

    public function __construct() {
        parent::__construct();
        $this->transformRedisKey        = Config::get('rediskey.label.redisLabelTransform');
        $this->labelLibraryRedisKey     = Config::get('rediskey.label.redisLabelLibrary');
        $this->labelLibraryHeatRedisKey = Config::get('rediskey.label.redisLabelLibraryHeat');
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
//        $restlt      = $this->getLabelLibrary($labelIdList);
        $result = DbLabel::getLabelLibrary([['id', 'in', $labelIdList]], 'id as label_id,label_name');
        return ['code' => '200', 'data' => $result];
    }

    /**
     * 标签搜索
     * @param $searchContent
     * @return array
     * @author zyr
     */
    public function searchLabel($searchContent) {
        $data = $this->getLabelScan($searchContent);
        if (empty($data)) {
            return ['code' => '3000'];
        }
        $data   = array_unique($data);
        $heat   = $this->redis->zRevRange($this->labelLibraryHeatRedisKey, 0, -1);
        $result = [];
        foreach ($heat as $v) {
            if (in_array($v, $data)) {
                array_push($result, $v);
                if (count($result) >= 10) {
                    break;
                }
            }
        }
        $result = $this->getLabelLibrary($result);
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
        $labelLibId = 0;
        $labelLib   = DbLabel::getLabelLibrary(['label_name' => $labelName], 'id', true);
        if (!empty($labelLib)) { //标签库有该标签
            $labelLibId         = $labelLib['id'];
            $labelGoodsRelation = DbLabel::getLabelGoodsRelation(['label_lib_id' => $labelLibId, 'goods_id' => $goodsId], 'id', true);
            if (!empty($labelGoodsRelation)) {
                return ['code' => '3004']; //标签已关联该商品
            }
        }
        $goodsStatus = DbGoods::getOneGoods(['id' => $goodsId], 'status');
        $goodsStatus = $goodsStatus['status'];
        $flag        = false;
        Db::startTrans();
        try {
            if (empty($labelLibId)) { //标签库没有就添加
                $labelLibId = DbLabel::addLabelLibrary(['label_name' => $labelName]);
                $flag       = true;
            } else {
                DbLabel::modifyHeat($labelLibId);
            }
            DbLabel::addLabelGoodsRelation(['goods_id' => $goodsId, 'label_lib_id' => $labelLibId]); //添加标签商品关联
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => '3006']; //添加失败
        }
        if ($goodsStatus == '1') {
            if ($flag === true) {
                $this->setTransform($this->getTransformPinyin($labelName), $labelLibId);
                $this->setLabelLibrary($labelLibId, $labelName);
                $this->setLabelHeat($labelLibId, true);//执行zAdd
            } else {
                $this->setLabelHeat($labelLibId, false);//执行zIncrBy
            }
        }
        return ['code' => '200'];
    }

    /**
     * 删除商品标签
     * @param $labelLibId
     * @param $goodsId
     * @return array
     * @author zyr
     */
    public function labelDel($labelLibId, $goodsId) {
        $labelGoodsRelation = DbLabel::getLabelGoodsRelation(['label_lib_id' => $labelLibId, 'goods_id' => $goodsId], 'id', true); //要删除的商品标签关联
        if (empty($labelGoodsRelation)) {
            return ['code' => '3003']; //商品标签不存在
        }
        $delLabelGoodsRelationId = $labelGoodsRelation['id'];
        $labelGoodsRelationList  = DbLabel::getLabelGoodsRelation([
            ['label_lib_id', '=', $labelLibId],
            ['goods_id', '<>', $goodsId],
        ], 'id');
        $delLabelLibraryId       = 0;
        $labelLibName            = '';
        if (empty($labelGoodsRelationList)) { //没有其他商品关联这个标签
            $delLabelLibraryId = $labelLibId;
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
                $labelKey = $this->redis->hGet($this->transformRedisKey, $tl);
                if ($labelKey === false) {
                    continue;
                }
                $labelLibraryIdList = json_decode($labelKey, true);
                if (!in_array($delLabelLibraryId, $labelLibraryIdList)) {
                    continue;
                }
                $indexKey = array_search($delLabelLibraryId, $labelLibraryIdList);
                if ($indexKey === false) {
                    continue;
                }
                array_splice($labelLibraryIdList, $indexKey, 1);
                if (!empty($labelLibraryIdList)) {
                    $this->redis->hSet($this->transformRedisKey, $tl, json_encode($labelLibraryIdList));
                } else {
                    $this->redis->hDel($this->transformRedisKey, $tl);
                }
            }
            $this->redis->zDelete($this->labelLibraryHeatRedisKey, $delLabelLibraryId);
            $this->redis->hDel($this->labelLibraryRedisKey, $delLabelLibraryId);
        }
        return ['code' => '200'];
    }

    private function getTransformPinyin($name) {
        if (empty($name)) {
            return [];
        }
        $pinyin       = new Pinyin('Overtrue\Pinyin\MemoryFileDictLoader');
        $withoutTone2 = implode('', $pinyin->convert($name, PINYIN_UMLAUT_V));
        $withoutTone  = $pinyin->permalink($name, '', PINYIN_UMLAUT_V);
        $ucWord       = $pinyin->abbr($name, '', PINYIN_KEEP_ENGLISH);
        $ucWord2      = $pinyin->abbr($name, '');
        $data         = [
            strtolower($name), //全名
            strtolower($withoutTone), //包含非中文的全拼音
            strtolower($withoutTone2), //不包含非中文的全拼音
            strtolower($ucWord), //拼音首字母,包含非汉字内容
            strtolower($ucWord2), //拼音首字母,不包含非汉字内容
        ];
        return array_filter(array_unique($data));
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

    /**
     * 标签库模糊查询
     * @param $searchContent
     * @return array
     * @author zyr
     */
    private function getLabelScan($searchContent) {
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
            return [];
        }
        $data = array_unique($data);
        return $data;
    }

    private function setLabelHeat($labelLibId, $heat) {
        $redisKey = $this->labelLibraryHeatRedisKey;
        if ($heat === true) {
            $this->redis->zAdd($redisKey, 1, $labelLibId);
        } else {
            $this->redis->zIncrBy($redisKey, 1, $labelLibId);
        }
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