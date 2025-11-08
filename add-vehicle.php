<?php

// تحميل نظام الترجمة
require_once 'includes/translator.php';

require_once 'config/database.php';

require_once 'includes/functions.php';

 

// التحقق من تسجيل الدخول

if (!isLoggedIn()) {

    setMessage('يجب تسجيل الدخول أولاً', 'danger');

    redirect('auth/login.php');

}

 

// التحقق من حالة KYC

$stmt = $conn->prepare("SELECT kyc_status FROM users WHERE id = ?");

$stmt->execute([$_SESSION['user_id']]);

$user = $stmt->fetch();

 

if ($user['kyc_status'] != 'verified') {

    if ($user['kyc_status'] == 'unverified') {

        setMessage('يجب التحقق من هويتك أولاً لإضافة سيارة', 'danger');

        redirect('kyc/submit.php');

    } elseif ($user['kyc_status'] == 'pending') {

        setMessage('طلب التحقق قيد المراجعة. يرجى الانتظار', 'warning');

        redirect('dashboard.php');

    } elseif ($user['kyc_status'] == 'rejected') {

        setMessage('تم رفض طلب التحقق. يرجى إعادة المحاولة', 'danger');

        redirect('kyc/submit.php');

    }

}

 

$errors = [];

$success = '';

 

// معالجة النموذج

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

 

    // استلام البيانات

    $brand = clean($_POST['brand']);

    $model = clean($_POST['model']);

    $year = clean($_POST['year']);

    $mileage = clean($_POST['mileage']);

    $color = clean($_POST['color']);

    $transmission = clean($_POST['transmission']);

    $fuel_type = clean($_POST['fuel_type']);

    $description = clean($_POST['description']);

    $starting_price = clean($_POST['starting_price']);

    $reserve_price = clean($_POST['reserve_price']);

    $buy_now_price = !empty($_POST['buy_now_price']) ? clean($_POST['buy_now_price']) : NULL;

    $auction_duration = clean($_POST['auction_duration']); // بالدقائق

    $duration_unit = clean($_POST['duration_unit']); // minutes أو hours

 

    // التحقق من البيانات

    if (empty($brand)) $errors[] = 'الماركة مطلوبة';

    if (empty($model)) $errors[] = 'الموديل مطلوب';

    if (empty($year) || $year < 1900 || $year > date('Y')+1) $errors[] = 'سنة الصنع غير صحيحة';

    if (empty($starting_price) || $starting_price <= 0) $errors[] = 'السعر الابتدائي غير صحيح';

    if (!empty($reserve_price) && $reserve_price < $starting_price) $errors[] = 'السعر الاحتياطي يجب أن يكون أكبر من السعر الابتدائي';

    if (!empty($buy_now_price) && $buy_now_price <= $starting_price) $errors[] = 'سعر الشراء الفوري يجب أن يكون أكبر من السعر الابتدائي';

    if (empty($auction_duration) || $auction_duration <= 0) $errors[] = 'مدة المزاد غير صحيحة';

 

    // التحقق من الصور

    if (empty($_FILES['images']['name'][0])) {

        $errors[] = 'يجب رفع صورة واحدة على الأقل';

    }

 

    // إذا لا توجد أخطاء

    if (empty($errors)) {

        try {

            $conn->beginTransaction();

 

            // إدراج السيارة

            $stmt = $conn->prepare("

                INSERT INTO vehicles (seller_id, brand, model, year, mileage, color, transmission, fuel_type, description, status)

                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'approved')

            ");

            $stmt->execute([

                $_SESSION['user_id'],

                $brand,

                $model,

                $year,

                $mileage,

                $color,

                $transmission,

                $fuel_type,

                $description

            ]);

 

            $vehicle_id = $conn->lastInsertId();

 

            // رفع الصور

            $upload_dir = 'uploads/vehicles/';

            if (!is_dir($upload_dir)) {

                mkdir($upload_dir, 0777, true);

            }

 

            $uploaded_images = 0;

            foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {

                if ($uploaded_images >= 5) break;

 

                if ($_FILES['images']['error'][$key] == 0) {

                    $file_name = $_FILES['images']['name'][$key];

                    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

                    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

 

                    if (in_array($file_ext, $allowed_extensions)) {

                        $new_file_name = $vehicle_id . '_' . time() . '_' . $key . '.' . $file_ext;

                        $destination = $upload_dir . $new_file_name;

 

                        if (move_uploaded_file($tmp_name, $destination)) {

                            // إدراج في قاعدة البيانات

                            $is_primary = ($uploaded_images == 0) ? 1 : 0;

                            $stmt = $conn->prepare("INSERT INTO vehicle_images (vehicle_id, image_path, is_primary) VALUES (?, ?, ?)");

                            $stmt->execute([$vehicle_id, $destination, $is_primary]);

                            $uploaded_images++;

                        }

                    }

                }

            }

 

            if ($uploaded_images == 0) {

                throw new Exception('فشل رفع الصور');

            }

 

            // حساب وقت البداية والنهاية

            $start_time = date('Y-m-d H:i:s');

 

            // تحويل المدة إلى دقائق

            if ($duration_unit == 'hours') {

                $duration_in_minutes = $auction_duration * 60;

            } else {

                $duration_in_minutes = $auction_duration;

            }

 

            $end_time = date('Y-m-d H:i:s', time() + ($duration_in_minutes * 60));

 

            // إنشاء المزاد

            $stmt = $conn->prepare("

                INSERT INTO auctions (vehicle_id, seller_id, starting_price, current_price, reserve_price, buy_now_price, start_time, end_time, status)

                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')

            ");

            $stmt->execute([

                $vehicle_id,

                $_SESSION['user_id'],

                $starting_price,

                $starting_price,

                $reserve_price,

                $buy_now_price,

                $start_time,

                $end_time

            ]);

 

            $auction_id = $conn->lastInsertId();

 

            $conn->commit();

 

            $success = 'تم إضافة السيارة للمزاد بنجاح!';

            header("refresh:2;url=auction-details.php?id=$auction_id");

 

        } catch(Exception $e) {

            $conn->rollBack();

            $errors[] = 'حدث خطأ: ' . $e->getMessage();

        }

    }

}

 

