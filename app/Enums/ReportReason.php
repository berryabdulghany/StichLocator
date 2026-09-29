<?php

namespace App\Enums;

/**
 * Alasan pengguna melaporkan sebuah ulasan.
 */
enum ReportReason: string
{
    case Spam = 'spam';
    case Offensive = 'offensive';
    case Irrelevant = 'irrelevant';
    case Fake = 'fake';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Spam => __('Spam or advertising'),
            self::Offensive => __('Offensive or inappropriate'),
            self::Irrelevant => __('Not about this tailor'),
            self::Fake => __('Looks fake'),
            self::Other => __('Other'),
        };
    }
}
