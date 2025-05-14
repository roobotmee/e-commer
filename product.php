<?php
// Include configuration file
require_once 'config.php';

// Include database connection
require_once 'connect.php';

// Check if product ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$product_id = intval($_GET['id']);

// Get product details
$product_query = "SELECT p.*, c.name as category_name FROM products p 
                  LEFT JOIN categories c ON p.category_id = c.id 
                  WHERE p.id = ?";
$stmt = $conn->prepare($product_query);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: index.php');
    exit;
}

$product = $result->fetch_assoc();

// Get product images
$images_query = "SELECT * FROM product_images WHERE product_id = ?";
$stmt = $conn->prepare($images_query);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$images_result = $stmt->get_result();
$product_images = [];

while ($image = $images_result->fetch_assoc()) {
    $product_images[] = $image;
}

// If no additional images, use the main product image
if (empty($product_images)) {
    $product_images[] = ['image_url' => $product['image']];
}

// Get product specifications
$specs_query = "SELECT * FROM product_specifications WHERE product_id = ?";
$stmt = $conn->prepare($specs_query);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$specs_result = $stmt->get_result();
$product_specs = [];

while ($spec = $specs_result->fetch_assoc()) {
    $product_specs[] = $spec;
}

// Get product reviews
$reviews_query = "SELECT r.*, u.username, u.avatar FROM reviews r 
                  LEFT JOIN users u ON r.user_id = u.id 
                  WHERE r.product_id = ? 
                  ORDER BY r.created_at DESC 
                  LIMIT 2";
$stmt = $conn->prepare($reviews_query);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$reviews_result = $stmt->get_result();
$product_reviews = [];

while ($review = $reviews_result->fetch_assoc()) {
    $product_reviews[] = $review;
}

// Get related products
$related_query = "SELECT * FROM products 
                  WHERE category_id = ? AND id != ? 
                  LIMIT 3";
$stmt = $conn->prepare($related_query);
$stmt->bind_param("ii", $product['category_id'], $product_id);
$stmt->execute();
$related_result = $stmt->get_result();
$related_products = [];

while ($related = $related_result->fetch_assoc()) {
    $related_products[] = $related;
}

// Include header
include 'includes/header.php';
?>