?>

<!DOCTYPE html>

<html lang="<?php echo currentLang(); ?>" dir="<?php echo textDirection(); ?>">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>إضافة سيارة للمزاد</title>

    <style>
        :root {
            --royal-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --glass-white: rgba(255, 255, 255, 0.1);
            --glass-border: rgba(255, 255, 255, 0.18);
            --transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Cairo', 'Tajawal', sans-serif;
            background: #0f0c29;
            direction: rtl;
            min-height: 100vh;
        }

        .dynamic-background {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -2;
            background: linear-gradient(125deg, #0f0c29, #302b63, #24243e, #0f0c29);
            background-size: 400% 400%;
            animation: gradientFlow 20s ease infinite;
        }

        @keyframes gradientFlow {
            0% { background-position: 0% 0%; }
            25% { background-position: 100% 0%; }
            50% { background-position: 100% 100%; }
            75% { background-position: 0% 100%; }
            100% { background-position: 0% 0%; }
        }

        .header {
            background: var(--glass-white);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--glass-border);
            color: white;
            padding: 20px 0;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .container {

            max-width: 800px;

            margin: 0 auto;

            padding: 0 20px;

        }

        .header h1 {
            font-size: 1.5rem;
            background: linear-gradient(135deg, #fff, #e0e7ff, #c7d2fe);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .content {

            padding: 40px 20px;

        }

        .form-container {
            background: var(--glass-white);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 30px;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
        }

        .form-title {

            text-align: center;

            margin-bottom: 30px;

        }

        .form-title h2 {
            font-size: 2rem;
            font-weight: 900;
            margin-bottom: 10px;
            background: linear-gradient(135deg, #667eea, #764ba2, #f093fb);
            background-size: 200% 200%;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: titleGradient 3s ease infinite;
        }

        @keyframes titleGradient {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .form-row {

            display: grid;

            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));

            gap: 20px;

            margin-bottom: 20px;

        }

        .form-group {

            margin-bottom: 20px;

        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: rgba(255, 255, 255, 0.9);
            font-weight: 600;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--glass-border);
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            font-size: 1rem;
            transition: 0.3s;
            color: white;
        }

        .form-group textarea {

            min-height: 120px;

            resize: vertical;

        }

        .form-group input:focus,

        .form-group select:focus,

        .form-group textarea:focus {

            outline: none;

            border-color: #2563eb;

            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);

        }

        .duration-input-group {

            display: grid;

            grid-template-columns: 2fr 1fr;

            gap: 10px;

        }

        .btn {
            width: 100%;
            padding: 14px;
            background: var(--royal-gradient);
            color: white;
            border: none;
            border-radius: 15px;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
            margin-top: 10px;
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(102, 126, 234, 0.4);
        }

        .alert {

            padding: 12px 15px;

            border-radius: 8px;

            margin-bottom: 20px;

        }

        .alert-danger {

            background: #fee2e2;

            color: #991b1b;

            border-right: 4px solid #dc2626;

        }

        .alert-success {

            background: #d1fae5;

            color: #065f46;

            border-right: 4px solid #10b981;

        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: 0.3s;
        }

        .back-link:hover {
            color: white;
            transform: translateX(-5px);
        }

    </style>


    <!-- Responsive & Mobile Menu CSS -->
    <link rel="stylesheet" href="assets/css/responsive.css">

