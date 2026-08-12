<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class CustomPrintRequest extends ObjectModel
{
    public $id_customer;
    public $firstname;
    public $lastname;
    public $email;
    public $phone;
    public $blank;
    public $placement;
    public $method;
    public $size;
    public $quantity = 1;
    public $estimated_total = 0;
    public $brief;
    public $artwork_path;
    public $status = 'new';
    public $date_add;
    public $date_upd;

    public static $definition = [
        'table' => 'mycustomprint_request',
        'primary' => 'id_mycustomprint_request',
        'fields' => [
            'id_customer' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'allow_null' => true],
            'firstname' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 255],
            'lastname' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 255],
            'email' => ['type' => self::TYPE_STRING, 'validate' => 'isEmail', 'required' => true, 'size' => 255],
            'phone' => ['type' => self::TYPE_STRING, 'validate' => 'isPhoneNumber', 'allow_null' => true, 'size' => 32],
            'blank' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 64],
            'placement' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 64],
            'method' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 64],
            'size' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'allow_null' => true, 'size' => 16],
            'quantity' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true],
            'estimated_total' => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice', 'required' => true],
            'brief' => ['type' => self::TYPE_HTML, 'validate' => 'isCleanHtml', 'allow_null' => true],
            'artwork_path' => ['type' => self::TYPE_STRING, 'allow_null' => true, 'size' => 255],
            'status' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 32],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
        ],
    ];
}
