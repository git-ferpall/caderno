<?php
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    header('Location: /checklist/modelos/criar.php', true, 302);
    exit;
}
header('Location: /checklist/modelos/criar.php?id=' . $id, true, 301);
exit;
