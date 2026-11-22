# نظام إدارة المشاريع وتتبع التقدم اليومي
# Daily Progress Project Management System

## نظرة عامة | Overview

نظام إدارة مشاريع شامل مبني على Laravel لتتبع التقدم اليومي للمشاريع، إدارة الموظفين والعملاء، وتتبع بنود العمل والإنجاز.

A comprehensive Laravel-based project management system for tracking daily project progress, managing employees and clients, and monitoring work items and completion.

---

## المميزات الرئيسية | Key Features

### 1. إدارة المشاريع | Project Management
- ✅ إنشاء وإدارة المشاريع مع معلومات كاملة (العميل، التواريخ، الحالة)
- ✅ دعم المشاريع المسودة (Drafts) والنشر
- ✅ نسخ المشاريع
- ✅ تقسيم المشاريع إلى مشاريع فرعية (Subprojects)
- ✅ مخططات جانت (Gantt Charts) لعرض الجدول الزمني
- ✅ لوحة تحكم خاصة بكل مشروع

### 2. إدارة القوالب | Template Management
- ✅ إنشاء قوالب مشاريع قابلة لإعادة الاستخدام
- ✅ حفظ المشاريع كقوالب
- ✅ استخدام القوالب لإنشاء مشاريع جديدة بسرعة
- ✅ إدارة العلاقات بين البنود (Predecessors)

### 3. تتبع التقدم اليومي | Daily Progress Tracking
- ✅ تسجيل التقدم اليومي لكل بند عمل
- ✅ ربط التقدم بالموظفين والتواريخ
- ✅ حساب نسبة الإنجاز التلقائية
- ✅ عرض تاريخ التقدم الكامل
- ✅ تصدير تقارير التقدم

### 4. إدارة بنود العمل | Work Items Management
- ✅ إنشاء وإدارة بنود العمل مع الوحدات
- ✅ تصنيف البنود حسب الفئات
- ✅ تحديد العلاقات بين البنود (Predecessors)
- ✅ إدارة الكميات والمدة الزمنية
- ✅ إعادة ترتيب البنود

### 5. إدارة المشاكل | Issues Management
- ✅ نظام متكامل لإدارة المشاكل
- ✅ لوحة Kanban لعرض المشاكل
- ✅ إضافة تعليقات ومرفقات
- ✅ إحصائيات المشاكل
- ✅ تحديث حالة المشاكل

### 6. إدارة الموظفين والعملاء | Employees & Clients Management
- ✅ إدارة بيانات الموظفين والعملاء
- ✅ ربط الموظفين بالمشاريع
- ✅ نظام صلاحيات متقدم للموظفين
- ✅ ربط الموظفين بحسابات المستخدمين

### 7. نظام الصلاحيات | Permissions System
- ✅ نظام صلاحيات متقدم باستخدام Spatie Laravel Permission
- ✅ إدارة الأدوار والصلاحيات
- ✅ صلاحيات مخصصة لكل موظف
- ✅ حماية المسارات والوظائف

### 8. سجل الأنشطة | Activity Log
- ✅ تتبع جميع الأنشطة في النظام
- ✅ عرض سجل الأنشطة حسب المستخدم أو الكائن
- ✅ تصفية وبحث في السجلات

### 9. النسخ الاحتياطي والاستعادة | Backup & Restore
- ✅ إنشاء نسخ احتياطية من البيانات
- ✅ تصدير البيانات بصيغة ZIP
- ✅ تصدير SQL Dump
- ✅ استعادة النسخ الاحتياطية

### 10. التقارير والتصدير | Reports & Export
- ✅ تقارير التقدم التفصيلية
- ✅ تصدير البيانات بصيغ مختلفة (PDF, Excel)
- ✅ تقارير حسب المشروع أو الموظف

### 11. دعم متعدد اللغات | Multi-language Support
- ✅ دعم العربية والإنجليزية والهندية والأردية
- ✅ تبديل اللغة ديناميكياً
- ✅ ترجمة كاملة للواجهة

### 12. سلة المحذوفات | Recycle Bin
- ✅ استعادة العناصر المحذوفة
- ✅ حذف نهائي للعناصر

---

## المتطلبات | Requirements

- **PHP**: >= 8.2
- **Composer**: Latest version
- **Node.js**: >= 16.x
- **NPM**: Latest version
- **Database**: MySQL/MariaDB/PostgreSQL/SQLite
- **Web Server**: Apache/Nginx

---

## التثبيت | Installation

### 1. استنساخ المشروع | Clone the Repository
```bash
git clone <repository-url>
cd daily-progress-1.02
```

### 2. تثبيت التبعيات | Install Dependencies

#### تثبيت تبعيات PHP
```bash
composer install
```

#### تثبيت تبعيات JavaScript
```bash
npm install
```

### 3. إعداد البيئة | Environment Setup

#### نسخ ملف البيئة
```bash
cp .env.example .env
```

#### توليد مفتاح التطبيق
```bash
php artisan key:generate
```

