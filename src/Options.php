<?php


namespace Swaggest\JsonSchemaMaker;


class Options
{
    /** @var string place to put new definitions */
    public $defsPtr = '#/definitions/';

    /** @var bool use `type: number` instead of `type: [integer, number]` */
    public $upgradeIntToNumber = true;

    /** @var bool set `x-nullable: true` instead of `type: [null, T]` for compatibility with Swagger 2.0  */
    public $useXNullable = false;

    /** @var bool set `nullable: true` instead of `type: [null, T]` for compatibility with Swagger 2.0  */
    public $useNullable = false;
}