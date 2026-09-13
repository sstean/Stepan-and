<?php
declare(strict_types=1);

// A tiny PHP endpoint keeps the best score available after a browser refresh.
$scoresFile = __DIR__ . '/scores.json';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    $payload = json_decode(file_get_contents('php://input'), true) ?? [];
    $name = trim((string) ($payload['name'] ?? 'Игрок'));
    $name = mb_substr($name ?: 'Игрок', 0, 18);
    $score = max(0, min(10000, (int) ($payload['score'] ?? 0)));
    $scores = is_file($scoresFile) ? json_decode((string) file_get_contents($scoresFile), true) : [];
    $scores = is_array($scores) ? $scores : [];
    $scores[] = ['name' => $name, 'score' => $score, 'date' => date('d.m')];
    usort($scores, static fn($a, $b) => $b['score'] <=> $a['score']);
    $scores = array_slice($scores, 0, 5);
    file_put_contents($scoresFile, json_encode($scores, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);
    echo json_encode(['scores' => $scores], JSON_UNESCAPED_UNICODE);
    exit;
}

$scores = is_file($scoresFile) ? json_decode((string) file_get_contents($scoresFile), true) : [];
$scores = is_array($scores) ? $scores : [];
?>
<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#090d1c">
  <title>Слабое звено — игра на эрудицию</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="styles.css">
</head>
<body>
  <main class="game-shell">
    <header class="topbar">
      <a class="brand" href="./" aria-label="Слабое звено, на главную"><span class="brand-mark">W</span><span>СЛАБОЕ<br>ЗВЕНО</span></a>
      <div class="round-status"><span class="pulse"></span> РАУНД <b id="roundNumber">01</b> / 03</div>
      <button class="icon-button" id="rulesButton" aria-label="Показать правила">?</button>
    </header>

    <section class="arena" aria-live="polite">
      <div class="orbit orbit-one"></div><div class="orbit orbit-two"></div>
      <div class="timer-wrap"><svg class="timer-ring" viewBox="0 0 120 120"><circle cx="60" cy="60" r="52"/><circle id="timerCircle" class="timer-progress" cx="60" cy="60" r="52"/></svg><div class="timer"><strong id="timerValue">60</strong><span>секунд</span></div></div>
      <div class="pot"><span>БАНК</span><strong id="bankValue">0 ₽</strong></div>
      <div class="ladder" id="ladder">
        <div data-value="1000">1 000 ₽</div><div data-value="500">500 ₽</div><div data-value="250">250 ₽</div><div data-value="100">100 ₽</div><div class="active" data-value="50">50 ₽</div><div data-value="0">0 ₽</div>
      </div>
      <article class="question-card" id="questionCard">
        <p class="eyebrow">ВОПРОС <span id="questionCount">01</span></p>
        <h1 id="questionText">Как называется самая большая планета Солнечной системы?</h1>
        <div class="answers" id="answers"></div>
        <p class="hint">Выберите ответ или нажмите клавишу 1–4</p>
      </article>
      <button class="bank-button" id="bankButton" title="Сохранить накопленную сумму">БАНК <span>↵</span></button>
    </section>

    <footer class="game-footer"><span id="message">Соберите цепочку правильных ответов.</span><button class="sound-toggle" id="soundButton">◖ ЗВУК: ВКЛ</button></footer>
  </main>

  <div class="modal-backdrop" id="modalBackdrop" hidden>
    <section class="modal" id="modal" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
      <button class="close" id="closeModal" aria-label="Закрыть">×</button>
      <p class="eyebrow">КАК ИГРАТЬ</p><h2 id="modalTitle">Не дайте цепочке оборваться.</h2>
      <ol><li>Отвечайте на вопросы, пока идёт таймер.</li><li>Каждый верный ответ увеличивает сумму в цепочке.</li><li>Нажмите «БАНК», чтобы сохранить деньги. Неверный ответ сбросит несохранённую сумму.</li></ol>
      <button class="primary" id="startButton">НАЧАТЬ ИГРУ</button>
    </section>
  </div>
  <div class="modal-backdrop" id="resultBackdrop" hidden>
    <section class="modal result-modal" role="dialog" aria-modal="true" aria-labelledby="resultTitle">
      <p class="eyebrow">ИГРА ОКОНЧЕНА</p><h2 id="resultTitle">Ваш банк</h2><strong class="final-score" id="finalScore">0 ₽</strong>
      <label class="name-label">Ваше имя <input id="playerName" maxlength="18" placeholder="Игрок"></label>
      <button class="primary" id="saveScore">СОХРАНИТЬ РЕЗУЛЬТАТ</button><button class="secondary" id="playAgain">СЫГРАТЬ ЕЩЁ РАЗ</button>
      <div class="leaderboard"><p>ЛУЧШИЕ ИГРОКИ</p><div id="scores"><?php foreach ($scores as $index => $entry): ?><div><span><?= $index + 1 ?>. <?= htmlspecialchars((string) $entry['name'], ENT_QUOTES, 'UTF-8') ?></span><b><?= (int) $entry['score'] ?> ₽</b></div><?php endforeach; ?></div></div>
    </section>
  </div>
  <script>window.initialScores = <?= json_encode($scores, JSON_UNESCAPED_UNICODE) ?>;</script>
  <script src="script.js"></script>
</body>
</html>
