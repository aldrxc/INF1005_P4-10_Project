<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/sanitize.php';

startSession();

if (isLoggedIn()) {
    header('Location: /dashboard.php');
    exit;
}

$pageTitle = 'Log In';
$errors    = [];
$old       = [];

if (!empty($_SESSION['login_error'])) {
    $errors['general'] = $_SESSION['login_error'];
    $old['identifier'] = $_SESSION['login_old'] ?? '';
    unset($_SESSION['login_error'], $_SESSION['login_old']);
}

generateCsrfToken();
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-sm-10 col-md-7 col-lg-5 col-xl-4">

            <div class="auth-card card shadow-lg border-0">
                <div class="card-body p-4 p-md-5">

                    <div class="text-center mb-4">
                        <i class="bi bi-music-note-list display-5 text-accent" aria-hidden="true"></i>
                        <h1 class="h3 mt-2 fw-bold">Welcome Back</h1>
                        <p class="text-muted small">Log in to your MerchVault account.</p>
                    </div>

                    <?php if (!empty($errors['general'])): ?>
                        <div class="alert alert-danger" role="alert">
                            <?= clean($errors['general']) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="/handlers/login-handler.php" novalidate>
                        <?= getCsrfField() ?>

                        <?php
                        // Preserve redirect destination
                        $redirect = htmlspecialchars($_GET['redirect'] ?? '', ENT_QUOTES, 'UTF-8');
                        if ($redirect):
                        ?>
                            <input type="hidden" name="redirect" value="<?= $redirect ?>">
                        <?php endif; ?>

                        <!-- Email or Username -->
                        <div class="mb-3">
                            <label for="identifier" class="form-label">Email or Username <span class="text-accent" aria-hidden="true">*</span></label>
                            <input type="text" class="form-control"
                                   id="identifier" name="identifier" required
                                   value="<?= clean($old['identifier'] ?? '') ?>"
                                   autocomplete="username">
                        </div>

                        <!-- Password -->
                        <div class="mb-4">
                            <label for="password" class="form-label">Password <span class="text-accent" aria-hidden="true">*</span></label>
                            <div class="input-group">
                                <input type="password" class="form-control"
                                       id="password" name="password" required
                                       autocomplete="current-password">
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword"
                                        aria-label="Show or hide password">
                                    <i class="bi bi-eye" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-accent w-100 fw-semibold">
                            <i class="bi bi-box-arrow-in-right me-2" aria-hidden="true"></i>Log In
                        </button>
                    </form>

                    <hr class="my-4">
                    <p class="text-center text-muted small mb-0">
                        New to MerchVault?
                        <a href="/register.php" class="text-accent text-decoration-none fw-semibold">Create an account</a>
                    </p>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
document.getElementById('togglePassword').addEventListener('click', function () {
    const pwField = document.getElementById('password');
    const icon    = this.querySelector('i');
    if (pwField.type === 'password') {
        pwField.type = 'text';
        icon.classList.replace('bi-eye', 'bi-eye-slash');
        this.setAttribute('aria-label', 'Hide password');
    } else {
        pwField.type = 'password';
        icon.classList.replace('bi-eye-slash', 'bi-eye');
        this.setAttribute('aria-label', 'Show password');
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
