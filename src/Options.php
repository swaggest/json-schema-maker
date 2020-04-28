<?php


namespace Swaggest\JsonSchemaMaker;


class Options
{
    // SchemaMaker options.

    /** @var string place to put new definitions */
    public $defsPtr = '#/definitions/';

    /** @var bool use `type: number` instead of `type: [integer, number]` */
    public $upgradeIntToNumber = true;

    /** @var bool add property to `required` if it is present in all samples */
    public $heuristicRequired = false;

    /** @var bool set `x-nullable: true` instead of `type: [null, T]` for compatibility with Swagger 2.0  */
    public $useXNullable = false;

    /** @var bool set `nullable: true` instead of `type: [null, T]` for compatibility with Swagger 2.0  */
    public $useNullable = false;

    /** @var bool set `example: <value>` for numbers and strings */
    public $collectExamples = false;

    // InstanceFaker options.

    /** @var bool treat non-existent `additionalProperties` as `additionalProperties: true` in InstanceFaker */
    public $defaultAdditionalProperties = true;

    /** @var int limit nesting depth in InstanceFaker */
    public $maxNesting = 10;
}