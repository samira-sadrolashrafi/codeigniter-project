<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$page_scripts = isset($page_scripts) && is_array($page_scripts) ? $page_scripts : array();
?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="<?php echo html_escape(base_url('assets/js/logout.js')); ?>"></script>
    <?php foreach ($page_scripts as $script): ?>
        <script src="<?php echo html_escape($script); ?>"></script>
    <?php endforeach; ?>
</body>
</html>
