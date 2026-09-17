        </main>

        <nav class="bottom-nav" aria-label="Main navigation">
            <a
                class="nav-item <?= ($activePage ?? '') === 'home' ? 'active' : '' ?>"
                href="/?page=home"
            >
                <span class="nav-icon">⌂</span>
                <span>Home</span>
            </a>

            <a
                class="nav-item <?= ($activePage ?? '') === 'check-in' ? 'active' : '' ?>"
                href="/?page=check-in"
            >
                <span class="nav-icon">✓</span>
                <span>Check-In</span>
            </a>

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
    </div>

    <script src="/assets/js/app.js"></script>
</body>
</html>