<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Support\Facades\Cache;

/**
 * ClearsLayoutCache
 * 
 * ضع هذا الـ Trait في أي Controller يحفظ أو يعدل بيانات.
 * استدعِ clearLayoutCache() بعد أي store / update / destroy.
 * 
 * هذا يضمن أن الإشعارات وعدادات لوحة التحكم دائماً محدَّثة
 * بدون إعادة حساب في كل طلب.
 */
trait ClearsLayoutCache
{
    /**
     * امسح كاش لوحة التحكم حتى يتحدث عدد الإشعارات فوراً
     */
    protected function clearLayoutCache(): void
    {
        Cache::forget('app_layout_alerts');
    }
}