#### تعديل ملف `.env`
قم بتعديل إعدادات قاعدة البيانات والبريد الإلكتروني:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_username
DB_PASSWORD=your_password

MAIL_MAILER=smtp
MAIL_HOST=your_mail_host
MAIL_PORT=587
MAIL_USERNAME=your_mail_username
MAIL_PASSWORD=your_mail_password
```

### 4. إعداد قاعدة البيانات | Database Setup

#### تشغيل Migrations
```bash
php artisan migrate
```

#### تشغيل Seeders
```bash
php artisan db:seed
```

سيقوم هذا بإنشاء:
- المستخدمين الأساسيين
- الأدوار والصلاحيات
- البيانات الأولية

### 5. إنشاء رابط التخزين | Storage Link
```bash
php artisan storage:link
```

### 6. بناء الأصول | Build Assets

#### للتطوير | Development
```bash
npm run dev
```

#### للإنتاج | Production
```bash
npm run build
```

### 7. تشغيل الخادم | Run Server

#### استخدام Laravel Serve
```bash
php artisan serve
```

#### أو استخدام Composer Script
```bash
composer run dev
```

سيقوم هذا بتشغيل:
- خادم Laravel
- Queue Worker
- Vite Dev Server

---

## البنية | Project Structure

```
daily-progress-1.02/
├── app/
│   ├── Console/          # أوامر Artisan
│   ├── Events/           # الأحداث
│   ├── Helpers/          # الدوال المساعدة
│   ├── Http/
│   │   ├── Controllers/  # المتحكمات
│   │   ├── Middleware/   # الوسطاء
│   │   └── Requests/     # طلبات التحقق
│   ├── Jobs/             # المهام في الخلفية
│   ├── Listeners/        # المستمعون للأحداث
│   ├── Models/           # النماذج
│   ├── Notifications/    # الإشعارات
│   ├── Observers/        # المراقبون
│   ├── Policies/         # السياسات
│   ├── Providers/        # مقدمي الخدمات
│   ├── Repositories/     # المستودعات
│   └── Services/         # الخدمات
├── bootstrap/            # ملفات التهيئة
├── config/               # ملفات الإعدادات
├── database/
│   ├── factories/        # مصانع البيانات
│   ├── migrations/       # الهجرات
│   └── seeders/          # البذور
├── public/               # الملفات العامة
├── resources/
│   ├── css/              # ملفات CSS
│   ├── js/               # ملفات JavaScript
│   ├── lang/             # ملفات الترجمة
│   ├── sass/             # ملفات SASS
│   └── views/            # قوالب Blade
├── routes/               # ملفات المسارات
├── storage/              # ملفات التخزين
├── tests/                # الاختبارات
└── vendor/               # مكتبات Composer
```

---

## النماذج الرئيسية | Main Models

### Project
- إدارة المشاريع
- العلاقات: Client, ProjectType, Employees, ProjectItems, Issues, Subprojects

### DailyProgress
- تتبع التقدم اليومي
- العلاقات: Project, ProjectItem, Employee

### WorkItem
- بنود العمل الأساسية
- العلاقات: Category, Predecessor, ProjectItems, TemplateItems

### ProjectItem
- بنود المشروع المحددة
- العلاقات: Project, WorkItem

### Employee
- إدارة الموظفين
- العلاقات: User, Projects, DailyProgress

### Client
- إدارة العملاء
- العلاقات: Projects

### Issue
- إدارة المشاكل
- العلاقات: Project, Comments, Attachments

### ProjectTemplate
- قوالب المشاريع
- العلاقات: TemplateItems

---

## المسارات الرئيسية | Main Routes

### المشاريع | Projects
- `GET /projects` - قائمة المشاريع
- `GET /projects/create` - إنشاء مشروع جديد
- `POST /projects` - حفظ مشروع جديد
- `GET /projects/{id}` - عرض تفاصيل المشروع
- `GET /projects/{id}/edit` - تعديل مشروع
- `PUT /projects/{id}` - تحديث مشروع
- `DELETE /projects/{id}` - حذف مشروع
- `GET /projects/{id}/gantt` - مخطط جانت
- `GET /projects/{id}/progress` - صفحة التقدم
- `GET /projects/{id}/dashboard` - لوحة التحكم

### التقدم اليومي | Daily Progress
- `GET /daily-progress` - قائمة التقدم
- `POST /daily-progress` - إضافة تقدم جديد
- `PUT /daily-progress/{id}` - تحديث التقدم
- `DELETE /daily-progress/{id}` - حذف التقدم

### القوالب | Templates
- `GET /project-templates` - قائمة القوالب
- `POST /project-templates` - إنشاء قالب جديد
- `GET /project-templates/{id}/items` - بنود القالب
- `POST /projects/{id}/save-as-template` - حفظ مشروع كقالب

### المشاكل | Issues
- `GET /issues` - قائمة المشاكل
- `GET /issues/kanban` - لوحة Kanban
- `POST /issues` - إضافة مشكلة جديدة
- `POST /issues/{id}/comments` - إضافة تعليق

### النسخ الاحتياطي | Backup
- `GET /backup` - قائمة النسخ الاحتياطية
- `GET /backup/export` - تصدير نسخة احتياطية
- `POST /backup/import` - استعادة نسخة احتياطية

---

## الصلاحيات | Permissions

النظام يستخدم Spatie Laravel Permission لإدارة الصلاحيات. الصلاحيات الرئيسية:

- `projects-view` - عرض المشاريع
- `projects-create` - إنشاء مشاريع
- `projects-edit` - تعديل المشاريع
- `projects-delete` - حذف المشاريع
- `projects-copy` - نسخ المشاريع
- `projects-save-as-template` - حفظ كمشروع كقالب
- `daily-progress-*` - صلاحيات التقدم اليومي
- `employees-*` - صلاحيات الموظفين
- `clients-*` - صلاحيات العملاء
- `issues-*` - صلاحيات المشاكل
- `backup-*` - صلاحيات النسخ الاحتياطي

---

## الاستخدام | Usage

### إنشاء مشروع جديد | Creating a New Project

1. انتقل إلى `/projects/create`
2. املأ معلومات المشروع الأساسية
3. اختر العميل ونوع المشروع
4. اختر القالب (اختياري) أو أضف البنود يدوياً
5. حدد الموظفين المخصصين للمشروع
6. احفظ المشروع كمسودة أو انشره مباشرة

### تسجيل التقدم اليومي | Recording Daily Progress

1. انتقل إلى صفحة المشروع
2. اضغط على "التقدم اليومي"
3. اختر البند والموظف والتاريخ
4. أدخل الكمية المنجزة
5. احفظ التقدم

### إدارة المشاكل | Managing Issues

1. انتقل إلى `/issues`
2. اضغط على "إضافة مشكلة جديدة"
3. املأ تفاصيل المشكلة
4. أضف تعليقات ومرفقات حسب الحاجة
5. استخدم لوحة Kanban لإدارة الحالات

---

## الاختبارات | Testing

```bash
# تشغيل جميع الاختبارات
php artisan test

