<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Tarazino | Shared document footer (CodeIgniter 3).
 *
 * Optional parameters from the page View:
 *   $page_scripts : array of URLs for that page's JavaScript files.
 *   $use_charts   : TRUE only for pages that need Chart.js.
 *
 * Order matters: Bootstrap -> shared logout -> Chart.js -> page scripts.
 * Business logic belongs in page modules, not this shared layout.
 * For a future Vue 3 app, vendor imports move to the JS build entry point.
 */
$page_scripts = isset($page_scripts) && is_array($page_scripts)
    ? $page_scripts
    : array();
$use_charts = isset($use_charts) && $use_charts === TRUE;

// Prefer a local Chart.js UMD bundle when present; otherwise use the pinned CDN.
$chartjs_path = 'assets/vendor/chartjs/chart.umd.min.js';
$chartjs_url = is_file(FCPATH . $chartjs_path)
    ? base_url($chartjs_path)
    : 'https://cdn.jsdelivr.net/npm/chart.js@4.5.1/dist/chart.umd.min.js';
?>
<!-- Bootstrap 5.3.3 Bundle includes Popper for modals and dropdowns. -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>

<!-- Existing shared logout flow; harmless on pages without its modal. -->
<script src="<?php echo html_escape(base_url('assets/js/logout.js')); ?>"></script>

<!-- Theme selection is shared by the desktop sidebar and mobile menu. -->
<script src="<?php echo html_escape(base_url('assets/js/theme.js?v=' . filemtime(FCPATH . 'assets/js/theme.js'))); ?>"></script>

<?php if ($use_charts): ?>
    <!-- Chart.js loads before the page module that creates the charts. -->
    <script src="<?php echo html_escape($chartjs_url); ?>"></script>
<?php endif; ?>

<!-- Jalali Datepicker Library -->
<script src="<?php echo base_url('assets/vendor/jalalidatepicker/jalalidatepicker.min.js'); ?>"></script>

<!-- Tarazino Jalali Adapter -->
<script src="<?php echo base_url('assets/js/jalali-datepicker.js?v=' . filemtime(FCPATH . 'assets/js/jalali-datepicker.js')); ?>"></script>

<?php foreach ($page_scripts as $script): ?>
    <?php if (is_string($script) && trim($script) !== ''): ?>
        <?php
        $script_path = FCPATH . 'assets/js/' . basename((string) parse_url($script, PHP_URL_PATH));
        $script_url = is_file($script_path)
            ? $script . (strpos($script, '?') === FALSE ? '?' : '&') . 'v=' . filemtime($script_path)
            : $script;
        ?>
        <script src="<?php echo html_escape($script_url); ?>"></script>
    <?php endif; ?>
<?php endforeach; ?>
</body>

</html>
