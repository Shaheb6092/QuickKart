<?php
require_once 'common/config.php';

// if the visitor already has a user session we shouldn't show the form again
// (this can happen if an administrator browses back to the front end).  send
// them straight to the shop home page.
if (isset($_SESSION['user_id']) && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$response = ['status' => 'error', 'message' => 'An unknown error occurred.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    if ($_POST['action'] === 'login') {
        // make sure any existing admin login is cleared before attempting a
        // customer login; otherwise the two roles can collide in the same
        // session which was causing users to be unable to sign in if they
        // had previously opened the admin panel.
        unset($_SESSION['admin_id'], $_SESSION['admin_user']);

        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $response['message'] = 'Please fill in all fields.';
        } else {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                // choose where to send the user after signing in; default to
                // homepage but honour a redirect parameter when provided.
                $redirect = 'index.php';
                if (!empty($_GET['redirect'])) {
                    // basic validation – only allow local paths without domain
                    $r = trim($_GET['redirect']);
                    if (strpos($r, '/') !== 0 && strpos($r, 'http') === false) {
                        $redirect = $r;
                    }
                }
                $response = ['status' => 'success', 'message' => 'Login successful!', 'redirect' => $redirect];
            } else {
                // if there was no matching customer account, check the admin
                // table – many people try to reuse the same credentials on
                // both sides and get confused when the frontend rejects them.
                if (!$user) {
                    $admin_check = $pdo->prepare("SELECT id FROM admin WHERE username = ?");
                    $admin_check->execute([$email]);
                    if ($admin_check->fetch()) {
                        $response['message'] = 'Those credentials belong to an administrator. Use the admin login page instead.';
                    } else {
                        $response['message'] = 'Invalid email or password.';
                    }
                } else {
                    $response['message'] = 'Invalid email or password.';
                }
            }
        }
    } elseif ($_POST['action'] === 'signup') {
        $name = $_POST['name'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        if (empty($name) || empty($phone) || empty($email) || empty($password)) {
            $response['message'] = 'Please fill all required fields.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $response['message'] = 'Invalid email format.';
        } else {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR phone = ?");
            $stmt->execute([$email, $phone]);
            if ($stmt->fetch()) {
                $response['message'] = 'Email or phone number already registered.';
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (name, phone, email, password) VALUES (?, ?, ?, ?)");
                if ($stmt->execute([$name, $phone, $email, $hashed_password])) {
                    $response = ['status' => 'success', 'message' => 'Signup successful! Please login.'];
                } else {
                    $response['message'] = 'Could not register user. Please try again.';
                }
            }
        }
    }
    echo json_encode($response);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Login / Sign Up - Quick Kart</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.min.css">
    <style>
        body { -webkit-user-select: none; user-select: none; -webkit-tap-highlight-color: transparent; }
    </style>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="w-full max-w-sm mx-auto bg-white p-6 rounded-2xl shadow-xl">
        <div class="text-center mb-6">
            <h1 class="text-3xl font-bold text-indigo-600"><span><i class="fas fa-shopping-cart"></i></span>&nbsp;Quick Kart</h1>
            <p class="text-gray-500">Create or Login Account to get started.</p>
        </div>
        
        <div class="mb-4 border-b border-gray-200">
            <ul class="flex flex-wrap -mb-px text-sm font-medium text-center" id="auth-tabs">
                <li class="mr-2">
                    <button class="inline-block p-4 border-b-2 rounded-t-lg" data-tab="login">Login</button>
                </li>
                <li>
                    <button class="inline-block p-4 border-b-2 rounded-t-lg" data-tab="signup">Sign Up</button>
                </li>
            </ul>
        </div>

        <!-- Login Form -->
        <div id="login-form-container" class="tab-content">
            <form id="login-form">
                <input type="hidden" name="action" value="login">
                <div class="mb-4">
                    <label for="login-email" class="block text-sm font-medium text-gray-700">Email</label>
                    <input type="email" name="email" id="login-email" class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500" required>
                </div>
                <div class="mb-6">
                    <label for="login-password" class="block text-sm font-medium text-gray-700">Password</label>
                    <div class="relative">
                        <input type="password" name="password" id="login-password" class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500" required>
                        <span class="absolute inset-y-0 right-0 flex items-center pr-3">
                            <i class="bi bi-eye-slash toggle-password text-gray-400 cursor-pointer" data-target="login-password"></i>
                        </span>
                    </div>
                </div>
                <div class="mb-3 form-check flex gap-2">
                    <input type="checkbox" class="form-check-input" id="exampleCheck1">
                    <label class="form-check-label" for="exampleCheck1">Remember me</label>
                    <a href="forgot_password.html" class="text-indigo-600 ml-16">Forgot password?</a>
                </div>
                <button type="submit" class="w-full flex justify-center mt-4 py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">Log In</button>
            </form>
        </div>

        <!-- Signup Form -->
        <div id="signup-form-container" class="tab-content hidden">
            <form id="signup-form">
                <input type="hidden" name="action" value="signup">
                <div class="mb-4">
                    <label for="signup-name" class="block text-sm font-medium text-gray-700">Full Name</label>
                    <input type="text" name="name" id="signup-name" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md" required>
                </div>
                <div class="mb-4">
                    <label for="signup-phone" class="block text-sm font-medium text-gray-700">Phone</label>
                    <input type="tel" name="phone" id="signup-phone" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md" required>
                </div>
                <div class="mb-4">
                    <label for="signup-email" class="block text-sm font-medium text-gray-700">Email</label>
                    <input type="email" name="email" id="signup-email" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md" required>
                </div>
                <div class="mb-6">
                    <label for="signup-password" class="block text-sm font-medium text-gray-700">Password</label>
                    <div class="relative">
                        <input type="password" name="password" id="signup-password" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md" required>
                        <span class="absolute inset-y-0 right-0 flex items-center pr-3">
                            <i class="bi bi-eye-slash toggle-password text-gray-400 cursor-pointer" data-target="signup-password"></i>
                        </span>
                    </div>
                </div>
                <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">Sign Up</button>
                <p class="mt-2 text-gray-400 text-center">Already have an account? <a href="login.php" class="text-black">Sign in</a></p>                
            </form>
        </div>

        <!-- Social Login Option-->
        <div class="login_container text-center items-center justify-center mt-6 space-x-4">
            <h5>Or login with</h5> 
            <div class="login_bypass flex gap-2 justify-center space-x-4 mt-2">
                <a href="" class="p-2 px-6 rounded-md bg-indigo-200 hover:bg-indigo-600 hover:text-white"> <i class="fab fa-google"></i> Google</a>
                <a href="" class="p-2 px-6 rounded-md bg-indigo-200 hover:bg-indigo-600 hover:text-white"> <i class="fab fa-facebook"></i> Facebook</a>
            </div>
        </div>

        <div id="form-feedback" class="mt-4 text-center"></div>
        
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const tabs = document.querySelectorAll('#auth-tabs button');
            const tabContents = document.querySelectorAll('.tab-content');
            
            function switchTab(targetTab) {
                tabs.forEach(tab => {
                    if(tab.dataset.tab === targetTab) {
                        tab.classList.add('border-indigo-500', 'text-indigo-600');
                        tab.classList.remove('border-transparent', 'hover:text-gray-600', 'hover:border-gray-300');
                    } else {
                        tab.classList.remove('border-indigo-500', 'text-indigo-600');
                        tab.classList.add('border-transparent', 'hover:text-gray-600', 'hover:border-gray-300');
                    }
                });

                tabContents.forEach(content => {
                    content.id.includes(targetTab) ? content.classList.remove('hidden') : content.classList.add('hidden');
                });
            }

            tabs.forEach(tab => {
                tab.addEventListener('click', () => switchTab(tab.dataset.tab));
            });

            // Set initial tab
            switchTab('login');

            const loginForm = document.getElementById('login-form');
            const signupForm = document.getElementById('signup-form');
            const feedbackDiv = document.getElementById('form-feedback');

            async function handleFormSubmit(form) {
                const formData = new FormData(form);
                feedbackDiv.innerHTML = `<div class="animate-pulse text-gray-500">Processing...</div>`;

                try {
                    // if the page was opened with a redirect query param we
                    // want to forward it so the server can include it in the
                    // JSON response.
                    let url = 'login.php';
                    const params = new URLSearchParams(window.location.search);
                    if (params.has('redirect')) {
                        url += '?redirect=' + encodeURIComponent(params.get('redirect'));
                    }
                    const response = await fetch(url, {
                        method: 'POST',
                        body: formData
                    });
                    const result = await response.json();

                    feedbackDiv.textContent = result.message;
                    feedbackDiv.className = `mt-4 text-center font-bold ${result.status === 'success' ? 'text-green-500' : 'text-red-500'}`;
                    
                    if (result.status === 'success') {
                        if (result.redirect) {
                            setTimeout(() => window.location.href = result.redirect, 1000);
                        } else {
                            // On successful signup, switch to login tab
                            signupForm.reset();
                            switchTab('login');
                        }
                    }
                } catch (error) {
                    feedbackDiv.textContent = 'An error occurred. Please try again.';
                    feedbackDiv.className = 'mt-4 text-center font-bold text-red-500';
                }
            }

            loginForm.addEventListener('submit', (e) => {
                e.preventDefault();
                handleFormSubmit(loginForm);
            });

            signupForm.addEventListener('submit', (e) => {
                e.preventDefault();
                handleFormSubmit(signupForm);
            });
        });



        // =================================================================
        //      PASSWORD TOGGLE VISIBILITY
        // =================================================================
        document.querySelectorAll(".toggle-password").forEach(icon => {
            icon.addEventListener("click", function() {
                const input = document.getElementById(this.getAttribute("data-target"));

                if (input.type === "password") {
                    input.type = "text";
                    this.classList.remove("bi-eye-slash");
                    this.classList.add("bi-eye");
                } else {
                    input.type = "password";
                    this.classList.remove("bi-eye");
                    this.classList.add("bi-eye-slash");
                }
            });
        });
    </script>
</body>
</html>