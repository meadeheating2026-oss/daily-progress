<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Urca Backup</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #0f172a;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .backup-card {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 16px;
            padding: 40px;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5);
        }

        .backup-card .logo {
            text-align: center;
            margin-bottom: 32px;
        }

        .backup-card .logo svg {
            width: 56px;
            height: 56px;
            margin-bottom: 12px;
        }

        .backup-card .logo h1 {
            color: #f1f5f9;
            font-size: 22px;
            font-weight: 700;
            letter-spacing: 1px;
        }

        .backup-card .logo p {
            color: #64748b;
            font-size: 13px;
            margin-top: 4px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            color: #94a3b8;
            font-size: 13px;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 12px 16px;
            background: #0f172a;
            border: 1px solid #334155;
            border-radius: 8px;
            color: #f1f5f9;
            font-size: 14px;
            transition: border-color 0.2s;
            outline: none;
        }

        .form-group input:focus {
            border-color: #3b82f6;
        }

        .form-group input::placeholder {
            color: #475569;
        }

        .btn-backup {
            width: 100%;
            padding: 13px;
            background: #3b82f6;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s, transform 0.1s;
            margin-top: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-backup:hover:not(:disabled) {
            background: #2563eb;
        }

        .btn-backup:active:not(:disabled) {
            transform: scale(0.98);
        }

        .btn-backup:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .btn-backup .spinner {
            display: none;
            width: 18px;
            height: 18px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
        }

        .btn-backup.loading .spinner {
            display: block;
        }

        .btn-backup.loading .btn-text {
            opacity: 0.8;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 13px;
            margin-top: 16px;
            display: none;
        }

        .alert.error {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.4);
            color: #fca5a5;
        }

        .alert.success {
            background: rgba(34, 197, 94, 0.15);
            border: 1px solid rgba(34, 197, 94, 0.4);
            color: #86efac;
        }
    </style>
</head>
<body>

<div class="backup-card">
    <div class="logo">
        <!-- Database icon -->
        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <ellipse cx="12" cy="5" rx="9" ry="3" stroke="#3b82f6" stroke-width="1.8"/>
            <path d="M3 5v4c0 1.657 4.03 3 9 3s9-1.343 9-3V5" stroke="#3b82f6" stroke-width="1.8"/>
            <path d="M3 9v4c0 1.657 4.03 3 9 3s9-1.343 9-3V9" stroke="#3b82f6" stroke-width="1.8"/>
            <path d="M3 13v4c0 1.657 4.03 3 9 3s9-1.343 9-3v-4" stroke="#3b82f6" stroke-width="1.8"/>
        </svg>
        <h1>URCA BACKUP</h1>
        <p>Database Backup System</p>
    </div>

    <div class="form-group">
        <label for="email">البريد الإلكتروني</label>
        <input type="email" id="email" placeholder="admin@example.com" autocomplete="email" />
    </div>

    <div class="form-group">
        <label for="password">كلمة المرور</label>
        <input type="password" id="password" placeholder="••••••••" autocomplete="current-password" />
    </div>

    <button class="btn-backup" id="backupBtn" onclick="startBackup()">
        <span class="spinner"></span>
        <span class="btn-text">تحميل النسخة الاحتياطية</span>
    </button>

    <div class="alert" id="alertBox"></div>
</div>

<script>
    // بيانات المصادقة الثابتة — التحقق يتم هنا في الـ JS فقط
    const backupConfig = {
        validEmail    : 'admin@urca.com',
        validPassword : '12345678',
        url           : '{{ route("urca-backup.download") }}'
    };

    function showAlert(message, type) {
        const box = document.getElementById('alertBox');
        box.textContent = message;
        box.className = 'alert ' + type;
        box.style.display = 'block';
    }

    function hideAlert() {
        document.getElementById('alertBox').style.display = 'none';
    }

    function setLoading(state) {
        const btn = document.getElementById('backupBtn');
        btn.disabled = state;
        if (state) {
            btn.classList.add('loading');
            btn.querySelector('.btn-text').textContent = 'جاري التحضير...';
        } else {
            btn.classList.remove('loading');
            btn.querySelector('.btn-text').textContent = 'تحميل النسخة الاحتياطية';
        }
    }

    async function startBackup() {
        hideAlert();

        const email    = document.getElementById('email').value.trim();
        const password = document.getElementById('password').value;

        if (!email || !password) {
            showAlert('يرجى إدخال البريد الإلكتروني وكلمة المرور', 'error');
            return;
        }

        // ✅ التحقق في الـ JS
        if (email !== backupConfig.validEmail || password !== backupConfig.validPassword) {
            showAlert('البريد الإلكتروني أو كلمة المرور غير صحيحة', 'error');
            return;
        }

        setLoading(true);

        try {
            // إرسال الطلب بدون credentials — التحقق تم بالفعل أعلاه
            const response = await fetch(backupConfig.url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/octet-stream',
                },
            });

            if (response.ok) {
                const blob        = await response.blob();
                const disposition = response.headers.get('Content-Disposition') || '';
                const match       = disposition.match(/filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/);
                const filename    = match ? match[1].replace(/['"]/g, '') : 'backup.sql';

                const url = window.URL.createObjectURL(blob);
                const a   = document.createElement('a');
                a.href     = url;
                a.download = filename;
                document.body.appendChild(a);
                a.click();
                a.remove();
                window.URL.revokeObjectURL(url);

                showAlert('✓ تم تحميل النسخة الاحتياطية بنجاح', 'success');
            } else {
                showAlert('فشل الاتصال بالخادم', 'error');
            }
        } catch (err) {
            showAlert('خطأ في الاتصال: ' + err.message, 'error');
        } finally {
            setLoading(false);
        }
    }

    // السماح بالضغط على Enter
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') startBackup();
    });
</script>

</body>
</html>
