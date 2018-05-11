<?php
/*
 * BKAV eHoaDon API client in PHP
 *
 * A PHP library to handle authentication and communication with BKAV eHoaDon API
 * Copyright (C) 2018 Tin Hoc Tai Nha <tinhoctainha.com>
 */
namespace BKAV;

use BKAV\Convertor;


class Result
{
    const Ok = 0;
    const Error = 1;

    public $Status = 0;

    public $Object = (object)[];

    public function Result($Status, $obj, $ConvertToBase64 = false)
    {
      $this->Status = Status;
      if ($ConvertToBase64)
      {
        $str = "null";
        Convertor::ObjectToBase64String($obj, &$str);
        $this->Object = (object) $str;
      }
      else
        $this->Object = $obj;
    }

    public static function ResultOk()
    {
      return self::GetResultOk(null, false);
    }

    public static function GetResultOk($obj = null, $ConvertToBase64 = false)
    {
      return self::Result(0, $obj, $ConvertToBase64);
    }

    public static function GetResultError($obj)
    {
      return self::Result(1, $obj, false);
    }

    public static function GetResult($Status, $obj, $ConvertToBase64 = false)
    {
      return self::Result($Status, $obj, $ConvertToBase64);
    }

    public function isOk()
    {
      return $this->Status == 0;
    }

    public function isError()
    {
      return $this->Status == 1;
    }
}