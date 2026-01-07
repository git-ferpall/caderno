<h2>📋 Relatório semanal de apontamentos</h2>

<p>Olá <strong><?= $nome ?></strong>,</p>

<p>Confira abaixo o resumo dos seus apontamentos por propriedade:</p>

<?php foreach ($relatorio as $prop): ?>

<hr>

<h3>🏡 <?= htmlspecialchars($prop['nome']) ?></h3>

<ul>
  <li>🔴 <strong><?= count($prop['atrasadas']) ?></strong> tarefas atrasadas</li>
  <li>🟡 <strong><?= count($prop['semana']) ?></strong> tarefas planejadas para esta semana</li>
</ul>

<img src="<?= gerarGrafico($prop) ?>" style="max-width:360px">

<?php if ($prop['atrasadas']): ?>
<h4>🔴 Atrasadas</h4>
<ul>
  <?php foreach ($prop['atrasadas'] as $a): ?>
    <li>
      <strong><?= $a['tipo'] ?></strong><br>
      📅 <?= date('d/m/Y', strtotime($a['data'])) ?><br>
      <?= nl2br($a['observacoes']) ?>
    </li>
  <?php endforeach ?>
</ul>
<?php endif ?>

<?php if ($prop['semana']): ?>
<h4>🟡 Planejadas para esta semana</h4>
<ul>
  <?php foreach ($prop['semana'] as $a): ?>
    <li>
      <strong><?= $a['tipo'] ?></strong><br>
      📅 <?= date('d/m/Y', strtotime($a['data'])) ?><br>
      <?= nl2br($a['observacoes']) ?>
    </li>
  <?php endforeach ?>
</ul>
<?php endif ?>

<?php endforeach ?>

<p style="font-size:12px;color:#666">
Você está recebendo este e-mail porque autorizou comunicações por e-mail.
</p>
