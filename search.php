<?php
// تحميل نظام الترجمة
require_once 'includes/translator.php';

require_once 'config/database.php';
require_once 'includes/functions.php';

// استلام معايير البحث
$search_query = isset($_GET['q']) ? clean($_GET['q']) : '';
$brand = isset($_GET['brand']) ? clean($_GET['brand']) : '';
$min_price = isset($_GET['min_price']) ? clean($_GET['min_price']) : '';
$max_price = isset($_GET['max_price']) ? clean($_GET['max_price']) : '';
$year_from = isset($_GET['year_from']) ? clean($_GET['year_from']) : '';
$year_to = isset($_GET['year_to']) ? clean($_GET['year_to']) : '';
$transmission = isset($_GET['transmission']) ? clean($_GET['transmission']) : '';
$fuel_type = isset($_GET['fuel_type']) ? clean($_GET['fuel_type']) : '';
$sort = isset($_GET['sort']) ? clean($_GET['sort']) : 'newest';

// بناء الاستعلام
$sql = "
    SELECT 
        a.*,
        v.brand,
        v.model,
        v.year,
        v.mileage,
        v.transmission,
        v.fuel_type,
        u.username as seller_name,
        (SELECT image_path FROM vehicle_images WHERE vehicle_id = v.id AND is_primary = 1 LIMIT 1) as image
    FROM auctions a
    JOIN vehicles v ON a.vehicle_id = v.id
    JOIN users u ON a.seller_id = u.id
    WHERE a.status = 'active' AND a.end_time > NOW()
";

$params = [];

