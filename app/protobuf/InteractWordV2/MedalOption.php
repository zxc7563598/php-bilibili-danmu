<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# NO CHECKED-IN PROTOBUF GENCODE
# source: INTERACT_WORD_V2.proto

namespace app\Protobuf\InteractWordV2;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>MedalOption</code>
 */
class MedalOption extends \Google\Protobuf\Internal\Message
{
    /**
     * Generated from protobuf field <code>.MedalType typ = 1;</code>
     */
    protected $typ = 0;
    /**
     * Generated from protobuf field <code>int64 room_id = 2;</code>
     */
    protected $room_id = 0;
    /**
     * Generated from protobuf field <code>bool need_guard = 3;</code>
     */
    protected $need_guard = false;
    /**
     * Generated from protobuf field <code>bool strong_depend = 4;</code>
     */
    protected $strong_depend = false;
    /**
     * Generated from protobuf field <code>bool need_group = 5;</code>
     */
    protected $need_group = false;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type int $typ
     *     @type int|string $room_id
     *     @type bool $need_guard
     *     @type bool $strong_depend
     *     @type bool $need_group
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\INTERACTWORDV2::initOnce();
        parent::__construct($data);
    }

    /**
     * Generated from protobuf field <code>.MedalType typ = 1;</code>
     * @return int
     */
    public function getTyp()
    {
        return $this->typ;
    }

    /**
     * Generated from protobuf field <code>.MedalType typ = 1;</code>
     * @param int $var
     * @return $this
     */
    public function setTyp($var)
    {
        GPBUtil::checkEnum($var, \app\Protobuf\InteractWordV2\MedalType::class);
        $this->typ = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>int64 room_id = 2;</code>
     * @return int|string
     */
    public function getRoomId()
    {
        return $this->room_id;
    }

    /**
     * Generated from protobuf field <code>int64 room_id = 2;</code>
     * @param int|string $var
     * @return $this
     */
    public function setRoomId($var)
    {
        GPBUtil::checkInt64($var);
        $this->room_id = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>bool need_guard = 3;</code>
     * @return bool
     */
    public function getNeedGuard()
    {
        return $this->need_guard;
    }

    /**
     * Generated from protobuf field <code>bool need_guard = 3;</code>
     * @param bool $var
     * @return $this
     */
    public function setNeedGuard($var)
    {
        GPBUtil::checkBool($var);
        $this->need_guard = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>bool strong_depend = 4;</code>
     * @return bool
     */
    public function getStrongDepend()
    {
        return $this->strong_depend;
    }

    /**
     * Generated from protobuf field <code>bool strong_depend = 4;</code>
     * @param bool $var
     * @return $this
     */
    public function setStrongDepend($var)
    {
        GPBUtil::checkBool($var);
        $this->strong_depend = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>bool need_group = 5;</code>
     * @return bool
     */
    public function getNeedGroup()
    {
        return $this->need_group;
    }

    /**
     * Generated from protobuf field <code>bool need_group = 5;</code>
     * @param bool $var
     * @return $this
     */
    public function setNeedGroup($var)
    {
        GPBUtil::checkBool($var);
        $this->need_group = $var;

        return $this;
    }

}

