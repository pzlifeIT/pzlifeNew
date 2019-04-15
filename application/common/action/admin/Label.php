<?php
namespace app\common\action\admin;
use app\common\action\admin\CommonIndex;
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
        if(empty($data)){
            return ['code' => '3000'];
        }
        $data   = array_unique($data);
        $result = $this->redis->hMGet($this->labelLibraryRedisKey, $data);
        return ['code' => '200', $result];
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
            print_r($e);
            Db::rollback();
            return ['code' => '3006']; //添加失败
        }
        if ($flag) {
            $this->setTransform($this->getTransformPinyin($labelName), $labeLibId);
            $this->setLabelLibrary($labeLibId, $labelName);
        }
        return ['code' => '200'];
    }

    private function getTransformPinyin($name) {
        $pinyin       = new Pinyin();
        $ucWord       = $pinyin->transformUcwords($name); //拼音首字母,包含非汉字内容
        $ucWord2      = $pinyin->transformUcwords($name, ' ', true); //拼音首字母,不包含非汉字内容
        $withoutTone  = $pinyin->transformWithoutTone($name, '', false); //包含非中文的全拼音
        $withoutTone2 = $pinyin->transformWithoutTone($name, '', true); //不包含非中文的全拼音
        return [
            '1' => $name, //全名
            '2' => $withoutTone, //包含非中文的全拼音
            '3' => $withoutTone2, //不包含非中文的全拼音
            '4' => $ucWord, //拼音首字母,包含非汉字内容
            '5' => $ucWord2, //拼音首字母,不包含非汉字内容
        ];
    }

    /**
     * 通过标签库的id列表获取标签列表
     * @param $labeLibIdList array
     * @return: array
     * @author: zyr
     */
    private function getLabelLibrary($labeLibIdList) {
        return $this->redis->hMGet($this->labelLibraryRedisKey, $labeLibIdList);
    }

    /**
     * 标签转换后存储
     * @param $trans 标签转换后的列表
     * @param $labelLibId 标签库id
     * @author: zyr
     */
    private function setTransform($trans, $labeLibId) {
        $redisKey = $this->transformRedisKey;
        foreach ($trans as $t) {
            if (!$this->redis->hSetNx($redisKey, $t, json_encode([$labeLibId]))) {
                $transLabel = json_decode($this->redis->hGet($redisKey, $t), true);
                if (!in_array($labeLibId, $transLabel)) {
                    array_push($transLabel, $labeLibId);
                    $this->redis->hSet($redisKey, $t, json_encode($transLabel));
                }
            }
        }
    }

    /**
     * @description:
     * @param $labelLibId 标签库id
     * @param $name 标签名
     * @author: zyr
     */
    private function setLabelLibrary($labelLibId, $name) {
        $redisKey = $this->labelLibraryRedisKey;
        $this->redis->hSetNx($redisKey, $labelLibId, $name);
    }
}