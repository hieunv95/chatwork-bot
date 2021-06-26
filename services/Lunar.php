<?php

namespace Services;

use \Exception;

class Lunar {

  /**
   * |----4位闰月|-------------13位1为30天，0为29天|
   *
   */
  private static $lunar_month_days = array(
         1887,   0x1694,   0x16aa,   0x4ad5,    0xab6,   0xc4b7,    0x4ae,    0xa56,
       0xb52a,   0x1d2a,    0xd54,   0x75aa,   0x156a,  0x1096d,    0x95c,   0x14ae,
       0xaa4d,   0x1a4c,   0x1b2a,   0x8d55,    0xad4,   0x135a,   0x495d,    0x95c,
       0xd49b,   0x149a,   0x1a4a,   0xbaa5,   0x16a8,   0x1ad4,   0x52da,   0x12b6,
       0xe937,    0x92e,   0x1496,   0xb64b,    0xd4a,    0xda8,   0x95b5,    0x56c,
       0x12ae,   0x492f,    0x92e,   0xcc96,   0x1a94,   0x1d4a,   0xada9,    0xb5a,
        0x56c,   0x726e,   0x125c,   0xf92d,   0x192a,   0x1a94,   0xdb4a,   0x16aa,
        0xad4,   0x955b,    0x4ba,   0x125a,   0x592b,   0x152a,   0xf695,    0xd94,
       0x16aa,   0xaab5,    0x9b4,   0x14b6,   0x6a57,    0xa56,  0x1152a,   0x1d2a,
        0xd54,   0xd5aa,   0x156a,    0x96c,   0x94ae,   0x14ae,    0xa4c,   0x7d26,
       0x1b2a,   0xeb55,    0xad4,   0x12da,   0xa95d,    0x95a,   0x149a,   0x9a4d,
       0x1a4a,  0x11aa5,   0x16a8,   0x16d4,   0xd2da,   0x12b6,    0x936,   0x9497,
       0x1496,  0x1564b,    0xd4a,    0xda8,   0xd5b4,   0x156c,   0x12ae,   0xa92f,
        0x92e,    0xc96,   0x6d4a,   0x1d4a,  0x10d65,    0xb58,   0x156c,   0xb26d,
       0x125c,   0x192c,   0x9a95,   0x1a94,   0x1b4a,   0x4b55,    0xad4,   0xf55b,
        0x4ba,   0x125a,   0xb92b,   0x152a,   0x1694,   0x96aa,   0x15aa,  0x12ab5,
        0x974,   0x14b6,   0xca57,    0xa56,   0x1526,   0x8e95,    0xd54,   0x15aa,
       0x49b5,    0x96c,   0xd4ae,   0x149c,   0x1a4c,   0xbd26,   0x1aa6,    0xb54,
       0x6d6a,   0x12da,  0x1695d,    0x95a,   0x149a,   0xda4b,   0x1a4a,   0x1aa4,
       0xbb54,   0x16b4,    0xada,   0x495b,    0x936,   0xf497,   0x1496,   0x154a,
       0xb6a5,    0xda4,   0x15b4,   0x6ab6,   0x126e,  0x1092f,    0x92e,    0xc96,
       0xcd4a,   0x1d4a,    0xd64,   0x956c,   0x155c,   0x125c,   0x792e,   0x192c,
       0xfa95,   0x1a94,   0x1b4a,   0xab55,    0xad4,   0x14da,   0x8a5d,    0xa5a,
      0x1152b,   0x152a,   0x1694,   0xd6aa,   0x15aa,    0xab4,   0x94ba,   0x14b6,
        0xa56,   0x7527,    0xd26,   0xee53,    0xd54,   0x15aa,   0xa9b5,    0x96c,
       0x14ae,   0x8a4e,   0x1a4c,  0x11d26,   0x1aa4,   0x1b54,   0xcd6a,    0xada,
        0x95c,   0x949d,   0x149a,   0x1a2a,   0x5b25,   0x1aa4,   0xfb52,   0x16b4,
        0xaba,   0xa95b,    0x936,   0x1496,   0x9a4b,   0x154a,  0x136a5,    0xda4,
       0x15ac,
  );

