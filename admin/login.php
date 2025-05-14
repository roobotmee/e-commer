<?php
// Sessiyani boshlash
session_start();

// Agar allaqachon tizimga kirgan bo'lsa
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: index.php');
    exit;
}

// O'zgaruvchilarni ishga tushirish
$username = '';
$error = '';

// Login formani qayta ishlash
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Konfiguratsiya faylini qo'shish
    require_once '../config.php';
    
    // Forma ma'lumotlarini olish
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    // Ma'lumotlarni tekshirish
    if (empty($username) || empty($password)) {
        $error = 'Iltimos, foydalanuvchi nomi va parolni kiriting';
    } else {
        // Ma'lumotlar bazasiga ulanish
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        // Ulanishni tekshirish
        if ($conn->connect_error) {
            die("Ulanish xatosi: " . $conn->connect_error);
        }
        
        // So'rovni tayyorlash
        $stmt = $conn->prepare("SELECT id, username, password, full_name FROM admins WHERE username = ? AND status = 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Parolni tekshirish
            if (password_verify($password, $user['password'])) {
                // Parol to'g'ri, sessiya o'zgaruvchilarini o'rnatish
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $user['id'];
                $_SESSION['admin_username'] = $user['username'];
                $_SESSION['admin_full_name'] = $user['full_name'];
                
                // So'nggi kirish vaqtini yangilash
                $update_stmt = $conn->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?");
                $update_stmt->bind_param("i", $user['id']);
                $update_stmt->execute();
                $update_stmt->close();
                
                // Boshqaruv paneliga yo'naltirish
                header('Location: index.php');
                exit;
            } else {
                $error = 'Noto\'g\'ri foydalanuvchi nomi yoki parol';
            }
        } else {
            $error = 'Noto\'g\'ri foydalanuvchi nomi yoki parol';
        }
        
        // So'rov va ulanishni yopish
        $stmt->close();
        $conn->close();
    }
}
?>

<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin kirish - SossMM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #6750a4;
            --primary-light: #e8def8;
            --background-color: #f9f9f9;
            --card-bg: #ffffff;
            --text-color: #1c1b1f;
            --text-light: #49454f;
            --border-color: #e0e0e0;
            --danger-color: #f44336;
            --radius: 8px;
            --shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--background-color);
            color: var(--text-color);
            line-height: 1.6;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .login-container {
            width: 100%;
            max-width: 400px;
            padding: 20px;
        }

        .login-card {
            background-color: var(--card-bg);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 30px;
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-logo {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .login-title {
            font-size: 18px;
            color: var(--text-light);
        }

        .login-form {
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            font-size: 16px;
            transition: border-color 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        .login-btn {
            width: 100%;
            padding: 12px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: var(--radius);
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .login-btn:hover {
            background-color: #5a4690;
        }

        .error-message {
            background-color: #ffebee;
            color: var(--danger-color);
            padding: 10px 15px;
            border-radius: var(--radius);
            margin-bottom: 20px;
            font-size: 14px;
        }

        .login-footer {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: var(--text-light);
        }

        .back-link {
            color: var(--primary-color);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 10px;
            }
            
            .login-card {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="login-logo">
                    <i class="fas fa-store"></i>
                    <span>SossMM</span>
                </div>
                <h1 class="login-title">Admin kirish</h1>
            </div>
            
            <?php if (!empty($error)): ?>
            <div class="error-message">
                <?php echo $error; ?>
            </div>
            <?php endif; ?>
            
            <form class="login-form" method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                <div class="form-group">
                    <label for="username" class="form-label">Foydalanuvchi nomi</label>
                    <input type="text" id="username" name="username" class="form-control" value="<?php echo htmlspecialchars($username); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">Parol</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>
                
                <button type="submit" class="login-btn">Kirish</button>
            </form>
            
            <div class="login-footer">
                <a href="../index.php" class="back-link">
                    <i class="fas fa-arrow-left"></i> Saytga qaytish
                </a>
            </div>
        </div>
    </div>
</body>
</html>