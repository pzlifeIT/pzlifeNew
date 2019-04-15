<?php
namespace pinyin;
use cache\Phpredis;

class Pinyin {
    private $ChineseCharacters; //utf-8中国汉字集合
    private $charset = 'utf-8'; //编码
    private $redis;
    public function __construct() {
        if (empty($this->ChineseCharacters)) {
            $this->redis = Phpredis::getConn();
            $redisKey    = 'pinyin:chineseCharacters';
            if ($this->redis->exists($redisKey)) {
                $this->ChineseCharacters = $this->redis->get($redisKey);
            } else {
                $chineseCharacters = file_get_contents(dirname(__FILE__) . '/ChineseCharacters.dat');
                $this->redis->setEx($redisKey, 2592000, $chineseCharacters);
                $this->ChineseCharacters = $chineseCharacters;
            }
        }
    }
    /**
     * 转成带有声调的汉语拼音
     * @param $input_char String  需要转换的汉字
     * @param $delimiter  String   转换之后拼音之间分隔符
     * @param $outside_ignore  Boolean     是否忽略非汉字内容
     */
    public function transformWithTone($input_char, $delimiter = ' ', $outside_ignore = false) {
        $input_len   = mb_strlen($input_char, $this->charset);
        $output_char = '';
        for ($i = 0; $i < $input_len; $i++) {
            $word = mb_substr($input_char, $i, 1, $this->charset);
            if (preg_match('/^[\x{4e00}-\x{9fa5}]$/u', $word) && preg_match('/\,' . preg_quote($word) . '(.*?)\,/', $this->ChineseCharacters, $matches)) {
                $output_char .= $matches[1] . $delimiter;
            } else if (!$outside_ignore) {
                $output_char .= $word;
            }
        }
        return $output_char;
    }

    /**
     * 转成带无声调的汉语拼音
     * @param $input_char String  需要转换的汉字
     * @param $delimiter  String   转换之后拼音之间分隔符
     * @param $outside_ignore  Boolean     是否忽略非汉字内容
     */
    public function transformWithoutTone($input_char, $delimiter = '', $outside_ignore = true) {

        $char_with_tone = $this->TransformWithTone($input_char, $delimiter, $outside_ignore);

        $char_without_tone = str_replace(array('ā', 'á', 'ǎ', 'à', 'ō', 'ó', 'ǒ', 'ò', 'ē', 'é', 'ě', 'è', 'ī', 'í', 'ǐ', 'ì', 'ū', 'ú', 'ǔ', 'ù', 'ǖ', 'ǘ', 'ǚ', 'ǜ', 'ü'),
            array('a', 'a', 'a', 'a', 'o', 'o', 'o', 'o', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'u', 'u', 'u', 'u', 'v', 'v', 'v', 'v', 'v')
            , $char_with_tone);
        return $char_without_tone;

    }

    /**
     * 转成汉语拼音首字母,只包括汉字
     * @param $input_char String  需要转换的汉字
     * @param $delimiter  String   转换之后拼音之间分隔符
     */
    public function transformUcwordsOnlyChar($input_char, $delimiter = '') {

        $char_without_tone = ucwords($this->TransformWithoutTone($input_char, ' ', true));
        $ucwords           = preg_replace('/[^A-Z]/', '', $char_without_tone);
        if (!empty($delimiter)) {
            $ucwords = implode($delimiter, str_split($ucwords));
        }
        return $ucwords;

    }

    /**
     * 转成汉语拼音首字母,包含非汉字内容
     * @param $input_char String  需要转换的汉字
     * @param $delimiter  String   转换之后拼音之间分隔符
     */
    public function transformUcwords($input_char, $delimiter = ' ', $outside_ignore = false) {

        $input_len   = mb_strlen($input_char, $this->charset);
        $output_char = '';
        for ($i = 0; $i < $input_len; $i++) {
            $word = mb_substr($input_char, $i, 1, $this->charset);
            if (preg_match('/^[\x{4e00}-\x{9fa5}]$/u', $word) && preg_match('/\,' . preg_quote($word) . '(.*?)\,/', $this->ChineseCharacters, $matches)) {
                $output_char .= $matches[1] . $delimiter;
            } else if (!$outside_ignore) {
                $output_char .= $delimiter . $word . $delimiter;
            }
        }
        $output_char = str_replace(array('ā', 'á', 'ǎ', 'à', 'ō', 'ó', 'ǒ', 'ò', 'ē', 'é', 'ě', 'è', 'ī', 'í', 'ǐ', 'ì', 'ū', 'ú', 'ǔ', 'ù', 'ǖ', 'ǘ', 'ǚ', 'ǜ', 'ü'),
            array('a', 'a', 'a', 'a', 'o', 'o', 'o', 'o', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'u', 'u', 'u', 'u', 'v', 'v', 'v', 'v', 'v')
            , $output_char);

        $array = explode($delimiter, $output_char);
        $array = array_filter($array);
        $res   = '';
        foreach ($array as $list) {
            $res .= substr($list, 0, 1);
        }
        return $res;
    }

}