  private static $gregorian_1_1 = array(
         1887,  0xec04c,  0xec23f,  0xec435,  0xec649,  0xec83e,  0xeca51,  0xecc46,
      0xece3a,  0xed04d,  0xed242,  0xed436,  0xed64a,  0xed83f,  0xeda53,  0xedc48,
      0xede3d,  0xee050,  0xee244,  0xee439,  0xee64d,  0xee842,  0xeea36,  0xeec4a,
      0xeee3e,  0xef052,  0xef246,  0xef43a,  0xef64e,  0xef843,  0xefa37,  0xefc4b,
      0xefe41,  0xf0054,  0xf0248,  0xf043c,  0xf0650,  0xf0845,  0xf0a38,  0xf0c4d,
      0xf0e42,  0xf1037,  0xf124a,  0xf143e,  0xf1651,  0xf1846,  0xf1a3a,  0xf1c4e,
      0xf1e44,  0xf2038,  0xf224b,  0xf243f,  0xf2653,  0xf2848,  0xf2a3b,  0xf2c4f,
      0xf2e45,  0xf3039,  0xf324d,  0xf3442,  0xf3636,  0xf384a,  0xf3a3d,  0xf3c51,
      0xf3e46,  0xf403b,  0xf424e,  0xf4443,  0xf4638,  0xf484c,  0xf4a3f,  0xf4c52,
      0xf4e48,  0xf503c,  0xf524f,  0xf5445,  0xf5639,  0xf584d,  0xf5a42,  0xf5c35,
      0xf5e49,  0xf603e,  0xf6251,  0xf6446,  0xf663b,  0xf684f,  0xf6a43,  0xf6c37,
      0xf6e4b,  0xf703f,  0xf7252,  0xf7447,  0xf763c,  0xf7850,  0xf7a45,  0xf7c39,
      0xf7e4d,  0xf8042,  0xf8254,  0xf8449,  0xf863d,  0xf8851,  0xf8a46,  0xf8c3b,
      0xf8e4f,  0xf9044,  0xf9237,  0xf944a,  0xf963f,  0xf9853,  0xf9a47,  0xf9c3c,
      0xf9e50,  0xfa045,  0xfa238,  0xfa44c,  0xfa641,  0xfa836,  0xfaa49,  0xfac3d,
      0xfae52,  0xfb047,  0xfb23a,  0xfb44e,  0xfb643,  0xfb837,  0xfba4a,  0xfbc3f,
      0xfbe53,  0xfc048,  0xfc23c,  0xfc450,  0xfc645,  0xfc839,  0xfca4c,  0xfcc41,
      0xfce36,  0xfd04a,  0xfd23d,  0xfd451,  0xfd646,  0xfd83a,  0xfda4d,  0xfdc43,
      0xfde37,  0xfe04b,  0xfe23f,  0xfe453,  0xfe648,  0xfe83c,  0xfea4f,  0xfec44,
      0xfee38,  0xff04c,  0xff241,  0xff436,  0xff64a,  0xff83e,  0xffa51,  0xffc46,
      0xffe3a, 0x10004e, 0x100242, 0x100437, 0x10064b, 0x100841, 0x100a53, 0x100c48,
     0x100e3c, 0x10104f, 0x101244, 0x101438, 0x10164c, 0x101842, 0x101a35, 0x101c49,
     0x101e3d, 0x102051, 0x102245, 0x10243a, 0x10264e, 0x102843, 0x102a37, 0x102c4b,
     0x102e3f, 0x103053, 0x103247, 0x10343b, 0x10364f, 0x103845, 0x103a38, 0x103c4c,
     0x103e42, 0x104036, 0x104249, 0x10443d, 0x104651, 0x104846, 0x104a3a, 0x104c4e,
     0x104e43, 0x105038, 0x10524a, 0x10543e, 0x105652, 0x105847, 0x105a3b, 0x105c4f,
     0x105e45, 0x106039, 0x10624c, 0x106441, 0x106635, 0x106849, 0x106a3d, 0x106c51,
     0x106e47, 0x10703c, 0x10724f, 0x107444, 0x107638, 0x10784c, 0x107a3f, 0x107c53,
     0x107e48,
  );

  private const MIN_YEAR = 1900;

