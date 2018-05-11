<?php
/*
 * BKAV eHoaDon API client in PHP
 *
 * A PHP library to handle authentication and communication with BKAV eHoaDon API
 * Copyright (C) 2018 Tin Hoc Tai Nha <tinhoctainha.com>
 */
namespace BKAV;


class Convertor
{
    public static function JsonToObject($data, &$obj)
    {
      
    }

    public static function ObjectToJson($obj, &$value)
    {
      
    }

    public static function ObjectToXML($obj, $prefixXML, $namespaceXML, &$xml)
    {
      
    }

    public static function ObjectToXML($obj, $prefixXML, $namespaceXML, &$xml)
    {
      
    }

    public static function XMLToObject($xml, &$obj)
    {
      
    }

    public static function StringToObject($stringData, &$obj)
    {
      
    }

    public static function StringToObject($isXML, $stringData, &$obj)
    {
      
    }

    public static function ObjectToString($objectData, &$value)
    {
      
    }

    public static function ObjectToString($isXML, $objectData, &$value)
    {
      
    }

    public static function ObjectToBase64String($obj, &$value)
    {
      $value = null;
      if ($obj == null)
        return "Error: Object is null @ObjectToBase64String";
      $value = base64_encode(serialize($obj));
    }

    public static function ObjectToBase64String($obj)
    {
      
    }

    public static function ObjectToBytes($obj)
    {
      
    }

    public static function Base64StringToObject($base64, &$value)
    {
      
    }

    public static function Base64StringToObject($base64, &$value)
    {
      
    }

    public static function Base64StringToObject($base64)
    {
      
    }

    public static function BytesToObject($bytes)
    {
      
    }

    public static function StringToGuid($guid, &$value)
    {
      
    }

    public static function ObjectToGuid($guid, &$value)
    {
      
    }

    public static function GetBytes($data, &$bytes)
    {
      
    }

    public static function GetString($bytes, &$data)
    {
      
    }

    public static function CopyTo($src, $dest)
    {
      
    }

    public static function Zip($str, &$zippedData)
    {
      
    }

    public static function Zip($obj, &$zippedData)
    {
      
    }

    public static function Zip($data, &$zippedData)
    {
      
    }

    public static function Unzip($bytes, &$unzippedData)
    {
      
    }

    public static function Unzip($bytes, &$obj)
    {
      
    }

    public static function Unzip($bytes, &$unzippedBytes)
    {
      
    }

}