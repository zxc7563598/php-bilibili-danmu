<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# NO CHECKED-IN PROTOBUF GENCODE
# source: INTERACT_WORD_V2.proto

namespace app\Protobuf\InteractWordV2;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>RiskCtrlInfo</code>
 */
class RiskCtrlInfo extends \Google\Protobuf\Internal\Message
{
    /**
     * Generated from protobuf field <code>string name = 1;</code>
     */
    protected $name = '';
    /**
     * Generated from protobuf field <code>string face = 2;</code>
     */
    protected $face = '';

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $name
     *     @type string $face
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\INTERACTWORDV2::initOnce();
        parent::__construct($data);
    }

    /**
     * Generated from protobuf field <code>string name = 1;</code>
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Generated from protobuf field <code>string name = 1;</code>
     * @param string $var
     * @return $this
     */
    public function setName($var)
    {
        GPBUtil::checkString($var, True);
        $this->name = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>string face = 2;</code>
     * @return string
     */
    public function getFace()
    {
        return $this->face;
    }

    /**
     * Generated from protobuf field <code>string face = 2;</code>
     * @param string $var
     * @return $this
     */
    public function setFace($var)
    {
        GPBUtil::checkString($var, True);
        $this->face = $var;

        return $this;
    }

}

