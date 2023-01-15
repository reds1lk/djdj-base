<?php

namespace Djdj\Base\util;

class IdCardUtils
{
    /**
     * 检查身份证号码是否正确的正则
     */
    protected const REGX = '#(^\d{15}$)|(^\d{17}(\d|X)$)#';

    /**
     * 省
     */
    protected const provinces = [
        11 => "北京", 12 => "天津", 13 => "河北", 14 => "山西", 15 => "内蒙古",
        21 => "辽宁", 22 => "吉林", 23 => "黑龙江", 31 => "上海", 32 => "江苏",
        33 => "浙江", 34 => "安徽", 35 => "福建", 36 => "江西", 37 => "山东", 41 => "河南",
        42 => "湖北", 43 => "湖南", 44 => "广东", 45 => "广西", 46 => "海南", 50 => "重庆",
        51 => "四川", 52 => "贵州", 53 => "云南", 54 => "西藏", 61 => "陕西", 62 => "甘肃",
        63 => "青海", 64 => "宁夏", 65 => "新疆", 71 => "台湾", 81 => "香港", 82 => "澳门", 91 => "国外"
    ];

    /**
     * 身份证号码校验
     */
    public static function vaild($idCard)
    {
        // 基础的校验，校验身份证格式是否正确
        if (!self::isCardNumber($idCard)) {
            return false;
        }
        // 将 15 位转换成 18 位
        $idCard = self::fifteen2Eighteen($idCard);
        // 检查省是否存在
        if (!self::checkProvince($idCard)) {
            return false;
        }
        // 检查生日是否正确
        if (!self::checkBirthday($idCard)) {
            return false;
        }
        // 检查校验码
        return self::checkCode($idCard);
    }

    /**
     * 检测是否是身份证号码
     */
    private static function isCardNumber($idCard)
    {
        return preg_match(self::REGX, $idCard);
    }

    /**
     * 15位转18位
     */
    private static function fifteen2Eighteen($idCard)
    {
        if (strlen($idCard) != 15) {
            return $idCard;
        }

        // 如果身份证顺序码是996 997 998 999，这些是为百岁以上老人的特殊编码
        // $code = array_search(substr($idCard, 12, 3), [996, 997, 998, 999]) !== false ? '18' : '19';
        // 一般 19 就行了
        $code = '19';
        $idCardBase = substr($idCard, 0, 6) . $code . substr($idCard, 6, 9);
        return $idCardBase . self::genCode($idCardBase);
    }

    /**
     * 检查省份是否正确
     */
    private static function checkProvince($idCard)
    {
        $provinceNumber = substr($idCard, 0, 2);
        return isset(self::provinces[$provinceNumber]);
    }

    /**
     * 检测生日是否正确
     */
    private static function checkBirthday($idCard)
    {
        $regx = '#^\d{6}(\d{4})(\d{2})(\d{2})\d{3}[0-9X]$#';
        if (!preg_match($regx, $idCard, $matches)) {
            return false;
        }
        array_shift($matches);
        list($year, $month, $day) = $matches;
        return checkdate($month, $day, $year);
    }

    /**
     * 校验码比对
     */
    private static function checkCode($idCard)
    {
        $idCardBase = substr($idCard, 0, 17);
        $code = self::genCode($idCardBase);
        return $idCard == ($idCardBase . $code);
    }

    /**
     * 生成校验码
     */
    final protected static function genCode($idCardBase)
    {
        $idCardLength = strlen($idCardBase);
        if ($idCardLength != 17) {
            return false;
        }
        $factor = [7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2];
        $verifyNumbers = ['1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2'];
        $sum = 0;
        for ($i = 0; $i < $idCardLength; $i++) {
            $sum += substr($idCardBase, $i, 1) * $factor[$i];
        }
        $index = $sum % 11;
        return $verifyNumbers[$index];
    }
}
