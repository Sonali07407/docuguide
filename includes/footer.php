<?php if (!is_user_logged_in()): ?>
<div class="modal fade no-print" id="fkAuthModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" style="max-width: 680px;">
        <div class="modal-content border-0 overflow-hidden rounded-1">
            <div class="row g-0">
                <div class="col-md-5 fk-modal-left d-flex flex-column justify-content-between">
                    <div>
                        <h4 class="fw-bold" id="modalTitle">Login</h4>
                        <p class="small opacity-75 mt-3" id="modalSubtitle">
                            Get access to your Liked Document Checklists, Official Portals, and Citizen Guides.
                        </p>
                    </div>
                    <div class="text-center mt-4" style="font-size: 3.5rem;">📑</div>
                </div>

                <div class="col-md-7 bg-white p-4 position-relative">
                    <button type="button" class="btn-close position-absolute top-0 end-0 m-3" data-bs-dismiss="modal" aria-label="Close"></button>

                    <div id="favNotice" class="alert alert-warning py-2 small mt-2 d-none">
                        Please register (or log in) to add documents to your favourites!
                    </div>

                    <?php if ($auth_error): ?>
                        <div class="alert alert-danger py-2 small mt-2"><?php echo htmlspecialchars($auth_error); ?></div>
                    <?php endif; ?>

                    <form method="POST" action="actions/auth_action.php" id="loginForm" class="mt-3">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="action" value="login">
                        <div class="mb-3">
                            <input type="email" name="email" class="form-control fk-input" placeholder="Enter Email Address" required>
                        </div>
                        <div class="mb-4">
                            <input type="password" name="password" class="form-control fk-input" placeholder="Enter Password" required>
                        </div>
                        <button type="submit" class="btn btn-fk-orange w-100 py-2 rounded-1">Login</button>

                        <div class="text-center mt-4">
                            <a href="#" class="text-primary fw-bold text-decoration-none small" onclick="toggleAuthMode('register'); return false;">
                                New to DocuGuide? Create an account
                            </a>
                        </div>
                    </form>

                    <form method="POST" action="actions/auth_action.php" id="registerForm" class="mt-3 d-none">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="action" value="register">
                        <div class="mb-3">
                            <input type="text" name="name" class="form-control fk-input" placeholder="Enter Full Name" required>
                        </div>
                        <div class="mb-3">
                            <input type="email" name="email" class="form-control fk-input" placeholder="Enter Email Address" required>
                        </div>
                        <div class="mb-4">
                            <input type="password" name="password" class="form-control fk-input" placeholder="Create Password (min 6 chars)" minlength="6" required>
                        </div>
                        <button type="submit" class="btn btn-fk-orange w-100 py-2 rounded-1">Continue & Register</button>

                        <div class="text-center mt-4">
                            <a href="#" class="text-primary fw-bold text-decoration-none small" onclick="toggleAuthMode('login'); return false;">
                                Existing User? Log in
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php if (!is_user_logged_in()): ?>
<script>
function toggleAuthMode(mode) {
    const loginForm = document.getElementById('loginForm');
    const registerForm = document.getElementById('registerForm');
    const title = document.getElementById('modalTitle');
    const subtitle = document.getElementById('modalSubtitle');

    if (mode === 'register') {
        loginForm.classList.add('d-none');
        registerForm.classList.remove('d-none');
        title.textContent = "Registration Required";
        subtitle.textContent = "Create an account to save documents to your Favourites and access them anytime.";
    } else {
        registerForm.classList.add('d-none');
        loginForm.classList.remove('d-none');
        title.textContent = "Login";
        subtitle.textContent = "Get access to your Liked Document Checklists, Official Portals, and Citizen Guides.";
    }
}

function openAuthPopup(mode = 'login', showFavWarning = false) {
    toggleAuthMode(mode);
    const notice = document.getElementById('favNotice');
    if (notice) {
        notice.classList.toggle('d-none', !showFavWarning);
    }
    const modalEl = document.getElementById('fkAuthModal');
    if (modalEl) {
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }
}

window.addEventListener('DOMContentLoaded', () => {
    const requireReg = <?php echo $require_register ? 'true' : 'false'; ?>;
    const hasError = <?php echo $auth_error !== '' ? 'true' : 'false'; ?>;
    const hasSeenPopup = sessionStorage.getItem('docuguide_popup_shown');

    if (requireReg) {
        openAuthPopup('register', true);
    } else if (hasError || !hasSeenPopup) {
        openAuthPopup('login', false);
        sessionStorage.setItem('docuguide_popup_shown', '1');
    }
});
</script>
<?php endif; ?>
</body>
</html>