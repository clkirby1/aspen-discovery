<?php


class HooplaRecordUsage extends DataObject
{
    public $__table = 'hoopla_record_usage';
    public $id;
    public $hooplaId;
    public $year;
    public $month;
    public $timesHeld;
    public $timesCheckedOut;
}