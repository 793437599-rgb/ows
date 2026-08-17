<?php


namespace app\common\enum;


use MyCLabs\Enum\Enum;

class DocumentTypeEnum extends Enum
{
    private $grop = [
        'Identity Documents',
        'Residency Documents',
        'Other Documents',
        'Consent Documents',
    ];

    private $identity = [
        'ID Card/Passport',
        'TaxID',
        'Driving License',
        'Other'
    ];

    private $residency = [
        'VISA',
        'Redidency Permit',
        'Refugee Status Certificate',
        'Other'
    ];

    private $other = [
        'Marriage Certificate',
        'Student Card',
        'Other'
    ];

    private $consent = [
        'Consent form',
        'Other'
    ];
}