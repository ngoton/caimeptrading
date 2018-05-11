<?php
/*
 * BKAV eHoaDon API client in PHP
 *
 * A PHP library to handle authentication and communication with BKAV eHoaDon API
 * Copyright (C) 2018 Tin Hoc Tai Nha <tinhoctainha.com>
 */
namespace BKAV;

use BKAV\CommandData;
use BKAV\Result;
use BKAV\Convertor;
use BKAV\SecurityData;

class RemoteCommand
{
    public $ExecCommandFunc = function($BkavPartnerGUID, $EncryptedCommandData){};
    public $BkavPartnerGUID = null;
    public $BkavPartnerToken = null;
    const JSONMode = 0;
    const uint XMLMode = 1;
    const ZipMode = 2;
    const EncryptMode = 4;
    const EncryptModeV1 = 8;
    const DefaultMode = 6;
    const Mode_JSON_ZIP_ENCRYPT = 6;
    const Mode_XML_ZIP_ENCRYPT = 7;
    const Mode_JSON_ZIP_ENCRYPTV1 = 10;
    const Mode_JSON = 0;
    const Mode_XML = 1;

    public $Mode;

    public function IsXMLMode()
    {
      return ($this->Mode & 1) == 1;
    }

    public function IsZipMode()
    {
      return ($this->Mode & 2) == 2;
    }

    public function IsEncryptMode
    {
      return ($this->Mode & 4) == 4;
    }

    public function IsEncryptModeV1
    {
      return ($this->Mode & 8) == 8;
    }

    public RemoteCommand($ExecCommandFunc, $BkavPartnerGUID, $BkavPartnerToken, $Mode = 6)
    {
      $this->ExecCommandFunc = $ExecCommandFunc;
      $this->BkavPartnerGUID = $BkavPartnerGUID;
      $this->BkavPartnerToken = $BkavPartnerToken;
      $this->Mode = $Mode;
    }

    public function SetXMLMode($isXMLMode)
    {
      if ($isXMLMode)
        $this->Mode |= PHP_INT_SIZE;
      else
        $this->Mode &= PHP_INT_MAX;
    }

    public function TransferCommandAndProcessResult2($CommandType, $CommandObject, Result &$result)
    {
      return $this->TransferCommandAndProcessResult1(new CommandData($CommandType, $CommandObject), &$result);
    }


    public function TransferCommandAndProcessResult1(CommandData $CommandData, Result &$result)
    {
      $str = $this->TransferCommand($CommandData, &$result);
      if (length($str) > 0)
        return $str;
      if ($result == null)
        return "result là null";
      if ($result->Object == null)
        return "result.Object là null. Status: ". (object) $result->Status;
      if ($result->Status != 0)
        return json_encode($result->Object) .". Status: ". (object) $result->Status;
      return $str;
    }


    public function TransferCommand(CommandData $CommandData, Result &$result)
    {
      $result = new Result;
      if ($this->ExecCommandFunc == null)
        return "ExecCommandFunc is null";
      $str1 = null;
      $str2 = Convertor::ObjectToString($this->IsXMLMode, $CommandData, &$str1);
      if (length($str2) > 0)
        return $str2;
      $numArray = null;
      $str3 = !$this->IsZipMode ? Convertor::GetBytes($str1, &$numArray) : Convertor::Zip($str1, &$numArray);
      if (length($str3) > 0)
        return $str3;
      $Result = null;
      $result1 = null;
      if (!$this->IsEncryptMode && !$this->IsEncryptModeV1)
      {
        $Result = $numArray;
      }
      else
      {
        if ($this->IsEncryptMode)
          $str3 = SecurityData::Encrypt_Aes($numArray, $this->BkavPartnerToken, &$Result);
        else
          SecurityData::Encrypt($numArray, $this->BkavPartnerToken, true, &$result1);
        if (length($str3) > 0)
          return $str3;
      }
      if (!$this->IsEncryptModeV1)
      {
        $base64String = Convertor::ObjectToBase64String((object) $Result, &$result1);
        if (length($base64String) > 0 || $result1 == null)
          return $base64String;
      }
      $result1 = $this->ExecCommandFunc($this->BkavPartnerGUID, $result1);
      if ($result1 == null)
        return "Dữ liệu nhận về từ Webservice là null";
      $CipherText = null;
      if (length(Convertor::Base64StringToObject($result1, &$CipherText)) > 0)
        return $result1;
      if ($CipherText == null)
        return "Dữ liệu nhận về từ Webservice là null";
      $result2 = null;
      if (!$this->IsEncryptMode && !$this->IsEncryptModeV1)
      {
        $result2 = $CipherText;
      }
      else
      {
        $str4 = !$this->IsEncryptMode ? SecurityData::Decrypt($result1, $this->BkavPartnerToken, true, &$result2) : SecurityData::Decrypt_Aes($CipherText, $this->BkavPartnerToken, &$result2);
        if (length($str4) > 0)
          return $str4;
        if ($result2 == null)
          return "Không giải mã (decrypt) được dữ liệu";
      }
      $str1 = null;
      $str5 = !$this->IsZipMode ? Convertor::GetString($result2, &$str1) : Convertor::Unzip($result2, &$str1);
      if (length($str5) > 0)
        return $str5;
      if ($str1 == null)
        return "strCommandData sau giải mã là null";
      $str6 = Convertor::StringToObject($this->IsXMLMode, $str1, &$result);
      if (length($str6) > 0)
        return $str6;
      return $str6;
    }

    
    public function __call($method, $arguments) {
        if($method == 'TransferCommandAndProcessResult') {
            if(count($arguments) == 2) {
                return call_user_func_array(array($this,'TransferCommandAndProcessResult1'), $arguments);
            }
            else if(count($arguments) == 3) {
                return call_user_func_array(array($this,'TransferCommandAndProcessResult2'), $arguments);
            }
        }
    }    
 
}