  </main>
</div>

<?php if (empty($_GET['minimal']) && empty($_POST['minimal'])): ?>
<footer class="bg-stone-200 text-stone-500 text-xs text-center py-3">
  <?= h(SITE_NAME) ?> · Admin · <?= date('Y') ?>
</footer>
<?php endif; ?>

<?php
// Wenn im Modal gespeichert wurde, signalisiere ans Parent-Fenster
if (!empty($_GET['saved']) && (!empty($_GET['minimal']) || !empty($_POST['minimal']))): ?>
<script>
  if (window.parent !== window) {
    window.parent.postMessage({ type: 'bzv-edit-saved' }, window.location.origin);
  }
</script>
<?php endif; ?>

</body>
</html>
