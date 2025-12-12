<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

if (isLoggedIn()) {
    if (isDM()) {
        header('Location: /admin/');
    } else {
        header('Location: /player/');
    }
    exit();
}

$error = '';
$success = '';
$showRegister = isset($_GET['register']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['register'])) {
        // Registration
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        
        if (empty($username) || empty($email) || empty($password)) {
            $error = 'All fields are required';
        } elseif ($password !== $confirm) {
            $error = 'Passwords do not match';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters';
        } else {
            if (register($username, $email, $password)) {
                $success = 'Account created! You can now login.';
                $showRegister = false;
            } else {
                $error = 'Username or email already exists';
            }
        }
    } else {
        // Login
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        
        if (login($username, $password)) {
            if (isDM()) {
                header('Location: /admin/');
            } else {
                header('Location: /player/');
            }
            exit();
        } else {
            $error = 'Invalid username or password';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $showRegister ? 'Register' : 'Login'; ?> - Achryon</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#f97316',
                        'primary-dark': '#ea580c',
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gradient-to-br from-gray-950 via-gray-900 to-gray-950 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <!-- Logo/Header -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-primary/20 rounded-2xl border-2 border-primary mb-4">
                <svg class="w-10 h-10 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
            </div>
            <h1 class="text-4xl font-bold text-white mb-2">Achryon</h1>
        </div>

        <!-- Auth Card -->
        <div class="bg-gray-900/80 backdrop-blur-sm rounded-xl border border-gray-800 shadow-2xl p-8">
            <?php if ($error): ?>
                <div class="bg-red-500/10 border border-red-500 text-red-400 px-4 py-3 rounded-lg mb-6">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="bg-green-500/10 border border-green-500 text-green-400 px-4 py-3 rounded-lg mb-6">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($showRegister): ?>
                <!-- Registration Form -->
                <form method="POST" action="?register">
                    <input type="hidden" name="register" value="1">
                    
                    <div class="mb-5">
                        <label class="block text-gray-300 mb-2 text-sm font-medium">Username</label>
                        <input 
                            type="text" 
                            name="username" 
                            required 
                            class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/50 transition"
                            placeholder="Choose a username"
                        >
                    </div>
                    
                    <div class="mb-5">
                        <label class="block text-gray-300 mb-2 text-sm font-medium">Email</label>
                        <input 
                            type="email" 
                            name="email" 
                            required 
                            class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/50 transition"
                            placeholder="your@email.com"
                        >
                    </div>
                    
                    <div class="mb-5">
                        <label class="block text-gray-300 mb-2 text-sm font-medium">Password</label>
                        <input 
                            type="password" 
                            name="password" 
                            required 
                            minlength="6"
                            class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/50 transition"
                            placeholder="Minimum 6 characters"
                        >
                    </div>
                    
                    <div class="mb-6">
                        <label class="block text-gray-300 mb-2 text-sm font-medium">Confirm Password</label>
                        <input 
                            type="password" 
                            name="confirm_password" 
                            required 
                            class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/50 transition"
                            placeholder="Re-enter password"
                        >
                    </div>
                    
                    <button 
                        type="submit" 
                        class="w-full bg-primary hover:bg-primary-dark text-white font-bold py-3 px-4 rounded-lg transition duration-200 transform hover:scale-[1.02] active:scale-[0.98]"
                    >
                        Create Account
                    </button>
                    
                    <p class="text-center text-gray-400 mt-6 text-sm">
                        Already have an account? 
                        <a href="login.php" class="text-primary hover:text-primary-dark font-medium">Login here</a>
                    </p>
                </form>
            <?php else: ?>
                <!-- Login Form -->
                <form method="POST" action="">
                    <div class="mb-5">
                        <label class="block text-gray-300 mb-2 text-sm font-medium">Username or Email</label>
                        <input 
                            type="text" 
                            name="username" 
                            required 
                            class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/50 transition"
                            placeholder="Enter username or email"
                        >
                    </div>
                    
                    <div class="mb-6">
                        <label class="block text-gray-300 mb-2 text-sm font-medium">Password</label>
                        <input 
                            type="password" 
                            name="password" 
                            required 
                            class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg text-white focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/50 transition"
                            placeholder="Enter password"
                        >
                    </div>
                    
                    <button 
                        type="submit" 
                        class="w-full bg-primary hover:bg-primary-dark text-white font-bold py-3 px-4 rounded-lg transition duration-200 transform hover:scale-[1.02] active:scale-[0.98]"
                    >
                        Login to Continue
                    </button>
                    
                    <p class="text-center text-gray-400 mt-6 text-sm">
                        Don't have an account? 
                        <a href="?register" class="text-primary hover:text-primary-dark font-medium">Register here</a>
                    </p>
                </form>
            <?php endif; ?>
            
            <div class="mt-6 pt-6 border-t border-gray-800">
                <p class="text-center text-gray-500 text-xs">
                    Thank you for playing!
                </p>
            </div>
        </div>
    </div>
</body>
</html>
