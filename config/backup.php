<?php

use Spatie\Backup\Notifications\Notifiable;
use Spatie\Backup\Notifications\Notifications\BackupHasFailedNotification;
use Spatie\Backup\Notifications\Notifications\BackupWasSuccessfulNotification;
use Spatie\Backup\Notifications\Notifications\CleanupHasFailedNotification;
use Spatie\Backup\Notifications\Notifications\CleanupWasSuccessfulNotification;
use Spatie\Backup\Notifications\Notifications\HealthyBackupWasFoundNotification;
use Spatie\Backup\Notifications\Notifications\UnhealthyBackupWasFoundNotification;
use Spatie\Backup\Tasks\Cleanup\Strategies\DefaultStrategy;
use Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumAgeInDays;
use Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumStorageInMegabytes;

return [

    'backup' => [
        /*
         * اسم التطبيق - سيستخدم في تسمية ملفات النسخ الاحتياطي
         */
        'name' => env('APP_NAME', 'fanana-phone'),

        'source' => [
            'files' => [
                /*
                 * الملفات والمجلدات التي سيتم تضمينها في النسخة الاحتياطية
                 */
                'include' => [
                    base_path('public/storage'),
                    base_path('storage/app/public'),
                ],

                /*
                 * الملفات والمجلدات المستثناة من النسخة الاحتياطية
                 */
                'exclude' => [
                    base_path('vendor'),
                    base_path('node_modules'),
                    base_path('storage/framework'),
                    base_path('storage/logs'),
                    base_path('.env'),
                    base_path('.git'),
                ],

                'follow_links' => false,

                'ignore_unreadable_directories' => false,

                'relative_path' => null,
            ],

            /*
             * قواعد البيانات التي سيتم نسخها احتياطياً
             */
            'databases' => [
                env('DB_CONNECTION', 'mysql'),
            ],
        ],

        /*
         * ضغط ملفات قاعدة البيانات لتقليل حجمها
         */
        'database_dump_compressor' => null,

        'database_dump_file_timestamp_format' => null,

        'database_dump_filename_base' => 'database',

        'database_dump_file_extension' => '',

        'destination' => [
            'compression_method' => defined('ZipArchive::CM_DEFAULT') ? ZipArchive::CM_DEFAULT : 0,

            'compression_level' => 9,

            'filename_prefix' => 'fanana-backup-',

            /*
             * الأقراص التي سيتم تخزين النسخ الاحتياطية عليها
             */
            'disks' => [
                'local',
            ],

            'continue_on_failure' => false,
        ],

        /*
         * المجلد المؤقت لتخزين الملفات أثناء عملية النسخ
         */
        'temporary_directory' => storage_path('app/backup-temp'),

        /*
         * تشفير ملفات النسخ الاحتياطي - معطل حالياً
         */
        'password' => null,

        'encryption' => 'none',

        'verify_backup' => false,

        'tries' => 1,

        'retry_delay' => 0,
    ],

    /*
     * إعدادات الإشعارات - معطلة حالياً لعدم وجود بريد إلكتروني
     */
    'notifications' => [
        'notifications' => [
            BackupHasFailedNotification::class => [],
            UnhealthyBackupWasFoundNotification::class => [],
            CleanupHasFailedNotification::class => [],
            BackupWasSuccessfulNotification::class => [],
            HealthyBackupWasFoundNotification::class => [],
            CleanupWasSuccessfulNotification::class => [],
        ],

        'notifiable' => Notifiable::class,

        'mail' => [
            'to' => 'owner@fanana-phone.local',

            'from' => [
                'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
                'name' => env('MAIL_FROM_NAME', 'فنانة فون'),
            ],
        ],

        'slack' => [
            'webhook_url' => '',
            'channel' => null,
            'username' => null,
            'icon' => null,
        ],

        'discord' => [
            'webhook_url' => '',
            'username' => '',
            'avatar_url' => '',
        ],

        'webhook' => [
            'url' => '',
        ],
    ],

    /*
     * قناة السجلات - استخدام القناة الافتراضية
     */
    'log_channel' => null,

    /*
     * مراقبة صحة النسخ الاحتياطية
     */
    'monitor_backups' => [
        [
            'name' => env('APP_NAME', 'fanana-phone'),
            'disks' => ['local'],
            'health_checks' => [
                MaximumAgeInDays::class => 2,
                MaximumStorageInMegabytes::class => 5000,
            ],
        ],
    ],

    /*
     * تنظيف النسخ القديمة
     */
    'cleanup' => [
        'strategy' => DefaultStrategy::class,

        'default_strategy' => [
            'keep_all_backups_for_days' => 7,

            'keep_daily_backups_for_days' => 16,

            'keep_weekly_backups_for_weeks' => 8,

            'keep_monthly_backups_for_months' => 4,

            'keep_yearly_backups_for_years' => 2,

            'delete_oldest_backups_when_using_more_megabytes_than' => 5000,
        ],

        'tries' => 1,

        'retry_delay' => 0,
    ],
];
