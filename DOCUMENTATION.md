# التوثيق التقني | Technical Documentation

## جدول المحتويات | Table of Contents

1. [البنية المعمارية](#البنية-المعاركية)
2. [قاعدة البيانات](#قاعدة-البيانات)
3. [النماذج والعلاقات](#النماذج-والعلاقات)
4. [المتحكمات](#المتحكمات)
5. [الخدمات](#الخدمات)
6. [API Routes](#api-routes)
7. [الوسطاء](#الوسطاء)
8. [السياسات](#السياسات)
9. [الأحداث والمستمعون](#الأحداث-والمستمعون)
10. [الأوامر المخصصة](#الأوامر-المخصصة)
11. [الترجمة](#الترجمة)

---

## البنية المعمارية | Architecture

### النمط المعماري | Architectural Pattern

المشروع يستخدم نمط **MVC (Model-View-Controller)** مع بعض التحسينات:

- **Models**: في `app/Models/`
- **Views**: في `resources/views/`
- **Controllers**: في `app/Http/Controllers/`
- **Services**: في `app/Services/` - لتنظيم منطق العمل المعقد
- **Repositories**: في `app/Repositories/` - لإدارة الوصول للبيانات
- **Observers**: في `app/Observers/` - لمراقبة أحداث النماذج

### بنية الطبقات | Layer Structure

```
┌─────────────────────────────────────┐
│         Presentation Layer          │
│      (Views, JavaScript, CSS)       │
└─────────────────────────────────────┘
              ↓
┌─────────────────────────────────────┐
│         Controller Layer            │
│      (HTTP Controllers)             │
└─────────────────────────────────────┘
              ↓
┌─────────────────────────────────────┐
│         Service Layer               │
│   (Business Logic)                  │
└─────────────────────────────────────┘
              ↓
┌─────────────────────────────────────┐
│      Repository Layer               │
│   (Data Access)                     │
└─────────────────────────────────────┘
              ↓
┌─────────────────────────────────────┐
│         Model Layer                 │
│   (Database Models)                 │
└─────────────────────────────────────┘
```

---

## قاعدة البيانات | Database

### الجداول الرئيسية | Main Tables

#### projects
```sql
- id
- name
- description
- client_id
- start_date
- end_date
- status (pending, active, completed)
- working_zone
- project_type_id
- working_days
- daily_work_hours
- weekly_holidays
- is_draft
- created_at
- updated_at
- deleted_at
```

#### project_items
```sql
- id
- project_id
- work_item_id
- subproject_name
- total_quantity
- completed_quantity
- duration
- lag
- start_date
- end_date
- target_completion_date
- notes
- item_order
- is_measurable
- status_id
- created_at
- updated_at
- deleted_at
```

#### daily_progress
```sql
- id
- project_id
- project_item_id
- employee_id
- progress_date
- quantity
- notes
- created_at
- updated_at
- deleted_at
```

#### work_items
```sql
- id
- name
- unit
- description
- category_id
- total_quantity
- expected_quantity_per_day
- duration
- predecessor_id
- lag
- shift
- created_at
- updated_at
- deleted_at
```

#### employees
```sql
- id
- name
- position
- phone
- email
- user_id
- created_at
- updated_at
- deleted_at
```

#### clients
```sql
- id
- name
- phone
- email
- address
- created_at
- updated_at
- deleted_at
```

#### issues
```sql
- id
- project_id
- title
- description
- status
- priority
- assigned_to
- created_by
- created_at
- updated_at
- deleted_at
```

#### project_templates
```sql
- id
- name
- description
- project_type_id
- created_at
- updated_at
- deleted_at
```

#### template_items
```sql
- id
- template_id
- work_item_id
- subproject_name
- total_quantity
- duration
- lag
- item_order
- predecessor_id
- created_at
- updated_at
- deleted_at
```

### العلاقات | Relationships

#### Projects
- `belongsTo` Client
- `belongsTo` ProjectType
- `belongsToMany` Employee
- `hasMany` ProjectItem
- `hasMany` Issue
- `hasMany` Subproject
- `hasManyThrough` DailyProgress

#### ProjectItems
- `belongsTo` Project
- `belongsTo` WorkItem
- `belongsTo` ItemStatus
- `hasMany` DailyProgress

#### DailyProgress
- `belongsTo` Project
- `belongsTo` ProjectItem
- `belongsTo` Employee

#### WorkItems
- `belongsTo` WorkItemCategory
- `belongsTo` WorkItem (predecessor)
- `hasMany` WorkItem (successors)
- `hasMany` ProjectItem
- `hasMany` TemplateItem

#### Employees
- `belongsTo` User
- `belongsToMany` Project
- `hasMany` DailyProgress

---

## النماذج والعلاقات | Models & Relationships

### Project Model

**العلاقات:**
```php
public function client()
public function type()
public function employees()
public function items()
public function projectItems()
public function issues()
public function subprojects()
public function dailyProgress()
```

**Attributes:**
- `overall_progress` - نسبة الإنجاز الإجمالية
- `total_duration` - المدة الإجمالية
- `total_lag` - التأخير الإجمالي
- `has_notes` - هل يوجد ملاحظات

**Scopes:**
- `scopeActive()` - المشاريع النشطة
- `scopeDraft()` - المشاريع المسودة
- `scopePublished()` - المشاريع المنشورة

**Methods:**
- `getItemsGroupedByCategory()` - تجميع البنود حسب الفئة
- `reorderItems($itemOrders)` - إعادة ترتيب البنود

### DailyProgress Model

**العلاقات:**
```php
public function project()
public function projectItem()
public function employee()
```

**Attributes:**
- `completion_percentage` - نسبة الإنجاز

### WorkItem Model

**العلاقات:**
```php
public function category()
public function predecessorItem()
public function successorItems()
public function projectItems()
public function templateItems()
```

### Employee Model

**العلاقات:**
```php
public function user()
public function projects()
public function dailyProgress()
```

---

## المتحكمات | Controllers

### ProjectController

**الطرق الرئيسية:**
- `index()` - عرض قائمة المشاريع
- `create()` - عرض نموذج الإنشاء
- `store()` - حفظ مشروع جديد
- `show()` - عرض تفاصيل المشروع
- `edit()` - عرض نموذج التعديل
- `update()` - تحديث المشروع
- `destroy()` - حذف المشروع
- `progress()` - صفحة التقدم
- `dashboard()` - لوحة التحكم
- `ganttChart()` - مخطط جانت
- `ganttData()` - بيانات مخطط جانت
- `export()` - تصدير التقدم
- `copy()` - نسخ المشروع
- `saveAsTemplate()` - حفظ كقالب
- `updateItemStatus()` - تحديث حالة البند
- `updateSubprojectWeight()` - تحديث وزن المشروع الفرعي
- `updateAllSubprojectsWeight()` - تحديث جميع الأوزان

### DailyProgressController

**الطرق الرئيسية:**
- `index()` - عرض قائمة التقدم
- `create()` - عرض نموذج الإنشاء
- `store()` - حفظ تقدم جديد
- `edit()` - عرض نموذج التعديل
- `update()` - تحديث التقدم
- `destroy()` - حذف التقدم
- `executedToday()` - التقدم المنفذ اليوم (API)

### ProjectTemplateController

**الطرق الرئيسية:**
- `index()` - عرض قائمة القوالب
- `create()` - عرض نموذج الإنشاء
- `store()` - حفظ قالب جديد
- `storeFromForm()` - حفظ قالب من النموذج
- `storeFromProject()` - حفظ مشروع كقالب
- `show()` - عرض تفاصيل القالب
- `edit()` - عرض نموذج التعديل
- `update()` - تحديث القالب
- `destroy()` - حذف القالب
- `items()` - جلب بنود القالب (JSON)
- `getTemplateData()` - جلب بيانات القالب (JSON)
- `reorderItems()` - إعادة ترتيب البنود
- `debugPredecessors()` - تصحيح العلاقات

### IssueController

**الطرق الرئيسية:**
- `index()` - عرض قائمة المشاكل
- `kanban()` - لوحة Kanban
- `statistics()` - إحصائيات المشاكل
- `create()` - عرض نموذج الإنشاء
- `store()` - حفظ مشكلة جديدة
- `show()` - عرض تفاصيل المشكلة
- `edit()` - عرض نموذج التعديل
- `update()` - تحديث المشكلة
- `destroy()` - حذف المشكلة
- `addComment()` - إضافة تعليق
- `deleteComment()` - حذف تعليق
- `downloadAttachment()` - تحميل مرفق
- `deleteAttachment()` - حذف مرفق
- `updateStatus()` - تحديث الحالة

### EmployeeController

**الطرق الرئيسية:**
- `index()` - عرض قائمة الموظفين
- `create()` - عرض نموذج الإنشاء
- `store()` - حفظ موظف جديد
- `edit()` - عرض نموذج التعديل
- `update()` - تحديث الموظف
- `destroy()` - حذف الموظف
- `editPermissions()` - تعديل الصلاحيات
- `updatePermissions()` - تحديث الصلاحيات

### ClientController

**الطرق الرئيسية:**
- `index()` - عرض قائمة العملاء
- `create()` - عرض نموذج الإنشاء
- `store()` - حفظ عميل جديد
- `edit()` - عرض نموذج التعديل
- `update()` - تحديث العميل
- `destroy()` - حذف العميل

### DashboardController

**الطرق الرئيسية:**
- `index()` - الصفحة الرئيسية للوحة التحكم

### BackupController

**الطرق الرئيسية:**
- `index()` - عرض قائمة النسخ الاحتياطية
- `export()` - تصدير نسخة احتياطية
- `import()` - استعادة نسخة احتياطية
- `download()` - تحميل نسخة احتياطية
- `destroy()` - حذف نسخة احتياطية

### DataExportController

**الطرق الرئيسية:**
- `exportAllData()` - تصدير جميع البيانات
- `exportSqlDump()` - تصدير SQL Dump

### RecycleBinController

**الطرق الرئيسية:**
- `index()` - عرض سلة المحذوفات
- `restore()` - استعادة عنصر
- `forceDelete()` - حذف نهائي
- `permanentDelete()` - حذف دائم

### ActivityLogController

**الطرق الرئيسية:**
- `index()` - عرض سجل الأنشطة
- `show()` - عرض تفاصيل النشاط
- `userActivities()` - أنشطة مستخدم
- `subjectActivities()` - أنشطة كائن
- `getActivities()` - جلب الأنشطة (API)

---

## الخدمات | Services

### ProjectService

**الطرق:**
- `createProject($data)` - إنشاء مشروع
- `updateProject($project, $data)` - تحديث مشروع
- `copyProject($project)` - نسخ مشروع
- `calculateProgress($project)` - حساب التقدم

### TemplateService

**الطرق:**
- `createFromProject($project)` - إنشاء قالب من مشروع
- `applyToProject($template, $projectData)` - تطبيق قالب على مشروع

### ActivityLogService

**الطرق:**
- `log($action, $subject, $description)` - تسجيل نشاط
- `getUserActivities($userId)` - أنشطة مستخدم
- `getSubjectActivities($subjectType, $subjectId)` - أنشطة كائن

---

## Repositories

### ProjectRepository

**الطرق:**
- `all()` - جميع المشاريع
- `find($id)` - البحث عن مشروع
- `create($data)` - إنشاء مشروع
- `update($project, $data)` - تحديث مشروع
- `delete($project)` - حذف مشروع
- `getActive()` - المشاريع النشطة
- `getByClient($clientId)` - مشاريع عميل

### ProjectItemRepository

**الطرق:**
- `getByProject($projectId)` - بنود مشروع
- `reorder($projectId, $orders)` - إعادة ترتيب

### EmployeeRepository

**الطرق:**
- `all()` - جميع الموظفين
- `withPermissions()` - مع الصلاحيات
- `updatePermissions($employee, $permissions)` - تحديث الصلاحيات

---

## API Routes

### Project API
```
GET /api/project-items/{project}
POST /api/projects/{project}/subprojects/update-all-weights
```

### Daily Progress API
```
GET /api/daily-progress/executed-today
```

### Projects API
```
GET /api/projects/{project}/subprojects
POST /api/projects/{project}/subprojects/{subproject}/update-weight
POST /api/projects/{project}/subprojects/update-all-weights
```

### Activity Logs API
```
GET /api/activity-logs/activities
```

---

## الوسطاء | Middleware

### Authenticate
- يتحقق من تسجيل دخول المستخدم

### Permission Middleware
- يتحقق من صلاحيات المستخدم
- يستخدم `can:permission-name` في المسارات

---

## السياسات | Policies

### ProjectPolicy

**الطرق:**
- `view()` - عرض المشروع
- `create()` - إنشاء مشروع
- `update()` - تحديث المشروع
- `delete()` - حذف المشروع

---

## الأحداث والمستمعون | Events & Listeners

### Observers

#### ClientObserver
- `created()` - عند إنشاء عميل جديد
- `updated()` - عند تحديث عميل
- `deleted()` - عند حذف عميل

#### EmployeeObserver
- `created()` - عند إنشاء موظف جديد
- `updated()` - عند تحديث موظف
- `deleted()` - عند حذف موظف

#### ProjectObserver
- `created()` - عند إنشاء مشروع جديد
- `updated()` - عند تحديث مشروع
- `deleted()` - عند حذف مشروع

---

## الأوامر المخصصة | Custom Commands

الأوامر المخصصة موجودة في `app/Console/Commands/`

---

## الترجمة | Localization

### اللغات المدعومة

- **العربية** (ar)
- **الإنجليزية** (en)
- **الهندية** (hi)
- **الأردية** (ur)

### ملفات الترجمة

الملفات موجودة في `resources/lang/{locale}/`

**الملفات المتاحة:**
- `general.php` - النصوص العامة
- `projects.php` - نصوص المشاريع
- `daily-progress.php` - نصوص التقدم اليومي
- `work-items.php` - نصوص بنود العمل
- `employees.php` - نصوص الموظفين
- `clients.php` - نصوص العملاء
- `issues.php` - نصوص المشاكل
- `auth.php` - نصوص المصادقة

### استخدام الترجمة

```php
// في الكود
__('general.welcome')
__('projects.list')
trans('projects.create')

// في Blade
{{ __('general.save') }}
{{ trans('projects.name') }}
```

### تبديل اللغة

```php
// في Controller
app()->setLocale('ar');
Config::set('app.locale', 'ar');

// من المسار
Route::get('/lang/{locale}', [LocalizationController::class, 'switch']);
```

---

## Helpers

### LocaleHelper

**الطرق:**
- `getCurrentLocale()` - اللغة الحالية
- `setLocale($locale)` - تعيين اللغة
- `getAvailableLocales()` - اللغات المتاحة

---

## JavaScript Structure

### الملفات الرئيسية

- `public/js/dashboard.js` - لوحة التحكم
- `public/js/project-form.js` - نموذج المشروع
- `public/js/projects-filter.js` - تصفية المشاريع
- `resources/js/app.js` - التطبيق الرئيسي
- `resources/js/project-items.js` - بنود المشروع

### المكتبات المستخدمة

- jQuery
- Bootstrap
- Vue.js
- Moment.js
- Chart.js (محتمل)

---

## CSS Structure

### الملفات الرئيسية

- `public/css/dashboard.css` - تنسيقات لوحة التحكم
- `public/css/dark-mode.css` - الوضع الداكن
- `public/css/gantt.css` - مخطط جانت
- `resources/css/app.css` - التنسيقات الرئيسية
- `resources/sass/app.scss` - ملفات SASS

---

## Security Features

### حماية CSRF
جميع نماذج POST محمية بـ CSRF tokens

### التحقق من الصلاحيات
جميع المسارات محمية بصلاحيات

### Soft Deletes
البيانات الحساسة تستخدم Soft Deletes

### Validation
جميع المدخلات يتم التحقق منها

### SQL Injection Protection
استخدام Eloquent ORM يحمي من SQL Injection

### XSS Protection
Blade يقوم بتنظيف البيانات تلقائياً

---

## Performance Optimization

### Caching
- Config Cache
- Route Cache
- View Cache

### Database Optimization
- Indexes على الحقول المهمة
- Eager Loading للعلاقات
- Query Optimization

### Asset Optimization
- Vite للبناء
- Minification للـ CSS/JS
- CDN للمكتبات (محتمل)

---

## Testing

### Unit Tests
في `tests/Unit/`

### Feature Tests
في `tests/Feature/`

### تشغيل الاختبارات
```bash
php artisan test
# أو
composer test
```

---

## Deployment

### متطلبات الإنتاج

1. تعيين `APP_ENV=production`
2. تعيين `APP_DEBUG=false`
3. توليد `APP_KEY`
4. بناء الأصول: `npm run build`
5. تحسين الكاش:
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```
6. إعداد Queue Worker
7. إعداد Cron Jobs:
   ```bash
   * * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
   ```

---

## Troubleshooting

### مشاكل شائعة

1. **خطأ في الصلاحيات**
   - تأكد من تشغيل migrations
   - تأكد من تشغيل seeders

2. **مشاكل في الترجمة**
   - تأكد من وجود ملفات الترجمة
   - امسح cache: `php artisan cache:clear`

3. **مشاكل في المسارات**
   - امسح route cache: `php artisan route:clear`

4. **مشاكل في Storage**
   - أنشئ الرابط: `php artisan storage:link`

---

## Best Practices

### Coding Standards
- اتباع PSR-12
- استخدام Laravel Pint

### Naming Conventions
- Controllers: `ResourceController`
- Models: `Singular`
- Routes: `kebab-case`
- Variables: `camelCase`

### Code Organization
- فصل منطق العمل إلى Services
- استخدام Repositories للوصول للبيانات
- استخدام Form Requests للتحقق

---

## Contributing

1. Fork المشروع
2. أنشئ فرع للميزة (`git checkout -b feature/AmazingFeature`)
3. Commit التغييرات (`git commit -m 'Add some AmazingFeature'`)
4. Push للفرع (`git push origin feature/AmazingFeature`)
5. افتح Pull Request

---

## License

MIT License

---

**آخر تحديث**: 2024

