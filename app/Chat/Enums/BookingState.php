<?php

namespace App\Chat\Enums;

final class BookingState
{
    public const INITIAL = 'initial';
    public const AWAITING_LOCATION = 'awaiting_location';
    public const AWAITING_LOCATION_CHOICE = 'awaiting_location_choice';
    public const AWAITING_DATE_TIME = 'awaiting_date_time';
    public const AWAITING_NAME = 'awaiting_name';
    public const AWAITING_PHONE = 'awaiting_phone';
    public const AWAITING_CONFIRMATION = 'awaiting_confirmation';
    public const AWAITING_CORRECTION = 'awaiting_correction';
    public const COMPLETED = 'completed';
}
