<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# NO CHECKED-IN PROTOBUF GENCODE
# source: INTERACT_WORD_V2.proto

namespace app\Protobuf\InteractWordV2;

use UnexpectedValueException;

/**
 * Protobuf type <code>HaveMedalType</code>
 */
class HaveMedalType
{
    /**
     * Generated from protobuf enum <code>Medal_Common = 0;</code>
     */
    const Medal_Common = 0;
    /**
     * Generated from protobuf enum <code>Medal_Group = 1;</code>
     */
    const Medal_Group = 1;

    private static $valueToName = [
        self::Medal_Common => 'Medal_Common',
        self::Medal_Group => 'Medal_Group',
    ];

    public static function name($value)
    {
        if (!isset(self::$valueToName[$value])) {
            throw new UnexpectedValueException(sprintf(
                    'Enum %s has no name defined for value %s', __CLASS__, $value));
        }
        return self::$valueToName[$value];
    }


    public static function value($name)
    {
        $const = __CLASS__ . '::' . strtoupper($name);
        if (!defined($const)) {
            throw new UnexpectedValueException(sprintf(
                    'Enum %s has no value defined for name %s', __CLASS__, $name));
        }
        return constant($const);
    }
}

