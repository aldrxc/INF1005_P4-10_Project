<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/sanitize.php';

startSession();

// already logged in -> redirect to dashboard
if (isLoggedIn()) {
    header('Location: /dashboard.php');
    exit;
}

$pageTitle = 'Create Account';
$errors    = [];
$old       = [];

// repopulate form on error
if (!empty($_SESSION['register_errors'])) {
    $errors = $_SESSION['register_errors'];
    $old    = $_SESSION['register_old'] ?? [];
    unset($_SESSION['register_errors'], $_SESSION['register_old']);
}

generateCsrfToken();
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-sm-10 col-md-8 col-lg-6 col-xl-5">

            <div class="auth-card card shadow-lg border-0">
                <div class="card-body p-4 p-md-5">

                    <div class="text-center mb-4">
                        <i class="bi bi-music-note-list display-5 text-accent" aria-hidden="true"></i>
                        <h1 class="h3 mt-2 fw-bold text-white">Join MerchVault</h1>
                        <p class="text-muted small">Buy and sell music merch with the community.</p>
                    </div>

                    <?php if (!empty($errors['general'])): ?>
                        <div class="alert alert-danger" role="alert">
                            <?= clean($errors['general']) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="/handlers/register-handler.php" novalidate id="registerForm">
                        <?= getCsrfField() ?>

                        <!-- username -->
                        <div class="mb-3">
                            <label for="username" class="form-label">Username <span class="text-accent" aria-hidden="true">*</span></label>
                            <input type="text" class="form-control <?= isset($errors['username']) ? 'is-invalid' : '' ?>"
                                id="username" name="username" required
                                maxlength="50" pattern="[A-Za-z0-9_]+"
                                value="<?= clean($old['username'] ?? '') ?>"
                                autocomplete="username"
                                aria-describedby="usernameHelp">
                            <div id="usernameHelp" class="form-text">Letters, numbers and underscores only.</div>
                            <?php if (isset($errors['username'])): ?>
                                <div class="invalid-feedback"><?= clean($errors['username']) ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- email -->
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address <span class="text-accent" aria-hidden="true">*</span></label>
                            <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                                id="email" name="email" required
                                maxlength="255"
                                value="<?= clean($old['email'] ?? '') ?>"
                                autocomplete="email">
                            <?php if (isset($errors['email'])): ?>
                                <div class="invalid-feedback"><?= clean($errors['email']) ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- display name -->
                        <div class="mb-3">
                            <label for="display_name" class="form-label">Display Name <span class="text-accent" aria-hidden="true">*</span></label>
                            <input type="text" class="form-control <?= isset($errors['display_name']) ? 'is-invalid' : '' ?>"
                                id="display_name" name="display_name" required
                                maxlength="100"
                                value="<?= clean($old['display_name'] ?? '') ?>"
                                autocomplete="name">
                            <?php if (isset($errors['display_name'])): ?>
                                <div class="invalid-feedback"><?= clean($errors['display_name']) ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- password -->
                        <div class="mb-3">
                            <label for="password" class="form-label">Password <span class="text-accent" aria-hidden="true">*</span></label>
                            <div class="input-group">
                                <input type="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                                    id="password" name="password" required
                                    minlength="8"
                                    autocomplete="new-password"
                                    aria-describedby="passwordHelp">
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword"
                                    aria-label="Show or hide password">
                                    <i class="bi bi-eye" aria-hidden="true"></i>
                                </button>
                                <?php if (isset($errors['password'])): ?>
                                    <div class="invalid-feedback"><?= clean($errors['password']) ?></div>
                                <?php endif; ?>
                            </div>
                            <div id="passwordHelp" class="form-text">Minimum 8 characters.</div>
                        </div>

                        <!-- confirm password -->
                        <div class="mb-4">
                            <label for="confirm_password" class="form-label">Confirm Password <span class="text-accent" aria-hidden="true">*</span></label>
                            <input type="password" class="form-control <?= isset($errors['confirm_password']) ? 'is-invalid' : '' ?>"
                                id="confirm_password" name="confirm_password" required
                                autocomplete="new-password">
                            <?php if (isset($errors['confirm_password'])): ?>
                                <div class="invalid-feedback"><?= clean($errors['confirm_password']) ?></div>
                            <?php endif; ?>
                        </div>

                        <button type="submit" class="btn btn-accent w-100 fw-semibold">
                            <i class="bi bi-person-plus me-2" aria-hidden="true"></i>Create Account
                        </button>
                    </form>

                    <hr class="my-4">
                    <p class="text-center text-muted small mb-0">
                        Already have an account?
                        <a href="/login.php" class="text-accent text-decoration-none fw-semibold">Log in</a>
                    </p>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
    // toggle password visibility
    document.getElementById('togglePassword').addEventListener('click', function() {
        const pwField = document.getElementById('password');
        const icon = this.querySelector('i');
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

    // client-side: confirm passwords match before submit
    document.getElementById('registerForm').addEventListener('submit', function(e) {
        const pw = document.getElementById('password').value;
        const cpw = document.getElementById('confirm_password').value;
        if (pw !== cpw) {
            e.preventDefault();
            document.getElementById('confirm_password').classList.add('is-invalid');
            // append or reuse feedback element
            let fb = document.querySelector('#confirm_password ~ .invalid-feedback');
            if (!fb) {
                fb = document.createElement('div');
                fb.className = 'invalid-feedback';
                document.getElementById('confirm_password').after(fb);
            }
            fb.textContent = 'Passwords do not match.';
        }
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>