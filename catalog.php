<?php
// Include configuration file
require_once 'config.php';

// Include database connection
require_once 'connect.php';

// Initialize variables
$category_id = isset($_GET['category']) ? intval($_GET['category']) : 0;
$featured = isset($_GET['featured']) ? intval($_GET['featured']) : 0;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$per_page = 12;
$offset = ($page - 1) * $per_page;

// Build query based on parameters
$query_params = [];
$where_clauses = [];

if ($category_id > 0) {
    $where_clauses[] = "category_id = ?";
    $query_params[] = $category_id;
}

if ($featured > 0) {
    $where_clauses[] = "featured = 1";
}

if (!empty($search)) {
    $where_clauses[] = "(name LIKE ? OR description LIKE ?)";
    $query_params[] = "%$search%";
    $query_params[] = "%$search%";
}

$where_clause = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Get total products count
$count_query = "SELECT COUNT(*) as total FROM products $where_clause";
$stmt = $conn->prepare($count_query);

if (!empty($query_params)) {
    $types = str_repeat('s', count($query_params));
    $stmt->bind_param($types, ...$query_params);
}

$stmt->execute();
$count_result = $stmt->get_result();
$total_products = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_products / $per_page);

// Get products
$products_query = "SELECT * FROM products $where_clause ORDER BY featured DESC, id DESC LIMIT ?, ?";
$stmt = $conn->prepare($products_query);

$query_params[] = $offset;
$query_params[] = $per_page;

$types = str_repeat('s', count($query_params) - 2) . 'ii';
$stmt->bind_param($types, ...$query_params);

$stmt->execute();
$products_result = $stmt->get_result();
$products = [];

while ($product = $products_result->fetch_assoc()) {
    $products[] = $product;
}

// Get category name if category filter is applied
$category_name = "Mahsulotlar";
if ($category_id > 0) {
    $category_query = "SELECT name FROM categories WHERE id = ?";
    $stmt = $conn->prepare($category_query);
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    $category_result = $stmt->get_result();
    
    if ($category_result->num_rows > 0) {
        $category_name = $category_result->fetch_assoc()['name'];
    }
}

// Get all categories for filter
$all_categories_query = "SELECT * FROM categories ORDER BY name";
$all_categories_result = $conn->query($all_categories_query);
$all_categories = [];

while ($category = $all_categories_result->fetch_assoc()) {
    $all_categories[] = $category;
}

// Include header
include 'includes/header.php';
?>

