<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
    ];

    protected $casts = [
        'value' => 'json',
    ];

    /**
     * الحصول على قيمة إعداد
     */
    public static function get($key, $default = null)
    {
        $setting = self::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    /**
     * تعيين قيمة إعداد
     */
    public static function set($key, $value, $type = 'string', $group = 'general')
    {
        return self::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'type' => $type,
                'group' => $group,
            ]
        );
    }

    /**
     * الحصول على جميع الإعدادات
     */
    public static function getAll()
    {
        return self::all()->pluck('value', 'key')->toArray();
    }

    /**
     * الحصول على إعدادات مجموعة معينة
     */
    public static function getGroup($group)
    {
        return self::where('group', $group)->get()->pluck('value', 'key')->toArray();
    }
}
