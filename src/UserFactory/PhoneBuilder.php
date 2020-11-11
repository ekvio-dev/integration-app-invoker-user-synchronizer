<?php

declare(strict_types=1);

namespace Ekvio\Integration\Invoker\UserFactory;

/**
 * Class PhoneBuilder
 * @package Ekvio\Integration\Invoker\UserFactory
 */
class PhoneBuilder
{
    /**
     * @param string $number
     * @return string|null
     */
    public static function build(string $number): ?string
    {
        $phone = (string) preg_replace('/[^0-9]/', '', $number);
        if (empty($phone)) {
            return null;
        }

        $symbols = strlen($phone);
        if ($symbols === 10) {
            $first = $phone[0];
            if ($first === '9') {
                return  '7' . $phone;
            }

            return $phone;
        }

        if ($symbols === 11) {
            $first = $phone[0];
            if ($first === '8') {
                return  substr_replace($phone, '7', 0, 1);
            }
            return $phone;
        }

        return $phone;
    }
}
