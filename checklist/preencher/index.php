<?php
/**
 * Preenchimento de checklist
 * Stack: MySQLi + protect.php (SSO)
 */

require_once __DIR__ . '/../../configuracao/configuracao_conexao.php';
require_once __DIR__ . '/../../configuracao/protect.php';

/* 🔒 Login */
$user = require_login();
$user_id = (int) $user->sub;

/* 🔒 BASE DO SISTEMA */
define('APP_PATH', realpath(__DIR__ . '/../../'));

/* 📥 Checklist */
$checklist_id = (int) ($_GET['id'] ?? 0);
if (!$checklist_id) {
    die('Checklist inválido');
}

/* 🔎 Buscar checklist */
$stmt = $mysqli->prepare("
    SELECT id, titulo, concluido
    FROM checklists
    WHERE id = ? AND user_id = ?
    LIMIT 1
");
$stmt->bind_param("ii", $checklist_id, $user_id);
$stmt->execute();
$checklist = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$checklist) {
    die('Checklist não encontrado ou sem permissão');
}

$bloqueado = ((int) $checklist['concluido'] === 1);

/* 🔎 Buscar itens */
$stmt = $mysqli->prepare("
    SELECT *
    FROM checklist_itens
    WHERE checklist_id = ?
    ORDER BY ordem
");
$stmt->bind_param("i", $checklist_id);
$stmt->execute();
$itens = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <title><?= htmlspecialchars($checklist['titulo']) ?></title>
    <base href="/">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="/img/logo-icon.png">
    <link rel="stylesheet" href="/css/style.css">
    <style>
        .bloqueado {
            pointer-events: none;
            opacity: .65;
        }
        .page-content {
            margin-top: 80px;
        }
    </style>
</head>

<body class="bg-light">

    <?php require APP_PATH . '/include/loading.php'; ?>
    <?php require APP_PATH . '/include/popups.php'; ?>
    <div id="conteudo">
        <?php require APP_PATH . '/include/menu.php'; ?>
        <div class="container py-4 page-content">
            <main class="sistema">


                <!-- TÍTULO -->
                <h3 class="mb-4">📋 <?= htmlspecialchars($checklist['titulo']) ?></h3>

                <!-- ALERTA BLOQUEADO -->
                <?php if ($bloqueado): ?>
                    <div class="alert alert-warning">
                        Checklist finalizado. Apenas visualização.
                    </div>
                <?php endif; ?>

                <!-- FORMULÁRIO -->
                <form method="post" action="\checklist\preencher\salvar.php">
                    <input type="hidden" name="checklist_id" value="<?= $checklist_id ?>">

                    <?php foreach ($itens as $i): ?>
                        <div class="card mb-3 <?= $bloqueado ? 'bloqueado' : '' ?>">
                            <div class="card-body">

                                <!-- ✔ CHECK -->
                                <div class="form-check mb-2">
                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        name="concluido[<?= $i['id'] ?>]"
                                        value="1"
                                        <?= $i['concluido'] ? 'checked' : '' ?>
                                    >
                                    <label class="form-check-label fw-bold">
                                        <?= htmlspecialchars($i['descricao']) ?>
                                    </label>
                                </div>

                                <!-- 📝 OBSERVAÇÃO -->
                                <?php if ((int) $i['permite_observacao'] === 1): ?>
                                    <textarea
                                        class="form-control mb-2"
                                        name="observacao[<?= $i['id'] ?>]"
                                        placeholder="Observações"
                                    ><?= htmlspecialchars($i['observacao'] ?? '') ?></textarea>
                                <?php endif; ?>

                                <!-- 📸 FOTO -->
                                <?php if ((int) $i['permite_foto'] === 1): ?>
                                    <div class="mb-2">
                                        <label class="form-label small">📸 Anexar foto</label>
                                        <input
                                            type="file"
                                            class="form-control upload-foto"
                                            data-item="<?= $i['id'] ?>"
                                            accept="image/*"
                                        />

                                        <div class="item-media mt-2" data-item="<?= $i['id'] ?>"></div>

                                    </div>
                                <?php endif; ?>

                                <!-- 📄 DOCUMENTO -->
                                <?php if ((int) $i['permite_anexo'] === 1): ?>
                                    <div class="mb-2">
                                        <label class="form-label small">📄 Anexar documento</label>
                                        <input
                                            type="file"
                                            class="form-control upload-doc"
                                            data-item="<?= $i['id'] ?>"
                                        >
                                    </div>
                                <?php endif; ?>

                            </div>
                        </div>
                    <?php endforeach; ?>

                    <!-- BOTÕES -->
                    <?php if (!$bloqueado): ?>
                        <button class="btn btn-primary" name="acao" value="salvar">
                            💾 Salvar
                        </button>

                        <button class="btn btn-danger" name="acao" value="finalizar">
                            🔒 Salvar e finalizar
                        </button>
                    <?php endif; ?>
                </form>
            </main>            
        </div>
    </div>
    <?php require APP_PATH . '/include/footer.php'; ?>                
<script>
/* =====================================================
 * 🔧 Reduz e converte imagem (canvas)
 * ===================================================== */
function reduzirImagem(file, maxLado = 1280, qualidade = 0.7) {
    return new Promise((resolve, reject) => {

        const img = new Image();
        const reader = new FileReader();

        reader.onload = e => img.src = e.target.result;
        reader.onerror = reject;

        img.onload = () => {

            let { width, height } = img;

            if (width > height && width > maxLado) {
                height *= maxLado / width;
                width = maxLado;
            } else if (height > maxLado) {
                width *= maxLado / height;
                height = maxLado;
            }

            const canvas = document.createElement('canvas');
            canvas.width = width;
            canvas.height = height;

            canvas.getContext('2d').drawImage(img, 0, 0, width, height);

            canvas.toBlob(blob => {
                if (!blob) return reject();

                resolve(new File([blob], file.name, {
                    type: 'image/jpeg',
                    lastModified: Date.now()
                }));

            }, 'image/jpeg', qualidade);
        };

        reader.readAsDataURL(file);
    });
}

/* =====================================================
 * 📸 Upload de imagem (1 por item)
 * ===================================================== */
document.querySelectorAll('.upload-foto').forEach(input => {

    input.addEventListener('change', async () => {

        const itemId = input.dataset.item;
        const original = input.files[0];
        if (!original) return;

        const mediaBox = document.querySelector(
            `.item-media[data-item="${itemId}"]`
        );

        // 🔥 Remove imagem anterior (1 por item)
        mediaBox.innerHTML = '';

        /* 🔄 Animação de conversão */
        const converting = document.createElement('div');
        converting.className = 'd-flex align-items-center gap-2';
        converting.innerHTML = `
            <div class="spinner-border spinner-border-sm text-primary"></div>
            <strong>Convertendo imagem...</strong>
        `;
        mediaBox.appendChild(converting);

        let file;
        try {
            file = await reduzirImagem(original);
        } catch {
            mediaBox.innerHTML = '';
            alert('Erro ao converter imagem');
            return;
        }

        converting.remove();

        /* 📊 Barra de progresso */
        const progress = document.createElement('div');
        progress.className = 'progress mb-2';

        const bar = document.createElement('div');
        bar.className = 'progress-bar progress-bar-striped progress-bar-animated';
        bar.style.width = '0%';
        bar.textContent = '0%';

        progress.appendChild(bar);
        mediaBox.appendChild(progress);

        /* 📤 Upload */
        const form = new FormData();
        form.append('item_id', itemId);
        form.append('tipo', 'foto');
        form.append('arquivo', file);

        const xhr = new XMLHttpRequest();
        xhr.open('POST', '/checklist/itens/upload.php', true);

        xhr.upload.onprogress = e => {
            if (e.lengthComputable) {
                const p = Math.round((e.loaded / e.total) * 100);
                bar.style.width = p + '%';
                bar.textContent = p + '%';
            }
        };

        xhr.onload = () => {
            if (xhr.status !== 200) {
                bar.classList.add('bg-danger');
                bar.textContent = 'Erro';
                return;
            }

            const resp = JSON.parse(xhr.responseText);
            if (!resp.ok) {
                alert(resp.erro || 'Erro no upload');
                mediaBox.innerHTML = '';
                return;
            }

            /* ✅ Preview final + botão remover */
            mediaBox.innerHTML = '';

            const img = document.createElement('img');
            img.src = URL.createObjectURL(file);
            img.className = 'img-thumbnail mb-2';
            img.style.maxWidth = '200px';

            const btnRemove = document.createElement('button');
            btnRemove.type = 'button';
            btnRemove.className = 'btn btn-sm btn-outline-danger';
            btnRemove.textContent = '🗑 Remover imagem';

            btnRemove.onclick = () => {
                mediaBox.innerHTML = '';
                input.value = '';

                // 🔐 opcional: remover no backend
                fetch('/checklist/itens/remover_arquivo.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        item_id: itemId,
                        tipo: 'foto'
                    })
                });
            };

            mediaBox.appendChild(img);
            mediaBox.appendChild(btnRemove);
        };

        xhr.send(form);
    });

});
</script>


<script src="/js/popups.js"></script>
<script src="/js/script.js"></script>   

</body>
</html>