  private const MAX_YEAR = 2100;

  #===================================================================

  private static function ValidateYear($year) {
    if ($year < self::MIN_YEAR || $year > self::MAX_YEAR) {
      throw new Exception('Year must be within range 1900-2100');
    }
  }

  #===================================================================

  private static function GetBitInteger($data, $length, $shift) {
    return ($data & (((1 << $length) - 1) << $shift)) >> $shift;
  }

  #===================================================================

  /**
   * WARNING: Dates before Oct. 1582 are inaccurate
   *
   */
  private static function Gregorian2Integer($y, $m, $d) {
    $m = ($m + 9) % 12;
    $y = intval($y) - intval($m / 10);
    return intval(365 * $y + intval($y / 4) - intval($y / 100) + intval($y / 400) + intval(($m * 306 + 5) / 10) + ($d - 1));
  }

  #===================================================================

  private static function Integer2Gregorian($g) {
    $y = intval((10000 * intval($g) + 14780) / 3652425);
    $ddd = intval($g - (365 * $y + intval($y / 4) - intval($y / 100) + intval($y / 400)));
    if ($ddd < 0) {
      $y--;
      $ddd = intval($g - (365 * $y + intval($y / 4) - intval($y / 100) + intval($y / 400)));
    }
    $mi = intval((100 * $ddd + 52) / 3060);
    $mm = intval(($mi + 2) % 12 + 1);
    $y  = (integer) $y + intval(($mi + 2) / 12);
    $dd = intval($ddd - intval(($mi * 306 + 5) / 10) + 1);
    return array(
      'y' => $y,
      'm' => $mm,
      'd' => $dd
    );
  }

  #===================================================================

  /**
   * Converts Chinese lunisolar date to Gregorian date.
   *
   * @param string 'year-month-day'
   *        Examples:
   *               '2017-8-23'   with or without leading zeros
   *               '2017-L6-01'  leap month is idicated by 'L'
   */
  public static function Lunar2Gregorian($lunar) {

    list($lunarYear, $lunarMonth, $lunarDay) = explode('-', $lunar);
    $lunarYear = (integer) $lunarYear;
    self::ValidateYear($lunarYear);
    $lunarDay  = (integer) ltrim($lunarDay, '0');
    if (substr($lunarMonth, 0, 1) == 'L') {
      $leapMonth  = true;
      $lunarMonth = (integer) ltrim(substr($lunarMonth, 1), '0');
    }
    else {
      $leapMonth  = false;
      $lunarMonth = (integer) ltrim($lunarMonth, '0');
    }

    $days = self::$lunar_month_days[$lunarYear - self::$lunar_month_days[0]];
    $leap = self::GetBitInteger($days, 4, 13);
    $offset  = 0;
    $loopend = $leap;

    if (!$leapMonth) {
      if ($lunarMonth <= $leap || $leap == 0) {
        $loopend = $lunarMonth - 1;
      }
      else {
        $loopend = $lunarMonth;
      }
    }

    for ($i = 0; $i < $loopend; $i++) {
      $offset += self::GetBitInteger($days, 1, 12 - $i) == 1 ? 30 : 29;
    }

    $offset += $lunarDay;
    $gregorian11 = self::$gregorian_1_1[$lunarYear - self::$gregorian_1_1[0]];

    $y = self::GetBitInteger($gregorian11, 12, 9);
    $m = self::GetBitInteger($gregorian11,  4, 5);
    $d = self::GetBitInteger($gregorian11,  5, 0);

    return self::Integer2Gregorian(self::Gregorian2Integer($y, $m, $d) + $offset - 1);
  }

  #===================================================================

