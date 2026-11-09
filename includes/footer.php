<?php
require_once __DIR__ . '/challenges.php';
$checkInNavState = challenge_app_state();
?>

        </main>

        <nav class="bottom-nav" aria-label="Main navigation">
            <a
                class="nav-item <?= ($activePage ?? '') === 'home' ? 'active' : '' ?>"
                href="/?page=home"
            >
                <span class="nav-icon">⌂</span>
                <span>Home</span>
            </a>

            <?php if ($checkInNavState['can_check_in']): ?>
                <a
                    class="nav-item <?= ($activePage ?? '') === 'check-in' ? 'active' : '' ?>"
                    href="/?page=check-in"
                >
                    <span class="nav-icon">✓</span>
                    <span>Check-In</span>
                </a>
            <?php else: ?>
                <button
                    type="button"
                    class="nav-item"
                    id="check-in-locked"
                    aria-label="Check-In unavailable. Show when it unlocks."
                    style="appearance: none; border: 0; background: transparent; opacity: 0.55; cursor: pointer;"
                >
                    <span class="nav-icon" aria-hidden="true">✓</span>
                    <span>Check-In</span>
                </button>
            <?php endif; ?>

            <a
                class="nav-item <?= ($activePage ?? '') === 'sos' ? 'active' : '' ?>"
                href="/?page=sos"
            >
                <span class="nav-icon">!</span>
                <span>SOS</span>
            </a>

            <a
                class="nav-item <?= ($activePage ?? '') === 'progress' ? 'active' : '' ?>"
                href="/?page=progress"
            >
                <span class="nav-icon">↗</span>
                <span>Progress</span>
            </a>

            <a
                class="nav-item <?= ($activePage ?? '') === 'future-me' ? 'active' : '' ?>"
                href="/?page=future-me"
            >
                <span class="nav-icon">★</span>
                <span>Future Me</span>
            </a>
        </nav>

        <?php if (!$checkInNavState['can_check_in']): ?>
            <div
                id="check-in-notice"
                role="status"
                aria-atomic="true"
                style="position: fixed; bottom: calc(90px + env(safe-area-inset-bottom, 0px)); left: 50%; transform: translateX(-50%); z-index: 100; width: max-content; max-width: calc(100vw - 32px); box-sizing: border-box; border-radius: 16px; color: #332b3d; text-align: center; pointer-events: none;"
            ></div>

            <script>
                (() => {
                    const button = document.getElementById('check-in-locked');
                    const notice = document.getElementById('check-in-notice');
                    let timer;

                    button.addEventListener('click', () => {
                        window.clearTimeout(timer);
                        notice.textContent = '🔒 Check-In unlocks when your next 45 begins.';
                        notice.style.padding = '12px 16px';
                        notice.style.background = '#f0e8ff';
                        notice.style.boxShadow = '0 8px 24px rgba(80, 52, 95, 0.18)';

                        timer = window.setTimeout(() => {
                            notice.textContent = '';
                            notice.style.padding = '';
                            notice.style.background = '';
                            notice.style.boxShadow = '';
                        }, 3500);
                    });
                })();
            </script>
        <?php endif; ?>
    </div>

    <script src="/assets/js/app.js"></script>
</body>
</html>