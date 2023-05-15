<?php


namespace App\Models\Jatelindo;

use Andromeda\ISO8583\Contracts\IsoMessageContract;
use Andromeda\ISO8583\MessageDefinition;

class AuthJatelindo extends MessageDefinition implements IsoMessageContract
{
    public function getIso(): array
    {
        // return [
        //     7   => ['an',10,self::FIXED_LENGTH],
        //     11  => ['n',6,self::FIXED_LENGTH],
        //     33  => ['n',11,self::VARIABLE_LENGTH],
        //     39  => ['an',2,self::FIXED_LENGTH],
        //     42  => ['n',15,self::FIXED_LENGTH],
        //     70  => ['n',3,self::FIXED_LENGTH],
        // ];

        return [
            7   => ['an',10,self::FIXED_LENGTH],
            11  => ['n',6,self::FIXED_LENGTH],
            33  => ['n',11,self::VARIABLE_LENGTH],
            39  => ['an',2,self::FIXED_LENGTH],
            42  => ['n',15,self::FIXED_LENGTH],
            70  => ['n',3,self::FIXED_LENGTH],
        ];
    }

}