<script src="<?php echo APP_BASE_URL; ?>/frontend/assets/js/scripts.js?v=2"></script>
<?php if (isset($moduleJS)): ?>
<script src="<?php echo htmlspecialchars($moduleJS); ?>?v=<?= time(); ?>"></script>
<?php endif; ?>
</body>
</html>