  /**
   * Converts Gregorian date to Chinese lunisolar date.
   *
   * @param string 'year-month-day'
   *        Examples:
   *               '2017-8-23'   with or without leading zeros
   *
   */
  public static function Gregorian2Lunar($greg) {

    list($gregYear, $gregMonth, $gregDay) = explode('-', $greg);
    $gregYear  = (integer) $gregYear;
    self::ValidateYear($gregYear);
    $gregMonth = (integer) ltrim($gregMonth, '0');
    $gregDay   = (integer) ltrim($gregDay, '0');

    $index = $gregYear - self::$gregorian_1_1[0];
    $data  = ($gregYear << 9) | ($gregMonth << 5) | ($gregDay);

    if (self::$gregorian_1_1[$index] > $data) {
      $index--;
    }

    $gregorian11 = self::$gregorian_1_1[$index];

    $y = self::GetBitInteger($gregorian11, 12, 9);
    $m = self::GetBitInteger($gregorian11,  4, 5);
    $d = self::GetBitInteger($gregorian11,  5, 0);

    $offset  = self::Gregorian2Integer($gregYear, $gregMonth, $gregDay) - self::Gregorian2Integer($y, $m, $d);
    $days    = self::$lunar_month_days[$index];
    $leap    = self::GetBitInteger($days, 4, 13);
    $lunarY  = $index + self::$gregorian_1_1[0];
    $lunarM  = 1;
    $offset += 1;

    for ($i = 0; $i < 13; $i++) {
      $dm = self::GetBitInteger($days, 1, 12 - $i) == 1 ? 30 : 29;
      if ($offset > $dm) {
        $lunarM++;
        $offset -= $dm;
      }
      else {
        break;
      }
    }

    $lunarD = intval($offset);
    $isleap = false;

    if ($leap != 0 && $lunarM > $leap) {
      $month = $lunarM - 1;
      if ($lunarM == $leap + 1) {
        $isleap = true;
      }
      $lunarM = $month;
    }

    if ($isleap) {
      $trad  = '閏'. self::ArabicMonth2hanzi($lunarM, true)  . self::ArabicDay2hanzi($lunarD);
      $simp  = '闰'. self::ArabicMonth2hanzi($lunarM, false) . self::ArabicDay2hanzi($lunarD);
      $latin = 'L'. str_pad($lunarM, 2, '0', STR_PAD_LEFT) .'-'. str_pad($lunarD, 2, '0', STR_PAD_LEFT);
    }
    else {
      $trad  = self::ArabicMonth2hanzi($lunarM, true)  . self::ArabicDay2hanzi($lunarD);
      $simp  = self::ArabicMonth2hanzi($lunarM, false) . self::ArabicDay2hanzi($lunarD);
      $latin = str_pad($lunarM, 2, '0', STR_PAD_LEFT) .'-'. str_pad($lunarD, 2, '0', STR_PAD_LEFT);
    }

    return array(
      'y'     => $lunarY,
      'm'     => $lunarM,
      'd'     => $lunarD,
      'leap'  => $isleap,
      'zh-cn' => $simp,
      'zh-hk' => $trad,
      'ja'    => $trad,
      'en'    => $latin,
    );
  }

  #===================================================================

  /**
   * https://en.wikipedia.org/wiki/Chinese_Zodiac
   * 获取干支纪年
   *
   */
  public static function GetLunarYearName($year, $trad = true) {
    $sky = array('庚', '辛', '壬', '癸', '甲', '乙', '丙', '丁', '戊', '己');
    if ($trad) {
      $earth = array('申', '酉', '戌', '亥', '子', '醜', '寅', '卯', '辰', '巳', '午', '未');
    }
    else {
      $earth = array('申', '酉', '戌', '亥', '子', '丑', '寅', '卯', '辰', '巳', '午', '未');
    }
    $year = $year .'';
    return $sky[$year{3}] . $earth[$year % 12];
  }

  #===================================================================

  public static function ArabicYear2hanzi($year) {
    $arr = array('零', '一', '二', '三', '四', '五', '六', '七', '八', '九');
    $year_arr = str_split($year);
    return $arr[$year_arr[0]] . $arr[$year_arr[1]] . $arr[$year_arr[2]] . $arr[$year_arr[3]];
  }

  #===================================================================

  /**
   * Converts Arabic numeral to Hanzi, Month
   *
   */
  public static function ArabicMonth2hanzi($int, $trad = true) {
    if ($trad) {
      $arr = array('', '正月', '二月', '三月', '四月', '五月', '六月', '七月', '八月', '九月', '十月', '冬月', '臘月');
    }
    else {
      $arr = array('', '正月', '二月', '三月', '四月', '五月', '六月', '七月', '八月', '九月', '十月', '冬月', '腊月');
    }
    return $arr[$int];
  }

