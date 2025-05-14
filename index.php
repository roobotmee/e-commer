<?php
// Include configuration file
require_once 'config.php';

// Include database connection
require_once 'connect.php';

// Include header
include 'includes/header.php';

// Get featured products
$featured_query = "SELECT * FROM products WHERE featured = 1 LIMIT 4";
$featured_result = $conn->query($featured_query);
$featured_products = [];
if ($featured_result && $featured_result->num_rows > 0) {
    while ($row = $featured_result->fetch_assoc()) {
        $featured_products[] = $row;
    }
}

// Get flash sale products
$flash_query = "SELECT * FROM products WHERE sale = 1 LIMIT 4";
$flash_result = $conn->query($flash_query);
$flash_products = [];
if ($flash_result && $flash_result->num_rows > 0) {
    while ($row = $flash_result->fetch_assoc()) {
        $flash_products[] = $row;
    }
}

// Get categories
$categories_query = "SELECT * FROM categories LIMIT 5";
$categories_result = $conn->query($categories_query);
$categories = [];
if ($categories_result && $categories_result->num_rows > 0) {
    while ($row = $categories_result->fetch_assoc()) {
        $categories[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Do'kon</title>
    <link rel="stylesheet" href="styles/style.css">
    <link rel="stylesheet" href="styles/animations.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="app-container">
        <!-- Status Bar -->
        <div class="status-bar">
            <div class="status-time">12:30</div>
            <div class="status-icons">
                <i class="fas fa-signal"></i>
                <i class="fas fa-wifi"></i>
                <i class="fas fa-battery-full"></i>
            </div>
        </div>

        <!-- App Header -->
        <header class="app-header">
            <div class="header-content">
                <div class="logo">SossMM</div>
                <div class="search-container">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Qidirish...">
                    </div>
                </div>
                <div class="header-actions">
                    <button class="action-btn">
                        <i class="fas fa-bell"></i>
                    </button>
                    <button class="action-btn">
                        <i class="fas fa-heart"></i>
                    </button>
                    <button class="action-btn theme-toggle">
                        <i class="fas fa-moon"></i>
                    </button>
                </div>
            </div>
        </header>

        <!-- App Content -->
        <main class="app-content">
            <!-- Banner Slider -->
            <div class="banner-slider">
                <div class="banner active">
                    <div class="banner-content">
                        <h2>Yangi kolleksiya</h2>
                        <p>Eng so'nggi mahsulotlarimizni ko'ring</p>
                        <button class="banner-btn">Ko'proq</button>
                    </div>
                    <div class="banner-image">
                        <img src="assets/images/banner1.jpg" alt="Banner 1">
                    </div>
                </div>
                <div class="banner">
                    <div class="banner-content">
                        <h2>Maxsus takliflar</h2>
                        <p>Chegirmalar va aksiyalar</p>
                        <button class="banner-btn">Ko'rish</button>
                    </div>
                    <div class="banner-image">
                        <img src="assets/images/banner2.jpg" alt="Banner 2">
                    </div>
                </div>
                <div class="banner">
                    <div class="banner-content">
                        <h2>Yangi mavsum</h2>
                        <p>Eng so'nggi trendlar</p>
                        <button class="banner-btn">Xarid qilish</button>
                    </div>
                    <div class="banner-image">
                        <img src="assets/images/banner3.jpg" alt="Banner 3">
                    </div>
                </div>
                <div class="banner-indicators">
                    <span class="indicator active"></span>
                    <span class="indicator"></span>
                    <span class="indicator"></span>
                </div>
            </div>

            <!-- Categories Section -->
            <section class="categories-section">
                <div class="section-header">
                    <h2>Kategoriyalar</h2>
                    <a href="categories.php" class="view-all">Barchasi <i class="fas fa-chevron-right"></i></a>
                </div>
                <div class="categories-grid">
                    <?php foreach ($categories as $category): ?>
                    <a href="catalog.php?category=<?php echo $category['id']; ?>" class="category-card">
                        <div class="category-icon">
                            <i class="<?php echo $category['icon']; ?>"></i>
                        </div>
                        <span><?php echo $category['name']; ?></span>
                    </a>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- Featured Products Section -->
            <section class="products-section">
                <div class="section-header">
                    <h2>Tavsiya etilgan mahsulotlar</h2>
                    <a href="catalog.php?featured=1" class="view-all">Barchasi <i class="fas fa-chevron-right"></i></a>
                </div>
                <div class="products-grid">
                    <?php foreach ($featured_products as $product): ?>
                    <div class="product-card">
                        <?php if ($product['is_new']): ?>
                        <div class="product-badge">Yangi</div>
                        <?php endif; ?>
                        <?php if ($product['discount'] > 0): ?>
                        <div class="product-badge sale">-<?php echo $product['discount']; ?>%</div>
                        <?php endif; ?>
                        <button class="favorite-btn" data-id="<?php echo $product['id']; ?>">
                            <i class="far fa-heart"></i>
                        </button>
                        <a href="product.php?id=<?php echo $product['id']; ?>" class="product-image">
                            <img src="<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>">
                        </a>
                        <div class="product-info">
                            <a href="product.php?id=<?php echo $product['id']; ?>" class="product-title"><?php echo $product['name']; ?></a>
                            <div class="product-price">
                                <span class="current-price"><?php echo number_format($product['price'], 0, '.', ','); ?> so'm</span>
                                <?php if ($product['old_price'] > 0): ?>
                                <span class="old-price"><?php echo number_format($product['old_price'], 0, '.', ','); ?> so'm</span>
                                <?php endif; ?>
                            </div>
                            <div class="product-rating">
                                <div class="stars">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <?php if ($i <= floor($product['rating'])): ?>
                                            <i class="fas fa-star"></i>
                                        <?php elseif ($i - 0.5 <= $product['rating']): ?>
                                            <i class="fas fa-star-half-alt"></i>
                                        <?php else: ?>
                                            <i class="far fa-star"></i>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                </div>
                                <span class="rating-count">(<?php echo $product['reviews']; ?>)</span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- Flash Sale Section -->
            <section class="flash-sale-section">
                <div class="section-header">
                    <h2>Flash Chegirmalar</h2>
                    <div class="countdown">
                        <span class="countdown-label">Tugashiga:</span>
                        <div class="countdown-timer">
                            <div class="countdown-item">
                                <span class="countdown-value hours">05</span>
                                <span class="countdown-unit">soat</span>
                            </div>
                            <div class="countdown-separator">:</div>
                            <div class="countdown-item">
                                <span class="countdown-value minutes">45</span>
                                <span class="countdown-unit">daqiqa</span>
                            </div>
                            <div class="countdown-separator">:</div>
                            <div class="countdown-item">
                                <span class="countdown-value seconds">30</span>
                                <span class="countdown-unit">soniya</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="products-slider">
                    <?php foreach ($flash_products as $product): ?>
                    <div class="product-slide">
                        <div class="product-card">
                            <div class="product-badge sale">-<?php echo $product['discount']; ?>%</div>
                            <button class="favorite-btn" data-id="<?php echo $product['id']; ?>">
                                <i class="far fa-heart"></i>
                            </button>
                            <a href="product.php?id=<?php echo $product['id']; ?>" class="product-image">
                                <img src="<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>">
                            </a>
                            <div class="product-info">
                                <a href="product.php?id=<?php echo $product['id']; ?>" class="product-title"><?php echo $product['name']; ?></a>
                                <div class="product-price">
                                    <span class="current-price"><?php echo number_format($product['price'], 0, '.', ','); ?> so'm</span>
                                    <span class="old-price"><?php echo number_format($product['old_price'], 0, '.', ','); ?> so'm</span>
                                </div>
                                <div class="product-rating">
                                    <div class="stars">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <?php if ($i <= floor($product['rating'])): ?>
                                                <i class="fas fa-star"></i>
                                            <?php elseif ($i - 0.5 <= $product['rating']): ?>
                                                <i class="fas fa-star-half-alt"></i>
                                            <?php else: ?>
                                                <i class="far fa-star"></i>
                                            <?php endif; ?>
                                        <?php endfor; ?>
                                    </div>
                                    <span class="rating-count">(<?php echo $product['reviews']; ?>)</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- Features Section -->
            <section class="features-section">
                <div class="feature">
                    <div class="feature-icon">
                        <i class="fas fa-truck"></i>
                    </div>
                    <div class="feature-text">
                        <h3>Bepul yetkazib berish</h3>
                        <p>100,000 so'mdan yuqori buyurtmalar uchun</p>
                    </div>
                </div>
                <div class="feature">
                    <div class="feature-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <div class="feature-text">
                        <h3>Xavfsiz to'lov</h3>
                        <p>100% xavfsiz to'lov</p>
                    </div>
                </div>
                <div class="feature">
                    <div class="feature-icon">
                        <i class="fas fa-headset"></i>
                    </div>
                    <div class="feature-text">
                        <h3>24/7 qo'llab-quvvatlash</h3>
                        <p>Onlayn yordam</p>
                    </div>
                </div>
            </section>
        </main>

        <!-- App Navigation Bar -->
        <nav class="app-navbar">
            <a href="index.php" class="nav-item active">
                <i class="fas fa-home"></i>
                <span>Bosh sahifa</span>
            </a>
            <a href="categories.php" class="nav-item">
                <i class="fas fa-th-large"></i>
                <span>Kategoriyalar</span>
            </a>
            <a href="cart.php" class="nav-item">
                <i class="fas fa-shopping-cart"></i>
                <span>Savat</span>
            </a>
            <a href="favorites.php" class="nav-item">
                <i class="fas fa-heart"></i>
                <span>Sevimlilar</span>
            </a>
            <a href="profile.php" class="nav-item">
                <i class="fas fa-user"></i>
                <span>Profil</span>
            </a>
        </nav>
    </div>

    <script src="assets/js/script.js"></script>
</body>
</html>
