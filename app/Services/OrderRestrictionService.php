<?php

namespace App\Services;

use App\Models\GeneralSetting;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * অ্যাডমিন প্যানেলের "Order Restriction Settings" (general_settings.order_limit_time /
 * order_limit_qty) এতদিন শুধু সেভ হতো, কোথাও পড়া হতো না। এই সার্ভিসটি চেকআউটে
 * সেই লিমিট কার্যকর করে — একই ফোন নম্বর বা একই IP থেকে নির্দিষ্ট সময়ের মধ্যে
 * নির্দিষ্ট সংখ্যার বেশি অর্ডার আটকে দেয়।
 */
class OrderRestrictionService
{
    /**
     * লিমিট অতিক্রম হলে বাংলা এরর মেসেজ, নাহলে null।
     */
    public function violationMessage(?string $phone, ?string $ipAddress): ?string
    {
        try {
            [$hours, $maxOrders] = $this->limits();

            if ($hours === null || $maxOrders === null) {
                return null;
            }

            $since = Carbon::now()->subHours($hours);

            $phone = trim((string) $phone);
            if ($phone !== '') {
                $phoneCount = Order::where('created_at', '>=', $since)
                    ->whereHas('shipping', fn ($q) => $q->where('phone', $phone))
                    ->count();

                if ($phoneCount >= $maxOrders) {
                    return $this->message($maxOrders, $hours);
                }
            }

            $ipAddress = trim((string) $ipAddress);
            if ($ipAddress !== '') {
                $ipCount = Order::where('created_at', '>=', $since)
                    ->where('ip_address', $ipAddress)
                    ->count();

                if ($ipCount >= $maxOrders) {
                    return $this->message($maxOrders, $hours);
                }
            }

            return null;
        } catch (\Throwable $e) {
            // রেস্ট্রিকশন চেক ব্যর্থ হলে অর্ডার আটকানো যাবে না — বিক্রি বন্ধ হওয়ার
            // চেয়ে একটা বাড়তি অর্ডার নেওয়াই ভালো।
            Log::error('Order restriction check failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * @return array{0: int|null, 1: int|null} [hours, maxOrders]
     */
    private function limits(): array
    {
        $setting = GeneralSetting::where('status', 1)->first() ?: GeneralSetting::first();

        if (!$setting) {
            return [null, null];
        }

        $hours     = (int) ($setting->order_limit_time ?? 0);
        $maxOrders = (int) ($setting->order_limit_qty ?? 0);

        // ০ বা ফাঁকা মানে ফিচারটি বন্ধ।
        if ($hours < 1 || $maxOrders < 1) {
            return [null, null];
        }

        return [$hours, $maxOrders];
    }

    private function message(int $maxOrders, int $hours): string
    {
        return 'আপনি ইতিমধ্যে গত ' . $this->bn($hours) . ' ঘণ্টায় ' . $this->bn($maxOrders)
            . 'টি অর্ডার করেছেন। নতুন অর্ডারের জন্য কিছুক্ষণ পরে চেষ্টা করুন অথবা আমাদের সাথে যোগাযোগ করুন।';
    }

    private function bn(int $number): string
    {
        return str_replace(
            ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'],
            ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'],
            (string) $number
        );
    }
}