  #===================================================================

  /**
   * Converts Arabic numeral to Hanzi, Day
   *
   */
  public static function ArabicDay2hanzi($int) {
    $arr = array('', '一', '二', '三', '四', '五', '六', '七', '八', '九', '十');
    if ($int <= 10) {
      return '初' . $arr[$int];
    }
    if ($int >10 && $int < 20) {
      return '十' . $arr[$int - 10];
    }
    if ($int == 20) {
      return '二十';
    }
    if ($int > 20 && $int < 30) {
      return '廿'. $arr[$int - 20];
    }
    if ($int == 30) {
      return '三十';
    }
    throw new Exception('Illegal vaule of argument int');
  }

  #===================================================================

  /**
   * https://en.wikipedia.org/wiki/Chinese_Zodiac
   *
   */
  public static function GetYearZodiac($year, $lang) {
    if ($lang == 'zh-hk') {
      $zodiac = array('猴', '雞', '狗', '豬', '鼠', '牛', '虎', '兔', '龍', '蛇', '馬', '羊');
    }
    elseif ($lang == 'zh-cn') {
      $zodiac = array('猴', '鸡', '狗', '猪', '鼠', '牛', '虎', '兔', '龙', '蛇', '马', '羊');
    }
    elseif ($lang == 'ja') {
      $zodiac = array('モンキー', 'チキン', '犬', '豚', 'ラット', '牛', 'タイガー', 'ラビット', 'ドラゴン', 'ヘビ', '馬', '羊');
    }
    else {
      $zodiac = array('monkey', 'rooster', 'dog', 'pig', 'rat', 'ox', 'tiger', 'rabbit', 'dragon', 'snake', 'horse', 'goat');
    }
    return $zodiac[$year % 12];
  }

  #===================================================================

  public static function GetZodiacEmoji($year) {
    $emoji = array('🐒', '🐔', '🐕', '🐖', '🐀', '🐂', '🐅', '🐇', '🐲', '🐍', '🐎', '🐐');
    return $emoji[$year % 12];
  }

  #===================================================================

  public static function GetZodiacGreeting($year, $lang) {
    if ($lang == 'zh-hk') {
      $greeting = array(
        '猴子年快樂！',
        '雞年快樂！',
        '狗年快樂！',
        '豬年快樂！',
        '鼠年快樂！',
        '牛年快樂！',
        '虎年快樂！',
        '兔子年快樂！',
        '龍年快樂！',
        '蛇年快樂！',
        '馬年快樂！',
        '羊年快樂！',
      );
    }
    elseif ($lang == 'zh-cn') {
      $greeting = array(
        '猴子年快乐！',
        '鸡年快乐！',
        '狗年快乐！',
        '猪年快乐！',
        '鼠年快乐！',
        '牛年快乐！',
        '虎年快乐！',
        '兔子年快乐！',
        '龙年快乐！',
        '蛇年快乐！',
        '马年快乐！',
        '羊年快乐！',
      );
    }
    elseif ($lang == 'ja') {
      $greeting = array(
        'ハッピーモンキーイヤー！',
        '酉のハッピーイヤー！',
        '犬のハッピーイヤー！',
        '豚のハッピーイヤー！',
        'ラットのハッピーイヤー！',
        '丑のハッピーイヤー！',
        'タイガーのハッピーイヤー！',
        'ハッピーラビットイヤー！',
        'ドラゴンのハッピーイヤー！',
        '蛇のハッピーイヤー！',
        '馬のハッピーイヤー！',
        'ヤギのハッピーイヤー！',
      );
    }
    else {
      $greeting = array(
        'Happy year of the Monkey!',
        'Happy year of the Rooster!',
        'Happy year of the Dog!',
        'Happy year of the Pig!',
        'Happy year of the Rat!',
        'Happy year of the Ox!',
        'Happy year of the Tiger!',
        'Happy year of the Rabbit!',
        'Happy year of the Dragon!',
        'Happy year of the Snake!',
        'Happy year of the Horse!',
        'Happy year of the Goat!',
      );
    }
    return $greeting[$year % 12];
  }

  #===================================================================
}