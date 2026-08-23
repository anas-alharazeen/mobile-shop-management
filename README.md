# فنانة فون

نظام عربي متكامل لإدارة معرض بيع وصيانة الهواتف الذكية، مبني كتطبيق واحد باستخدام **Laravel 13 + Vue 3 + Inertia.js + Tailwind CSS 3 + MySQL**.

## الوحدات المكتملة

- لوحة تحكم فعلية ومؤشرات ومخططات.
- الفئات والمنتجات والصور.
- مخزون المبيعات ومخزون الصيانة وحركات المخزون.
- الجرد والتسويات المخزنية.
- الموردون والمشتريات وفواتير الشراء والدفعات.
- العملاء ونقطة البيع وفواتير المبيعات والدفع المختلط والآجل.
- صيانة الأجهزة وقطع الغيار والدفعات والطباعة.
- مركز الدفعات والديون وكشوف حساب العملاء والموردين.
- الحسابات المالية والمصروفات والحركات والإغلاق اليومي.
- مرتجعات المبيعات والمشتريات والاستبدال.
- التقارير والطباعة والتصدير إلى PDF وExcel.
- إعدادات المتجر والنسخ الاحتياطي.
- واجهات عربية RTL مع الوضع الفاتح والداكن وتصميم متجاوب.

> لا يتضمن النظام تعدد المستخدمين أو الصلاحيات، ولا IMEI أو الأرقام التسلسلية، ولا وحدة ضمان مستقلة. العملة الوحيدة هي الشيكل.

## متطلبات التشغيل

- PHP 8.3 أو أحدث.
- MySQL 8 أو MariaDB متوافق.
- Composer 2.
- Node.js 20.19 أو أحدث، ويفضل Node.js 22 LTS.
- NPM.
- إضافات PHP: `ctype`, `curl`, `dom`, `fileinfo`, `filter`, `hash`, `mbstring`, `openssl`, `pcre`, `pdo`, `pdo_mysql`, `session`, `tokenizer`, `xml`, `zip`.
- أداة `mysqldump` متاحة في PATH لاستخدام النسخ الاحتياطي.

## التثبيت المحلي على Windows / WAMP

```powershell
cd fanana-phone
composer install
Copy-Item .env.example .env
php artisan key:generate
```

أنشئ قاعدة بيانات باسم `fanana_phone`، ثم عدّل بيانات MySQL داخل `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=fanana_phone
DB_USERNAME=root
DB_PASSWORD=
```

بعد ذلك:

```powershell
php artisan migrate --seed
php artisan storage:link
npm ci
npm run build
```

للتطوير:

```powershell
composer run dev
```

أو في نافذتين منفصلتين:

```powershell
php artisan serve
npm run dev
```

الرابط المحلي الافتراضي:

```text
http://127.0.0.1:8000
```

## حساب المالك المحلي

```text
البريد: owner@fanana-phone.local
كلمة المرور: Fanana@123456
```

غيّر كلمة المرور فور بدء الاستخدام الفعلي. التسجيل العام معطل ولا يمكن إنشاء حسابات من الواجهة.

## أوامر الفحص

```powershell
php artisan test
npm run build
vendor\bin\pint --test
php artisan route:list
```

لا تستخدم `php artisan migrate:fresh` على قاعدة بيانات تحتوي على بيانات فعلية.

## النسخ الاحتياطي

نسخة يدوية:

```powershell
php artisan backup:run
```

تنظيف النسخ القديمة:

```powershell
php artisan backup:clean
```

تمت جدولة نسخة يومية من قاعدة البيانات الساعة 02:00 وتنظيف النسخ القديمة الساعة 03:00. على خادم Linux أضف Cron:

```cron
* * * * * cd /path/to/fanana-phone && php artisan schedule:run >> /dev/null 2>&1
```

على Windows يمكن استخدام **Task Scheduler** لتشغيل `php artisan schedule:run` كل دقيقة.

## النشر في بيئة الإنتاج

اضبط `.env`:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.example
SESSION_SECURE_COOKIE=true
LOG_LEVEL=warning
```

ثم نفّذ:

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan storage:link
php artisan optimize
```

- اجعل Document Root يشير إلى مجلد `public`.
- امنح خادم الويب صلاحية الكتابة إلى `storage` و`bootstrap/cache`.
- استخدم HTTPS.
- فعّل Scheduler وQueue Worker إذا تم اعتماد Queue غير متزامن.
- لا ترفع ملف `.env` إلى Git.

## ملاحظات محاسبية ومخزنية

- جميع المبالغ مخزنة بصيغة Decimal وبالشيكل.
- اعتماد البيع يخصم مخزون المبيعات داخل Transaction.
- اعتماد الشراء يضيف إلى المخزون ويحدّث متوسط التكلفة.
- قطع الصيانة تخصم من مخزون الصيانة عند اعتمادها.
- كل حركة مالية أو مخزنية معتمدة لها سجل مرجعي.
- المرتجعات والاستبدال لا تعدّل الفواتير الأصلية، بل تنشئ مستندات مستقلة وحركات عكسية.
- الفواتير المعتمدة لا تحذف، وإنما تُلغى وفق قواعد النظام.

## التوثيق

راجع تقرير التدقيق داخل:

```text
docs/PROJECT_AUDIT_AR.md
```