// فلترة البحث النصي
if (!empty($search_query)) {
    $sql .= " AND (v.brand LIKE ? OR v.model LIKE ? OR CONCAT(v.brand, ' ', v.model) LIKE ?)";
    $search_term = "%$search_query%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

// فلترة الماركة
if (!empty($brand)) {
    $sql .= " AND v.brand = ?";
    $params[] = $brand;
}

// فلترة السعر
if (!empty($min_price)) {
    $sql .= " AND a.current_price >= ?";
    $params[] = $min_price;
}
if (!empty($max_price)) {
    $sql .= " AND a.current_price <= ?";
    $params[] = $max_price;
}

// فلترة السنة
if (!empty($year_from)) {
    $sql .= " AND v.year >= ?";
    $params[] = $year_from;
}
if (!empty($year_to)) {
    $sql .= " AND v.year <= ?";
    $params[] = $year_to;
}

// فلترة ناقل الحركة
if (!empty($transmission)) {
    $sql .= " AND v.transmission = ?";
    $params[] = $transmission;
}

// فلترة نوع الوقود
if (!empty($fuel_type)) {
    $sql .= " AND v.fuel_type = ?";
    $params[] = $fuel_type;
}

// الترتيب
switch ($sort) {
    case 'price_low':
        $sql .= " ORDER BY a.current_price ASC";
        break;
    case 'price_high':
        $sql .= " ORDER BY a.current_price DESC";
        break;
    case 'ending_soon':
        $sql .= " ORDER BY a.end_time ASC";
        break;
    case 'most_bids':
        $sql .= " ORDER BY a.total_bids DESC";
        break;
    default:
        $sql .= " ORDER BY a.created_at DESC";
}

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$results = $stmt->fetchAll();

// جلب قائمة الماركات للفلتر
$brands_stmt = $conn->query("SELECT DISTINCT brand FROM vehicles ORDER BY brand");
$brands = $brands_stmt->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="<?php echo currentLang(); ?>" dir="<?php echo textDirection(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>البحث - مزادات السيارات</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; direction: rtl; }
        .header { background: #2563eb; color: white; padding: 20px 0; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .container { max-width: 1400px; margin: 0 auto; padding: 0 20px; }
        .header h1 { font-size: 1.5rem; }
        .content { padding: 40px 20px; }
        .search-layout { display: grid; grid-template-columns: 280px 1fr; gap: 30px; }
        .filters { background: white; border-radius: 12px; padding: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); height: fit-content; position: sticky; top: 20px; }
        .filters h3 { margin-bottom: 20px; color: #1e293b; }
        .filter-group { margin-bottom: 20px; }
        .filter-group label { display: block; margin-bottom: 8px; color: #1e293b; font-weight: 600; font-size: 0.9rem; }
        .filter-group input, .filter-group select { width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 0.9rem; }
        .filter-group input:focus, .filter-group select:focus { outline: none; border-color: #2563eb; }
        .price-range { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .btn-filter { width: 100%; padding: 12px; background: #2563eb; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; margin-top: 10px; }
        .btn-filter:hover { background: #1d4ed8; }
        .btn-reset { width: 100%; padding: 10px; background: transparent; color: #64748b; border: 2px solid #e2e8f0; border-radius: 8px; cursor: pointer; margin-top: 10px; }
        .btn-reset:hover { background: #f8fafc; }
        .results-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .results-count { font-size: 1.1rem; color: #64748b; }
        .sort-select { padding: 10px 15px; border: 2px solid #e2e8f0; border-radius: 8px; cursor: pointer; }
        .results-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 25px; }
        .auction-card { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); transition: 0.3s; }
        .auction-card:hover { transform: translateY(-5px); box-shadow: 0 5px 20px rgba(0,0,0,0.15); }
        .auction-image { width: 100%; height: 200px; object-fit: cover; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .auction-content { padding: 20px; }
        .auction-title { font-size: 1.2rem; font-weight: bold; color: #1e293b; margin-bottom: 10px; }
        .auction-price { font-size: 1.5rem; font-weight: bold; color: #2563eb; margin-bottom: 10px; }
        .auction-meta { display: flex; gap: 15px; font-size: 0.85rem; color: #64748b; margin-bottom: 15px; }
        .btn { display: block; width: 100%; padding: 12px; background: #2563eb; color: white; text-align: center; text-decoration: none; border-radius: 8px; font-weight: 600; }
        .btn:hover { background: #1d4ed8; }
        .no-results { text-align: center; padding: 60px 20px; background: white; border-radius: 12px; }
        @media (max-width: 768px) { .search-layout { grid-template-columns: 1fr; } .filters { position: static; } }
    </style>

    <!-- Responsive & Mobile Menu CSS -->
    <link rel="stylesheet" href="assets/css/responsive.css">

</head>
<body>
    <div class="header">
                            <div style="position: fixed; top: 20px; left: 20px; z-index: 1000;">
        <?php include 'includes/lang-switcher.php'; ?>
    </div>
    <script src="js/auto-translate.js"></script>

        <div class="container">
            <h1>البحث في المزادات</h1>
                    <a href="dashboard.php" class="back-link">← العودة للوحة التحكم</a>

        </div>
    </div>

    <div class="content">
        <div class="container">
            <div class="search-layout">
                <!-- Filters Sidebar -->
                <aside class="filters">
                    <h3>تصفية النتائج</h3>
                    <form method="GET" action="">
                        <div class="filter-group">
                            <label>البحث</label>
                            <input type="text" name="q" placeholder="ابحث عن سيارة..." value="<?php echo htmlspecialchars($search_query); ?>">
                        </div>

                        <div class="filter-group">
                            <label>الماركة</label>
                            <select name="brand">
                                <option value="">الكل</option>
                                <?php foreach ($brands as $b): ?>
                                    <option value="<?php echo htmlspecialchars($b); ?>" <?php echo $brand == $b ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($b); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label>نطاق السعر ($)</label>
                            <div class="price-range">
                                <input type="number" name="min_price" placeholder="من" value="<?php echo htmlspecialchars($min_price); ?>">
                                <input type="number" name="max_price" placeholder="إلى" value="<?php echo htmlspecialchars($max_price); ?>">
                            </div>
                        </div>

                        <div class="filter-group">
                            <label>سنة الصنع</label>
                            <div class="price-range">
                                <input type="number" name="year_from" placeholder="من" value="<?php echo htmlspecialchars($year_from); ?>" min="1900" max="<?php echo date('Y'); ?>">
                                <input type="number" name="year_to" placeholder="إلى" value="<?php echo htmlspecialchars($year_to); ?>" min="1900" max="<?php echo date('Y'); ?>">
                            </div>
                        </div>

                        <div class="filter-group">
                            <label>ناقل الحركة</label>
                            <select name="transmission">
                                <option value="">الكل</option>
                                <option value="automatic" <?php echo $transmission == 'automatic' ? 'selected' : ''; ?>>أوتوماتيك</option>
                                <option value="manual" <?php echo $transmission == 'manual' ? 'selected' : ''; ?>>مانيوال</option>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label>نوع الوقود</label>
                            <select name="fuel_type">
                                <option value="">الكل</option>
                                <option value="petrol" <?php echo $fuel_type == 'petrol' ? 'selected' : ''; ?>>بنزين</option>
                                <option value="diesel" <?php echo $fuel_type == 'diesel' ? 'selected' : ''; ?>>ديزل</option>
                                <option value="electric" <?php echo $fuel_type == 'electric' ? 'selected' : ''; ?>>كهربائي</option>
                                <option value="hybrid" <?php echo $fuel_type == 'hybrid' ? 'selected' : ''; ?>>هايبرد</option>
                            </select>
                        </div>

                        <button type="submit" class="btn-filter">تطبيق الفلاتر</button>
                        <button type="button" onclick="window.location.href='search.php'" class="btn-reset">إعادة تعيين</button>
                    </form>
                </aside>

                <!-- Results -->
                <main>
                    <div class="results-header">
                        <div class="results-count">
                            <?php echo count($results); ?> نتيجة
                        </div>
                        <form method="GET" style="display: inline;">
                            <?php foreach ($_GET as $key => $value): ?>
                                <?php if ($key != 'sort'): ?>
                                    <input type="hidden" name="<?php echo htmlspecialchars($key); ?>" value="<?php echo htmlspecialchars($value); ?>">
                                <?php endif; ?>
                            <?php endforeach; ?>
                            <select name="sort" class="sort-select" onchange="this.form.submit()">
                                <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>الأحدث</option>
                                <option value="price_low" <?php echo $sort == 'price_low' ? 'selected' : ''; ?>>السعر: من الأقل للأعلى</option>
                                <option value="price_high" <?php echo $sort == 'price_high' ? 'selected' : ''; ?>>السعر: من الأعلى للأقل</option>
                                <option value="ending_soon" <?php echo $sort == 'ending_soon' ? 'selected' : ''; ?>>ينتهي قريباً</option>
                                <option value="most_bids" <?php echo $sort == 'most_bids' ? 'selected' : ''; ?>>الأكثر مزايدة</option>
                            </select>
                        </form>
                    </div>

                    <?php if (empty($results)): ?>
                        <div class="no-results">
                            <div style="font-size: 4rem; margin-bottom: 20px;">🔍</div>
                            <h3 style="color: #1e293b; margin-bottom: 10px;">لا توجد نتائج</h3>
                            <p style="color: #64748b;">جرب تغيير معايير البحث</p>
                        </div>
                    <?php else: ?>
                        <div class="results-grid">
                            <?php foreach ($results as $auction): ?>
                                <div class="auction-card">
                                    <?php if ($auction['image']): ?>
                                        <img src="<?php echo htmlspecialchars($auction['image']); ?>" class="auction-image">
                                    <?php else: ?>
                                        <div class="auction-image" style="display: flex; align-items: center; justify-content: center; color: white; font-size: 3rem;">
                                            🚗
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="auction-content">
                                        <div class="auction-title"><?php echo htmlspecialchars($auction['brand'] . ' ' . $auction['model']); ?></div>
                                        <div class="auction-price"><?php echo number_format($auction['current_price'], 0); ?>$</div>
                                        <div class="auction-meta">
                                            <span>📅 <?php echo $auction['year']; ?></span>
                                            <?php if ($auction['mileage']): ?>
                                                <span>📏 <?php echo number_format($auction['mileage']); ?> كم</span>
                                            <?php endif; ?>
                                            <span>🔨 <?php echo $auction['total_bids']; ?></span>
                                        </div>
                                        <a href="auction-details.php?id=<?php echo $auction['id']; ?>" class="btn">عرض التفاصيل</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </main>
            </div>
        </div>
    </div>

    <!-- Mobile Menu & Responsive JavaScript -->
    <script src="assets/js/mobile-menu.js"></script>

</body>
</html>