</head>

<body>

    <!-- الخلفية الديناميكية -->
    <div class="dynamic-background"></div>

    <!-- Header -->
    <div class="header">
        <div class="container">
            <h1>🚗 مزادات السيارات</h1>
        </div>
    </div>

 

    <div style="position: fixed; top: 20px; left: 20px; z-index: 1000;">

        <?php include 'includes/lang-switcher.php'; ?>

    </div>

    <script src="js/auto-translate.js"></script>

 

    <!-- Content -->

    <div class="content">

        <div class="container">

            <a href="dashboard.php" class="back-link">← العودة للوحة التحكم</a>

 

            <div class="form-container">

                <div class="form-title">

                    <h2>إضافة سيارة للمزاد</h2>

                    <p style="color: #64748b;">املأ البيانات بدقة لجذب المزيد من المزايدين</p>

                </div>

 

                <?php if (!empty($errors)): ?>

                    <div class="alert alert-danger">

                        <strong>⚠️ يرجى تصحيح الأخطاء:</strong>

                        <ul>

                            <?php foreach ($errors as $error): ?>

                                <li><?php echo $error; ?></li>

                            <?php endforeach; ?>

                        </ul>

                    </div>

                <?php endif; ?>

 

                <?php if ($success): ?>

                    <div class="alert alert-success">

                        <strong>✅ <?php echo $success; ?></strong>

                    </div>

                <?php endif; ?>

 

                <form method="POST" action="" enctype="multipart/form-data">

                    <h3 style="margin-bottom: 20px; color: #1e293b;">معلومات السيارة</h3>

 

                    <div class="form-row">

                        <div class="form-group">

                            <label>الماركة *</label>

                            <input type="text" name="brand" placeholder="مثال: BMW" value="<?php echo $_POST['brand'] ?? ''; ?>" required>

                        </div>

 

                        <div class="form-group">

                            <label>الموديل *</label>

                            <input type="text" name="model" placeholder="مثال: X5" value="<?php echo $_POST['model'] ?? ''; ?>" required>

                        </div>

                    </div>

 

                    <div class="form-row">

                        <div class="form-group">

                            <label>سنة الصنع *</label>

                            <input type="number" name="year" placeholder="2020" min="1900" max="<?php echo date('Y')+1; ?>" value="<?php echo $_POST['year'] ?? ''; ?>" required>

                        </div>

 

                        <div class="form-group">

                            <label>الكيلومترات</label>

                            <input type="number" name="mileage" placeholder="50000" value="<?php echo $_POST['mileage'] ?? ''; ?>">

                        </div>

                    </div>

 

                    <div class="form-row">

                        <div class="form-group">

                            <label>اللون</label>

                            <input type="text" name="color" placeholder="أسود" value="<?php echo $_POST['color'] ?? ''; ?>">

                        </div>

 

                        <div class="form-group">

                            <label>ناقل الحركة *</label>

                            <select name="transmission" required>

                                <option value="automatic" <?php echo (isset($_POST['transmission']) && $_POST['transmission']=='automatic')?'selected':''; ?>>أوتوماتيك</option>

                                <option value="manual" <?php echo (isset($_POST['transmission']) && $_POST['transmission']=='manual')?'selected':''; ?>>مانيوال</option>

                            </select>

                        </div>

                    </div>

 

                    <div class="form-group">

                        <label>نوع الوقود *</label>

                        <select name="fuel_type" required>

                            <option value="petrol" <?php echo (isset($_POST['fuel_type']) && $_POST['fuel_type']=='petrol')?'selected':''; ?>>بنزين</option>

                            <option value="diesel" <?php echo (isset($_POST['fuel_type']) && $_POST['fuel_type']=='diesel')?'selected':''; ?>>ديزل</option>

                            <option value="electric" <?php echo (isset($_POST['fuel_type']) && $_POST['fuel_type']=='electric')?'selected':''; ?>>كهربائي</option>

                            <option value="hybrid" <?php echo (isset($_POST['fuel_type']) && $_POST['fuel_type']=='hybrid')?'selected':''; ?>>هايبرد</option>

                        </select>

                    </div>

 

                    <div class="form-group">

                        <label>الوصف</label>

                        <textarea name="description" placeholder="اكتب وصفاً تفصيلياً للسيارة..."><?php echo $_POST['description'] ?? ''; ?></textarea>

                    </div>

 

                    <hr style="margin: 30px 0; border: none; border-top: 2px solid #e2e8f0;">

 

                    <h3 style="margin-bottom: 20px; color: #1e293b;">إعدادات المزاد</h3>

 

                    <div class="form-row">

                        <div class="form-group">

                            <label>السعر الابتدائي ($) *</label>

                            <input type="number" name="starting_price" placeholder="10000" step="0.01" value="<?php echo $_POST['starting_price'] ?? ''; ?>" required>

                        </div>

 

                        <div class="form-group">

                            <label>السعر الاحتياطي ($)</label>

                            <input type="number" name="reserve_price" placeholder="15000" step="0.01" value="<?php echo $_POST['reserve_price'] ?? ''; ?>">

                            <small style="color: #64748b;">السعر الأدنى الذي تقبل البيع به (اختياري)</small>

                        </div>

                    </div>

 

                    <div class="form-row">

                        <div class="form-group">

                            <label>سعر الشراء الفوري ($)</label>

                            <input type="number" name="buy_now_price" placeholder="20000" step="0.01" value="<?php echo $_POST['buy_now_price'] ?? ''; ?>">

                            <small style="color: #64748b;">سعر للشراء المباشر دون مزايدة (اختياري)</small>

                        </div>

 

                        <div class="form-group">

                            <label>مدة المزاد *</label>

                            <div class="duration-input-group">

                                <input

                                    type="number"

                                    name="auction_duration"

                                    placeholder="مثال: 30"

                                    min="1"

                                    value="<?php echo $_POST['auction_duration'] ?? ''; ?>"

                                    required

                                >

                                <select name="duration_unit" required>

                                    <option value="minutes" <?php echo (isset($_POST['duration_unit']) && $_POST['duration_unit']=='minutes')?'selected':''; ?>>دقيقة</option>

                                    <option value="hours" <?php echo (!isset($_POST['duration_unit']) || $_POST['duration_unit']=='hours')?'selected':''; ?>>ساعة</option>

                                </select>

                            </div>

                            <small style="color: #64748b;">مثال: 30 دقيقة، 2 ساعة، 24 ساعة</small>

                        </div>

                    </div>

 

                    <hr style="margin: 30px 0; border: none; border-top: 2px solid #e2e8f0;">

 

                    <h3 style="margin-bottom: 20px; color: #1e293b;">صور السيارة</h3>

 

                    <div class="form-group">

                        <label>رفع الصور (حتى 5 صور) *</label>

                        <input

                            type="file"

                            name="images[]"

                            accept="image/*"

                            multiple

                            required

                            onchange="previewImages(event)"

                        >

                        <small style="color: #64748b; display: block; margin-top: 5px;">

                            اختر حتى 5 صور للسيارة. الصورة الأولى ستكون الصورة الرئيسية.

                        </small>

                    </div>

 

                    <div id="image-preview" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 10px; margin-top: 15px;">

                    </div>

 

                    <button type="submit" class="btn">نشر المزاد الآن 🚀</button>

                </form>

            </div>

        </div>

    </div>

 

    <script>

        function previewImages(event) {

            const preview = document.getElementById('image-preview');

            preview.innerHTML = '';

 

            const files = event.target.files;

            if (files.length > 5) {

                alert('يمكنك رفع 5 صور كحد أقصى');

                event.target.value = '';

                return;

            }

 

            for (let i = 0; i < files.length; i++) {

                const file = files[i];

                const reader = new FileReader();

 

                reader.onload = function(e) {

                    const div = document.createElement('div');

                    div.style.cssText = 'position: relative; border: 2px solid #e2e8f0; border-radius: 8px; overflow: hidden;';

 

                    const img = document.createElement('img');

                    img.src = e.target.result;

                    img.style.cssText = 'width: 100%; height: 150px; object-fit: cover;';

 

                    if (i === 0) {

                        const badge = document.createElement('span');

                        badge.textContent = 'رئيسية';

                        badge.style.cssText = 'position: absolute; top: 5px; right: 5px; background: #2563eb; color: white; padding: 3px 8px; border-radius: 5px; font-size: 0.75rem;';

                        div.appendChild(badge);

                    }

 

                    div.appendChild(img);

                    preview.appendChild(div);

                }

 

                reader.readAsDataURL(file);

            }

        }

    </script>


    <!-- Mobile Menu & Responsive JavaScript -->
    <script src="assets/js/mobile-menu.js"></script>

</body>

</html>