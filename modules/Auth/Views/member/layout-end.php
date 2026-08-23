</div>
<script>
(function(){
    const root = document.documentElement;
    const saved = localStorage.getItem('member-theme') || 'light';
    root.setAttribute('data-theme', saved);
    window.toggleMemberTheme = function(){
        const next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        root.setAttribute('data-theme', next);
        localStorage.setItem('member-theme', next);
    };
})();
</script>
<?php require ROOT_PATH . '/framework/Views/theme/public/cookie-banner.php'; ?>
</body>
</html>