<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $category_name; ?> - Online Do'kon</title>
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
                <div class="header-title"><?php echo $category_name; ?></div>
                <div class="header-actions">
                    <button class="action-btn filter-toggle">
                        <i class="fas fa-filter"></i>
                    </button>
                    <button class="action-btn sort-toggle">
                        <i class="fas fa-sort"></i>
                    </button>
                </div>
            </div>
        </header>

        <!-- App Content -->
        <main class="app-content">
            <div class="catalog-page">
                <div class="categories-slider">
                    <div class="category-item <?php echo $category_id === 0 ? 'active' : ''; ?>">
                        <a href="catalog.php">
                            <div class="category-icon">
                                <i class="fas fa-th-large"></i>
                            </div>
                            <span>Barchasi</span>
                        </a>
                    </div>
                    <?php foreach ($all_categories as $cat): ?>
                    <div class="category-item <?php echo $category_id === $cat['id'] ? 'active' : ''; ?>">
                        <a href="catalog.php?category=<?php echo $cat['id']; ?>">
                            <div class="category-icon">
                                <i class="<?php echo $cat['icon']; ?>"></i>
                            </div>
                            <span><?php echo $cat['name']; ?></span>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="filter-panel">
                    <div class="filter-header">
                        <div class="filter-title">Filtrlash</div>
                        <div class="filter-clear">Tozalash</div>
                    </div>
                    <form action="catalog.php" method="get" id="filterForm">
                        <?php if ($category_id > 0): ?>
                        <input type="hidden" name="category" value="<?php echo $category_id; ?>">
                        <?php endif; ?>
                        
                        <div class="filter-section">
                            <div class="filter-section-title">Narx</div>
                            <div class="price-range">
                                <div class="price-input">
                                    <input type="number" name="min_price" placeholder="dan" min="0" value="<?php echo isset($_GET['min_price']) ? intval($_GET['min_price']) : ''; ?>">
                                </div>
                                <div class="price-separator">-</div>
                                <div class="price-input">
                                    <input type="number" name="max_price" placeholder="gacha" min="0" value="<?php echo isset($_GET['max_price']) ? intval($_GET['max_price']) : ''; ?>">
                                </div>
                            </div>
                        </div>
                        
                        <?php if ($category_id === 1): // Telefonlar ?>
                        <div class="filter-section">
                            <div class="filter-section-title">Xotira</div>
                            <div class="filter-options">
                                <div class="filter-option <?php echo isset($_GET['memory']) && $_GET['memory'] === '32' ? 'active' : ''; ?>" data-value="32">32GB</div>
                                <div class="filter-option <?php echo isset($_GET['memory']) && $_GET['memory'] === '64' ? 'active' : ''; ?>" data-value="64">64GB</div>
                                <div class="filter-option <?php echo isset($_GET['memory']) && $_GET['memory'] === '128' ? 'active' : ''; ?>" data-value="128">128GB</div>
                                <div class="filter-option <?php echo isset($_GET['memory']) && $_GET['memory'] === '256' ? 'active' : ''; ?>" data-value="256">256GB</div>
                                <div class="filter-option <?php echo isset($_GET['memory']) && $_GET['memory'] === '512' ? 'active' : ''; ?>" data-value="512">512GB</div>
                                <div class="filter-option <?php echo isset($_GET['memory']) && $_GET['memory'] === '1024' ? 'active' : ''; ?>" data-value="1024">1TB</div>
                            </div>
                            <input type="hidden" name="memory" id="memoryFilter" value="<?php echo isset($_GET['memory']) ? $_GET['memory'] : ''; ?>">
                        </div>
                        <?php endif; ?>
                        
                        <div class="filter-section">
                            <div class="filter-section-title">Rang</div>
                            <div class="filter-options">
                                <div class="filter-option <?php echo isset($_GET['color']) && $_GET['color'] === 'black' ? 'active' : ''; ?>" data-value="black">Qora</div>
                                <div class="filter-option <?php echo isset($_GET['color']) && $_GET['color'] === 'white' ? 'active' : ''; ?>" data-value="white">Oq</div>
                                <div class="filter-option <?php echo isset($_GET['color']) && $_GET['color'] === 'blue' ? 'active' : ''; ?>" data-value="blue">Ko'k</div>
                                <div class="filter-option <?php echo isset($_GET['color']) && $_GET['color'] === 'red' ? 'active' : ''; ?>" data-value="red">Qizil</div>
                                <div class="filter-option <?php echo isset($_GET['color']) && $_GET['color'] === 'green' ? 'active' : ''; ?>" data-value="green">Yashil</div>
                                <div class="filter-option <?php echo isset($_GET['color']) && $_GET['color'] === 'yellow' ? 'active' : ''; ?>" data-value="yellow">Sariq</div>
                            </div>
                            <input type="hidden" name="color" id="colorFilter" value="<?php echo isset($_GET['color']) ? $_GET['color'] : ''; ?>">
                        </div>
                        
                        <div class="filter-actions">
                            <button type="submit" class="filter-btn filter-apply">Qo'llash</button>
                        </div>
                    </form>
                </div>

                <?php if (empty($products)): ?>
                <div class="no-products">
                    <div class="no-products-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <h3>Mahsulotlar topilmadi</h3>
                    <p>Boshqa parametrlar bilan qidirib ko'ring</p>
                </div>
                <?php else: ?>
                <div class="products-grid">
                    <?php foreach ($products as $product): ?>
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

                <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                    <a href="<?php echo $_SERVER['PHP_SELF'] . '?' . http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" class="pagination-link">
                        <i class="fas fa-chevron-left"></i> Oldingi
                    </a>
                    <?php else: ?>
                    <span class="pagination-link disabled">
                        <i class="fas fa-chevron-left"></i> Oldingi
                    </span>
                    <?php endif; ?>
                    
                    <div class="page-info"><?php echo $page; ?> / <?php echo $total_pages; ?></div>
                    
                    <?php if ($page < $total_pages): ?>
                    <a href="<?php echo $_SERVER['PHP_SELF'] . '?' . http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" class="pagination-link">
                        Keyingi <i class="fas fa-chevron-right"></i>
                    </a>
                    <?php else: ?>
                    <span class="pagination-link disabled">
                        Keyingi <i class="fas fa-chevron-right"></i>
                    </span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </main>

        <!-- App Navigation Bar -->
        <nav class="app-navbar">
            <a href="index.php" class="nav-item">
                <i class="fas fa-home"></i>
                <span>Bosh sahifa</span>
            </a>
            <a href="categories.php" class="nav-item active">
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

        <!-- Sort Modal -->
        <div class="modal" id="sortModal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Saralash</h3>
                    <button class="modal-close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="catalog.php" method="get" id="sortForm">
                        <?php foreach ($_GET as $key => $value): ?>
                            <?php if ($key !== 'sort'): ?>
                            <input type="hidden" name="<?php echo $key; ?>" value="<?php echo $value; ?>">
                            <?php endif; ?>
                        <?php endforeach; ?>
                        
                        <div class="sort-options">
                            <label class="sort-option <?php echo !isset($_GET['sort']) || $_GET['sort'] === 'popular' ? 'active' : ''; ?>">
                                <input type="radio" name="sort" value="popular" <?php echo !isset($_GET['sort']) || $_GET['sort'] === 'popular' ? 'checked' : ''; ?>>
                                <span>Mashhur</span>
                            </label>
                            <label class="sort-option <?php echo isset($_GET['sort']) && $_GET['sort'] === 'newest' ? 'active' : ''; ?>">
                                <input type="radio" name="sort" value="newest" <?php echo isset($_GET['sort']) && $_GET['sort'] === 'newest' ? 'checked' : ''; ?>>
                                <span>Yangi</span>
                            </label>
                            <label class="sort-option <?php echo isset($_GET['sort']) && $_GET['sort'] === 'price_asc' ? 'active' : ''; ?>">
                                <input type="radio" name="sort" value="price_asc" <?php echo isset($_GET['sort']) && $_GET['sort'] === 'price_asc' ? 'checked' : ''; ?>>
                                <span>Narx: arzondan qimmatga</span>
                            </label>
                            <label class="sort-option <?php echo isset($_GET['sort']) && $_GET['sort'] === 'price_desc' ? 'active' : ''; ?>">
                                <input type="radio" name="sort" value="price_desc" <?php echo isset($_GET['sort']) && $_GET['sort'] === 'price_desc' ? 'checked' : ''; ?>>
                                <span>Narx: qimmatdan arzon</span>
                            </label>
                        </div>
                        
                        <div class="sort-actions">
                            <button type="submit" class="sort-apply-btn">Qo'llash</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/script.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Filter options
            const filterOptions = document.querySelectorAll('.filter-option');
            filterOptions.forEach(option => {
                option.addEventListener('click', function() {
                    const filterType = this.parentElement.nextElementSibling.id;
                    const filterValue = this.dataset.value;
                    
                    // Toggle active class
                    if (filterType === 'memoryFilter' || filterType === 'colorFilter') {
                        // For single selection filters
                        this.parentElement.querySelectorAll('.filter-option').forEach(opt => {
                            opt.classList.remove('active');
                        });
                        this.classList.add('active');
                        document.getElementById(filterType).value = filterValue;
                    } else {
                        // For multi-selection filters
                        this.classList.toggle('active');
                        
                        // Update hidden input value
                        const activeOptions = Array.from(this.parentElement.querySelectorAll('.filter-option.active'))
                            .map(opt => opt.dataset.value);
                        document.getElementById(filterType).value = activeOptions.join(',');
                    }
                });
            });
            
            // Filter clear button
            const filterClearBtn = document.querySelector('.filter-clear');
            if (filterClearBtn) {
                filterClearBtn.addEventListener('click', function() {
                    window.location.href = 'catalog.php' + (<?php echo $category_id; ?> > 0 ? '?category=<?php echo $category_id; ?>' : '');
                });
            }
            
            // Sort modal
            const sortToggleBtn = document.querySelector('.sort-toggle');
            const sortModal = document.getElementById('sortModal');
            const sortModalClose = sortModal.querySelector('.modal-close');
            
            if (sortToggleBtn && sortModal) {
                sortToggleBtn.addEventListener('click', function() {
                    sortModal.classList.add('active');
                    document.body.style.overflow = 'hidden';
                });
                
                sortModalClose.addEventListener('click', function() {
                    sortModal.classList.remove('active');
                    document.body.style.overflow = '';
                });
                
                // Close modal when clicking outside
                sortModal.addEventListener('click', function(e) {
                    if (e.target === sortModal) {
                        sortModal.classList.remove('active');
                        document.body.style.overflow = '';
                    }
                });
                
                // Auto-submit sort form when option changes
                const sortOptions = document.querySelectorAll('input[name="sort"]');
                sortOptions.forEach(option => {
                    option.addEventListener('change', function() {
                        document.getElementById('sortForm').submit();
                    });
                });
            }
            
            // Filter toggle
            const filterToggleBtn = document.querySelector('.filter-toggle');
            const filterPanel = document.querySelector('.filter-panel');
            
            if (filterToggleBtn && filterPanel) {
                filterToggleBtn.addEventListener('click', function() {
                    filterPanel.classList.toggle('active');
                });
            }
        });
    </script>
</body>
</html>
