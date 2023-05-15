<?php

namespace App\Services\External;

use Andromeda\ISO8583\Parser;
use App\Models\Jatelindo\AuthJatelindo;

class JatelindoService
{
    public static function signIn()
    {
        $message = new AuthJatelindo();
        $isoMaker = new Parser($message);
        $isoMaker->addMTI('0800');
        $isoMaker->addData(7, date('Ymdhms'));
        $isoMaker->addData(11, '123456');
        $isoMaker->addData(33, '1');
        $isoMaker->addData(39, '00');
        $isoMaker->addData(42, 'abc123');
        $isoMaker->addData(70, '301');
        $isoMaker->getISO();

        return $isoMaker->getISO();
    }
}