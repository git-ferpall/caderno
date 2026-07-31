<?php
declare(strict_types=1);
/** Scripts de segurança (CSRF + utils). Incluir após protect.php. */
$jsBase = preg_match('#/checklist/#', $_SERVER['SCRIPT_NAME'] ?? '') ? '../../js' : '../js';
?>
<script src="<?= htmlspecialchars($jsBase) ?>/caderno_utils.js"></script>
<script src="<?= htmlspecialchars($jsBase) ?>/csrf.js"></script>