# أو باستخدام Pest
composer test
```

---

## التطوير | Development

### الأوامر المفيدة | Useful Commands

```bash
# مسح الكاش
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# إعادة تحميل التطبيق
php artisan optimize:clear

# تشغيل Queue Worker
php artisan queue:work

# إنشاء Migration جديد
php artisan make:migration create_table_name

# إنشاء Controller جديد
php artisan make:controller ControllerName

# إنشاء Model جديد
php artisan make:model ModelName
```

---

## التبعيات | Dependencies

### Backend (PHP)
- **Laravel Framework**: ^12.0
- **Spatie Laravel Permission**: ^6.21
- **Laravel UI**: ^4.6
- **DomPDF**: For PDF generation
- **Fast Excel**: For Excel export

### Frontend (JavaScript)
- **Vue.js**: ^3.2.37
- **Bootstrap**: ^5.3.0
- **jQuery**: ^3.7.1
- **Moment.js**: ^2.30.1
- **Tailwind CSS**: ^4.0.0
- **Vite**: ^6.2.4

---

## الترجمة | Localization

النظام يدعم 4 لغات:
- العربية (ar)
- الإنجليزية (en)
- الهندية (hi)
- الأردية (ur)

ملفات الترجمة موجودة في `resources/lang/`

---

## الأمان | Security

- ✅ حماية CSRF
- ✅ التحقق من الصلاحيات على جميع المسارات
- ✅ Soft Deletes للبيانات الحساسة
- ✅ تشفير كلمات المرور
- ✅ حماية من SQL Injection
- ✅ حماية من XSS

---

## الدعم | Support

للحصول على المساعدة أو الإبلاغ عن مشاكل، يرجى فتح Issue في المستودع.

---

## الترخيص | License

هذا المشروع مرخص تحت رخصة MIT.

---

## الإصدار | Version

**الإصدار الحالي**: 1.02

---

## التطوير المستقبلي | Future Development

- [ ] تطبيق موبايل
- [ ] API للوصول الخارجي
- [ ] إشعارات فورية
- [ ] تقارير متقدمة
- [ ] تكامل مع أنظمة خارجية

---

## المساهمون | Contributors

- فريق التطوير

---

## التحديثات | Changelog

### الإصدار 1.02
- تحسينات في نظام القوالب
- إصلاحات في حساب التقدم
- تحسينات في الأداء
- إضافة ميزات جديدة للمشاكل

---

## ملاحظات | Notes

- تأكد من تشغيل `php artisan storage:link` بعد التثبيت
- تأكد من إعداد Queue Worker للإشعارات والمهام الخلفية
- يوصى باستخدام Redis للكاش في بيئة الإنتاج

---

**تم التطوير بـ ❤️ باستخدام Laravel**


