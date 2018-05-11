<?php
/*
 * BKAV eHoaDon API client in PHP
 *
 * A PHP library to handle authentication and communication with BKAV eHoaDon API
 * Copyright (C) 2018 Tin Hoc Tai Nha <tinhoctainha.com>
 */
namespace BKAV;


class CommandData
{
    public $CommandType = 0;

    public $CommandObject = (object)[];

    public $CmdType = 0;

    public function CommandData($CommandType, $CommandObject)
    {
      $this->CommandType = $CommandType;
      $this->CommandObject = $CommandObject;
    }

    public function ToString()
    {
        $result = [
            "CommandType" => $this->CommandType,
            "CommandObject" => json_encode($this->CommandObject)
        ];
        return json_encode($result);
    }
}