<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $product['name']; ?> - Online Do'kon</title>
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
                <button class="back-btn" onclick="history.back()">
                    <i class="fas fa-arrow-left"></i>
                </button>
                <div class="header-title">Mahsulot</div>
                <div class="header-actions">
                    <button class="action-btn share-btn" data-id="<?php echo $product['id']; ?>">
                        <i class="fas fa-share-alt"></i>
                    </button>
                    <button class="action-btn favorite-toggle" data-id="<?php echo $product['id']; ?>">
                        <i class="far fa-heart"></i>
                    </button>
                </div>
            </div>
        </header>

        <!-- App Content -->
        <main class="app-content">
            <div class="product-page">
                <div class="product-gallery">
                    <div class="gallery-main">
                        <img src="<?php echo $product_images[0]['image_url']; ?>" alt="<?php echo $product['name']; ?>" class="main-image">
                    </div>
                    <?php if (count($product_images) > 1): ?>
                    <div class="gallery-thumbs">
                        <?php foreach ($product_images as $index => $image): ?>
                        <div class="thumb <?php echo $index === 0 ? 'active' : ''; ?>" data-src="<?php echo $image['image_url']; ?>">
                            <img src="<?php echo $image['image_url']; ?>" alt="<?php echo $product['name']; ?> Thumbnail">
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="gallery-indicators">
                        <?php foreach ($product_images as $index => $image): ?>
                        <span class="indicator <?php echo $index === 0 ? 'active' : ''; ?>"></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="product-details">
                    <h1 class="product-title"><?php echo $product['name']; ?></h1>
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
                        <span class="rating-count">(<?php echo $product['reviews']; ?> baho)</span>
                    </div>
                    <div class="product-price">
                        <span class="current-price"><?php echo number_format($product['price'], 0, '.', ','); ?> so'm</span>
                        <?php if ($product['old_price'] > 0): ?>
                        <span class="old-price"><?php echo number_format($product['old_price'], 0, '.', ','); ?> so'm</span>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($product['variants'])): ?>
                    <div class="product-options">
                        <div class="product-size">
                            <h3 class="options-title">Xotira hajmi</h3>
                            <div class="size-options">
                                <?php 
                                $variants = json_decode($product['variants'], true);
                                foreach ($variants as $index => $variant): 
                                ?>
                                <label class="size-option <?php echo $index === 1 ? 'active' : ''; ?>">
                                    <input type="radio" name="size" value="<?php echo $variant['value']; ?>" <?php echo $index === 1 ? 'checked' : ''; ?>>
                                    <span><?php echo $variant['name']; ?></span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="payment-tabs">
                        <div class="payment-tab active" data-tab="cash">
                            <div class="tab-icon">
                                <i class="fas fa-money-bill-wave"></i>
                            </div>
                            <div class="tab-content">
                                <div class="tab-text">Naqd pul</div>
                                <div class="tab-price"><?php echo number_format($product['price'], 0, '.', ','); ?> so'm</div>
                            </div>
                        </div>
                        <div class="payment-tab" data-tab="installment">
                            <div class="tab-icon">
                                <i class="fas fa-credit-card"></i>
                            </div>
                            <div class="tab-content">
                                <div class="tab-text">Muddatli to'lov</div>
                                <div class="tab-price"><?php echo number_format($product['price'] / 12, 0, '.', ','); ?> so'm/oy</div>
                            </div>
                        </div>
                    </div>

                    <div class="payment-details" id="cash-details">
                        <div class="payment-detail-header">
                            <h3>Naqd pul orqali to'lov</h3>
                        </div>
                        <div class="payment-detail-content">
                            <div class="detail-item">
                                <span class="detail-label">Narxi:</span>
                                <span class="detail-value"><?php echo number_format($product['old_price'] > 0 ? $product['old_price'] : $product['price'], 0, '.', ','); ?> so'm</span>
                            </div>
                            <?php if ($product['old_price'] > 0): ?>
                            <div class="detail-item">
                                <span class="detail-label">Chegirma:</span>
                                <span class="detail-value">-<?php echo number_format($product['old_price'] - $product['price'], 0, '.', ','); ?> so'm</span>
                            </div>
                            <?php endif; ?>
                            <div class="detail-item total">
                                <span class="detail-label">Jami:</span>
                                <span class="detail-value"><?php echo number_format($product['price'], 0, '.', ','); ?> so'm</span>
                            </div>
                        </div>
                    </div>

                    <div class="payment-details" id="installment-details" style="display: none;">
                        <div class="payment-detail-header">
                            <h3>Muddatli to'lov</h3>
                        </div>
                        <div class="payment-detail-content">
                            <div class="detail-item">
                                <span class="detail-label">Boshlang'ich to'lov:</span>
                                <span class="detail-value">0 so'm</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Oylik to'lov:</span>
                                <span class="detail-value"><?php echo number_format($product['price'] / 12, 0, '.', ','); ?> so'm</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Muddat:</span>
                                <span class="detail-value">12 oy</span>
                            </div>
                            <div class="detail-item total">
                                <span class="detail-label">Jami:</span>
                                <span class="detail-value"><?php echo number_format($product['price'], 0, '.', ','); ?> so'm</span>
                            </div>
                            <button class="tab-btn installment-btn">Muddatli to'lovga xarid qilish</button>
                        </div>
                    </div>

                    <div class="product-description">
                        <h3>Mahsulot haqida</h3>
                        <?php echo $product['description']; ?>
                    </div>

                    <?php if (!empty($product_specs)): ?>
                    <div class="product-specs">
                        <h3>Texnik xususiyatlari</h3>
                        <div class="specs-list">
                            <?php foreach ($product_specs as $spec): ?>
                            <div class="spec-item">
                                <span class="spec-name"><?php echo $spec['name']; ?></span>
                                <span class="spec-value"><?php echo $spec['value']; ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($product_reviews)): ?>
            <section class="reviews-section">
                <div class="section-header">
                    <h2>Izohlar</h2>
                    <a href="reviews.php?product_id=<?php echo $product['id']; ?>" class="view-all">Barchasi (<?php echo $product['reviews']; ?>) <i class="fas fa-chevron-right"></i></a>
                </div>
                <div class="rating-summary">
                    <div class="rating-big">
                        <div class="rating-number"><?php echo number_format($product['rating'], 1); ?></div>
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
                        <div class="rating-count"><?php echo $product['reviews']; ?> baho</div>
                    </div>
                    <div class="rating-bars">
                        <?php 
                        $ratings_distribution = json_decode($product['ratings_distribution'], true);
                        for ($i = 5; $i >= 1; $i--): 
                            $percentage = isset($ratings_distribution[$i]) ? $ratings_distribution[$i] : 0;
                        ?>
                        <div class="rating-bar-item">
                            <span class="rating-label"><?php echo $i; ?></span>
                            <div class="rating-bar">
                                <div class="rating-fill" style="width: <?php echo $percentage; ?>%;"></div>
                            </div>
                            <span class="rating-percent"><?php echo $percentage; ?>%</span>
                        </div>
                        <?php endfor; ?>
                    </div>
                </div>
                <div class="reviews-list">
                    <?php foreach ($product_reviews as $review): ?>
                    <div class="review-item">
                        <div class="reviewer-info">
                            <div class="reviewer-avatar">
                                <img src="<?php echo !empty($review['avatar']) ? $review['avatar'] : 'assets/images/default-avatar.jpg'; ?>" alt="Reviewer">
                            </div>
                            <div class="reviewer-details">
                                <div class="reviewer-name"><?php echo $review['username']; ?></div>
                                <div class="review-date"><?php echo date('d.m.Y', strtotime($review['created_at'])); ?></div>
                            </div>
                            <div class="review-rating">
                                <div class="stars">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <?php if ($i <= $review['rating']): ?>
                                            <i class="fas fa-star"></i>
                                        <?php else: ?>
                                            <i class="far fa-star"></i>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                </div>
                            </div>
                        </div>
                        <div class="review-content">
                            <p><?php echo $review['content']; ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

            <?php if (!empty($related_products)): ?>
            <section class="related-section">
                <div class="section-header">
                    <h2>O'xshash mahsulotlar</h2>
                    <a href="catalog.php?category=<?php echo $product['category_id']; ?>" class="view-all">Barchasi <i class="fas fa-chevron-right"></i></a>
                </div>
                <div class="related-slider">
                    <?php foreach ($related_products as $related): ?>
                    <div class="related-slide">
                        <div class="product-card">
                            <button class="favorite-btn" data-id="<?php echo $related['id']; ?>">
                                <i class="far fa-heart"></i>
                            </button>
                            <a href="product.php?id=<?php echo $related['id']; ?>" class="product-image">
                                <img src="<?php echo $related['image']; ?>" alt="<?php echo $related['name']; ?>">
                            </a>
                            <div class="product-info">
                                <a href="product.php?id=<?php echo $related['id']; ?>" class="product-title"><?php echo $related['name']; ?></a>
                                <div class="product-price">
                                    <span class="current-price"><?php echo number_format($related['price'], 0, '.', ','); ?> so'm</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>
        </main>

        <div class="product-actions-fixed">
            <button class="add-to-cart-btn" data-id="<?php echo $product['id']; ?>">
                <i class="fas fa-shopping-cart"></i>
                Savatga qo'shish
            </button>
            <button class="buy-now-btn" data-id="<?php echo $product['id']; ?>">Hozir sotib olish</button>
        </div>

        <!-- Fullscreen Gallery -->
        <div class="fullscreen-gallery">
            <div class="gallery-container">
                <div class="gallery-header">
                    <div class="gallery-close">
                        <i class="fas fa-times"></i>
                    </div>
                </div>
                <div class="gallery-content">
                    <img src="<?php echo $product_images[0]['image_url']; ?>" alt="<?php echo $product['name']; ?>" class="gallery-image">
                    <?php if (count($product_images) > 1): ?>
                    <div class="gallery-nav gallery-prev">
                        <i class="fas fa-chevron-left"></i>
                    </div>
                    <div class="gallery-nav gallery-next">
                        <i class="fas fa-chevron-right"></i>
                    </div>
                    <?php endif; ?>
                </div>
                <?php if (count($product_images) > 1): ?>
                <div class="gallery-footer">
                    <div class="gallery-thumbnails">
                        <?php foreach ($product_images as $index => $image): ?>
                        <div class="gallery-thumb <?php echo $index === 0 ? 'active' : ''; ?>" data-src="<?php echo $image['image_url']; ?>">
                            <img src="<?php echo $image['image_url']; ?>" alt="<?php echo $product['name']; ?> Thumbnail">
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Installment Modal -->
        <div class="modal" id="installmentModal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Muddatli to'lovga xarid qilish</h3>
                    <button class="modal-close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="installmentForm" action="save-order.php" method="post">
                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                        <input type="hidden" name="payment_type" value="installment">
                        
                        <div class="form-group">
                            <label for="fullName">To'liq ism-familiya</label>
                            <input type="text" id="fullName" name="fullName" required>
                        </div>
                        <div class="form-group">
                            <label for="phoneNumber">Telefon raqami</label>
                            <input type="tel" id="phoneNumber" name="phoneNumber" placeholder="+998 __ ___ __ __" required>
                        </div>
                        <div class="form-group">
                            <label for="passportNumber">Passport seriyasi va raqami</label>
                            <input type="text" id="passportNumber" name="passportNumber" placeholder="AA 1234567" required>
                        </div>
                        <div class="form-group">
                            <label for="address">Manzil</label>
                            <textarea id="address" name="address" rows="3" required></textarea>
                        </div>
                        <div class="form-group">
                            <label>Muddatni tanlang</label>
                            <div class="term-options">
                                <label class="term-option">
                                    <input type="radio" name="term" value="3" data-monthly="<?php echo number_format($product['price'] / 3, 0, '.', ''); ?>">
                                    <span class="term-content">
                                        <span class="term-title">3 oy</span>
                                        <span class="term-price"><?php echo number_format($product['price'] / 3, 0, '.', ','); ?> so'm/oy</span>
                                    </span>
                                </label>
                                <label class="term-option">
                                    <input type="radio" name="term" value="6" data-monthly="<?php echo number_format($product['price'] / 6, 0, '.', ''); ?>">
                                    <span class="term-content">
                                        <span class="term-title">6 oy</span>
                                        <span class="term-price"><?php echo number_format($product['price'] / 6, 0, '.', ','); ?> so'm/oy</span>
                                    </span>
                                </label>
                                <label class="term-option active">
                                    <input type="radio" name="term" value="12" data-monthly="<?php echo number_format($product['price'] / 12, 0, '.', ''); ?>" checked>
                                    <span class="term-content">
                                        <span class="term-title">12 oy</span>
                                        <span class="term-price"><?php echo number_format($product['price'] / 12, 0, '.', ','); ?> so'm/oy</span>
                                    </span>
                                </label>
                                <label class="term-option">
                                    <input type="radio" name="term" value="24" data-monthly="<?php echo number_format($product['price'] / 24, 0, '.', ''); ?>">
                                    <span class="term-content">
                                        <span class="term-title">24 oy</span>
                                        <span class="term-price"><?php echo number_format($product['price'] / 24, 0, '.', ','); ?> so'm/oy</span>
                                    </span>
                                </label>
                            </div>
                        </div>
                        <div class="form-summary">
                            <div class="summary-item">
                                <span class="summary-label">Mahsulot:</span>
                                <span class="summary-value"><?php echo $product['name']; ?></span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Narxi:</span>
                                <span class="summary-value"><?php echo number_format($product['price'], 0, '.', ','); ?> so'm</span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Muddat:</span>
                                <span class="summary-value" id="selectedTerm">12 oy</span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Oylik to'lov:</span>
                                <span class="summary-value" id="monthlyPayment"><?php echo number_format($product['price'] / 12, 0, '.', ','); ?> so'm</span>
                            </div>
                        </div>
                        <button type="submit" class="submit-btn">Buyurtma berish</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/script.js"></script>
    <script>
        // Installment form handling
        document.addEventListener('DOMContentLoaded', function() {
            const termOptions = document.querySelectorAll('input[name="term"]');
            const selectedTermEl = document.getElementById('selectedTerm');
            const monthlyPaymentEl = document.getElementById('monthlyPayment');
            
            termOptions.forEach(option => {
                option.addEventListener('change', function() {
                    const term = this.value;
                    const monthly = this.dataset.monthly;
                    
                    // Update term options
                    document.querySelectorAll('.term-option').forEach(opt => {
                        opt.classList.remove('active');
                    });
                    this.closest('.term-option').classList.add('active');
                    
                    // Update summary
                    selectedTermEl.textContent = term + ' oy';
                    monthlyPaymentEl.textContent = Number(monthly).toLocaleString('uz-UZ') + ' so\'m';
                });
            });
            
            // Installment button
            const installmentBtn = document.querySelector('.installment-btn');
            const installmentModal = document.getElementById('installmentModal');
            const modalClose = document.querySelector('.modal-close');
            
            if (installmentBtn && installmentModal) {
                installmentBtn.addEventListener('click', function() {
                    installmentModal.classList.add('active');
                    document.body.style.overflow = 'hidden';
                });
                
                modalClose.addEventListener('click', function() {
                    installmentModal.classList.remove('active');
                    document.body.style.overflow = '';
                });
                
                // Close modal when clicking outside
                installmentModal.addEventListener('click', function(e) {
                    if (e.target === installmentModal) {
                        installmentModal.classList.remove('active');
                        document.body.style.overflow = '';
                    }
                });
            }
        });
    </script>
</body>
</html>
