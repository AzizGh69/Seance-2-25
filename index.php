<?php
require_once 'guestbook.class.php';

$gb = new GuestBook();
$errors   = [];
$success  = false;
$formData = [];

// Gère l'envoi du formulaire.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit') {
    $formData = [
        'nom'       => $_POST['nom']       ?? '',
        'email'     => $_POST['email']     ?? '',
        'message'   => $_POST['message']   ?? '',
        'note'      => $_POST['note']      ?? '',
        'categorie' => $_POST['categorie'] ?? '',
    ];
    $result = $gb->saveMessage($formData);
    if ($result['success']) {
        $success  = true;
        $formData = [];
    } else {
        $errors = $result['errors'];
    }
}

// Récupère les filtres depuis l'URL.
$filters = [];
if (isset($_GET['note']) && $_GET['note'] !== '') $filters['note'] = $_GET['note'];
if (!empty($_GET['categorie'])) $filters['categorie'] = $_GET['categorie'];
if (!empty($_GET['sentiment'])) $filters['sentiment'] = $_GET['sentiment'];

$messages = $gb->readMessages($filters);
$stats    = $gb->getStats();

function stars(int $n): string {
    return str_repeat('★', $n) . str_repeat('☆', 5 - $n);
}
function sentimentBadge(string $s): string {
  $map = ['positif'=>'badge-pos','négatif'=>'badge-neg','neutre'=>'badge-neu'];
  $cls = $map[$s] ?? 'badge-neu';
  return "<span class='badge {$cls}'>{$s}</span>";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Livre d'or – Musée des Expositions</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;1,400&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
  :root {
    --ink:    #1a1209;
    --cream:  #f5f0e8;
    --parch:  #ede7d4;
    --gold:   #c8a84b;
    --gold2:  #e8c96d;
    --rust:   #8b3a2a;
    --sage:   #5a6e4e;
    --mist:   #8fa3a0;
    --card:   #fdf9f2;
    --shadow: rgba(26,18,9,.15);
  }
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    background: var(--cream);
    color: var(--ink);
    font-family: 'DM Sans', sans-serif;
    font-weight: 300;
    min-height: 100vh;
    background-image:
      radial-gradient(ellipse at 10% 20%, rgba(200,168,75,.12) 0%, transparent 50%),
      radial-gradient(ellipse at 90% 80%, rgba(90,110,78,.10) 0%, transparent 50%),
      url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23c8a84b' fill-opacity='0.04'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
  }

  /* ── Header ── */
  header {
    background: linear-gradient(135deg, var(--ink) 0%, #2d1f0a 100%);
    color: var(--cream);
    text-align: center;
    padding: 3.5rem 2rem 3rem;
    position: relative;
    overflow: hidden;
  }
  header::before {
    content: '';
    position: absolute; inset: 0;
    background: url("data:image/svg+xml,%3Csvg width='80' height='80' viewBox='0 0 80 80' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='%23c8a84b' fill-opacity='0.06'%3E%3Cpath d='M0 0h40v40H0zm40 40h40v40H40z'/%3E%3C/g%3E%3C/svg%3E");
  }
  .header-ornament {
    font-size: 1.1rem; letter-spacing: .4em;
    color: var(--gold); text-transform: uppercase;
    margin-bottom: .75rem; position: relative;
  }
  header h1 {
    font-family: 'Cormorant Garamond', serif;
    font-size: clamp(2.2rem, 5vw, 3.8rem);
    font-weight: 600; line-height: 1.1;
    position: relative;
  }
  header h1 em {
    font-style: italic; color: var(--gold2);
  }
  header p {
    margin-top: .75rem; font-size: .95rem;
    opacity: .65; letter-spacing: .05em;
    position: relative;
  }
  .gold-rule {
    width: 6rem; height: 1px;
    background: linear-gradient(90deg, transparent, var(--gold), transparent);
    margin: 1.2rem auto;
  }

  /* ── Layout ── */
  .container { max-width: 1200px; margin: 0 auto; padding: 2.5rem 1.5rem; }

  .grid-main {
    display: grid;
    grid-template-columns: 420px 1fr;
    gap: 2.5rem;
    align-items: start;
  }
  @media (max-width: 900px) { .grid-main { grid-template-columns: 1fr; } }

  /* ── Card ── */
  .card {
    background: var(--card);
    border-radius: 2px;
    border: 1px solid rgba(200,168,75,.25);
    box-shadow: 0 4px 32px var(--shadow), 0 0 0 1px rgba(255,255,255,.6) inset;
    padding: 2rem;
  }
  .card-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.55rem; font-weight: 600;
    color: var(--ink); border-bottom: 1px solid var(--gold);
    padding-bottom: .6rem; margin-bottom: 1.5rem;
    display: flex; align-items: center; gap: .5rem;
  }

  /* ── Form ── */
  .field { margin-bottom: 1.25rem; }
  label {
    display: block; font-size: .78rem; letter-spacing: .1em;
    text-transform: uppercase; color: var(--mist);
    margin-bottom: .4rem;
  }
  input[type=text], input[type=email], textarea, select {
    width: 100%; padding: .7rem .9rem;
    background: var(--parch);
    border: 1px solid rgba(200,168,75,.3);
    border-radius: 1px;
    font-family: 'DM Sans', sans-serif;
    font-size: .93rem; color: var(--ink);
    transition: border-color .2s, box-shadow .2s;
    outline: none;
  }
  input:focus, textarea:focus, select:focus {
    border-color: var(--gold);
    box-shadow: 0 0 0 3px rgba(200,168,75,.15);
  }
  textarea { resize: vertical; min-height: 110px; }
  .char-count { font-size: .72rem; color: var(--mist); text-align: right; margin-top: .2rem; }

  /* Star Rating */
  .star-rating { display: flex; gap: .25rem; flex-direction: row-reverse; justify-content: flex-end; }
  .star-rating input { display: none; }
  .star-rating label {
    font-size: 1.8rem; cursor: pointer; color: #ccc;
    transition: color .15s; text-transform: none; letter-spacing: 0;
  }
  .star-rating input:checked ~ label,
  .star-rating label:hover,
  .star-rating label:hover ~ label { color: var(--gold); }

  .btn-submit {
    width: 100%; padding: .85rem;
    background: linear-gradient(135deg, var(--ink), #2d1f0a);
    color: var(--gold2); border: none;
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.1rem; letter-spacing: .15em;
    text-transform: uppercase; cursor: pointer;
    border-radius: 1px;
    transition: opacity .2s, transform .1s;
  }
  .btn-submit:hover { opacity: .88; }
  .btn-submit:active { transform: scale(.99); }

  .alert {
    padding: .8rem 1rem; border-radius: 1px;
    margin-bottom: 1.2rem; font-size: .88rem;
  }
  .alert-error { background:#fdf0ed; border-left:3px solid var(--rust); color: var(--rust); }
  .alert-success { background:#eef5ec; border-left:3px solid var(--sage); color: var(--sage); }
  .alert ul { padding-left: 1rem; margin-top: .3rem; }

  /* ── Stats ── */
  .stats-row {
    display: grid; grid-template-columns: repeat(auto-fit, minmax(130px,1fr));
    gap: 1rem; margin-bottom: 2rem;
  }
  .stat-box {
    background: var(--ink);
    color: var(--cream); border-radius: 2px;
    padding: 1.2rem 1rem; text-align: center;
    box-shadow: 0 4px 16px var(--shadow);
  }
  .stat-box .val {
    font-family: 'Cormorant Garamond', serif;
    font-size: 2.2rem; color: var(--gold2);
    line-height: 1;
  }
  .stat-box .lbl { font-size: .72rem; text-transform: uppercase; letter-spacing: .1em; opacity: .6; margin-top: .3rem; }

  /* Note dist */
  .note-dist { display: flex; flex-direction: column; gap: .35rem; margin-top: .8rem; }
  .note-row { display: flex; align-items: center; gap: .5rem; font-size: .8rem; }
  .note-row span:first-child { width: 1.5rem; color: var(--gold); text-align: right; }
  .bar-bg { flex: 1; height: 6px; background: rgba(200,168,75,.15); border-radius: 3px; overflow: hidden; }
  .bar-fill { height: 100%; background: var(--gold); border-radius: 3px; transition: width .4s; }
  .note-row .cnt { width: 1.5rem; color: var(--mist); }

  /* ── Filters ── */
  .filter-row {
    display: flex; gap: .75rem; flex-wrap: wrap;
    margin-bottom: 1.5rem; align-items: center;
  }
  .filter-row select, .filter-row input {
    padding: .45rem .75rem; font-size: .85rem; flex: 1; min-width: 140px;
  }
  .filter-row .btn-filter {
    padding: .45rem 1.2rem;
    background: var(--ink); color: var(--gold2);
    border: none; font-family: 'DM Sans', sans-serif;
    font-size: .85rem; cursor: pointer; border-radius: 1px;
  }
  .btn-reset {
    padding: .45rem .9rem;
    background: transparent; color: var(--mist);
    border: 1px solid var(--mist); font-size: .82rem;
    cursor: pointer; border-radius: 1px; text-decoration: none;
  }

  /* ── Message cards ── */
  .message-list { display: flex; flex-direction: column; gap: 1rem; }
  .msg-card {
    background: var(--card);
    border: 1px solid rgba(200,168,75,.2);
    border-radius: 2px;
    padding: 1.3rem 1.5rem;
    box-shadow: 0 2px 12px var(--shadow);
    animation: fadeUp .35s ease both;
  }
  @keyframes fadeUp { from{opacity:0;transform:translateY(10px)} to{opacity:1;transform:translateY(0)} }
  .msg-header {
    display: flex; justify-content: space-between;
    align-items: flex-start; flex-wrap: wrap; gap: .5rem;
    margin-bottom: .8rem;
  }
  .msg-author {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.15rem; font-weight: 600;
  }
  .msg-meta { font-size: .78rem; color: var(--mist); margin-top: .15rem; }
  .msg-stars { color: var(--gold); font-size: 1.05rem; letter-spacing: .05em; }
  .msg-body { font-size: .92rem; line-height: 1.65; color: #3a2e1e; }
  .badge {
    display: inline-block; font-size: .72rem; padding: .2rem .6rem;
    border-radius: 20px; font-weight: 500;
  }
  .badge-pos { background: #eef5ec; color: var(--sage); }
  .badge-neg { background: #fdf0ed; color: var(--rust); }
  .badge-neu { background: #f0f3f5; color: var(--mist); }
  .cat-badge {
    display: inline-block; font-size: .7rem;
    background: rgba(200,168,75,.15); color: var(--gold);
    padding: .15rem .5rem; border-radius: 2px; letter-spacing: .05em;
    text-transform: uppercase;
  }

  .no-msg {
    text-align: center; color: var(--mist);
    font-style: italic; padding: 2rem;
  }

  /* ── Sentiment summary ── */
  .sentiment-pills { display: flex; gap: .6rem; flex-wrap: wrap; margin-top: .6rem; }
  .spill { padding: .3rem .75rem; border-radius: 20px; font-size: .82rem; }

  footer {
    text-align: center; padding: 2rem;
    font-size: .78rem; color: var(--mist);
    border-top: 1px solid rgba(200,168,75,.2);
    margin-top: 3rem;
  }
</style>
</head>
<body>

<header>
  <div class="header-ornament">Musée des Expositions</div>
  <h1>Livre d'or <em>intelligent</em></h1>
  <div class="gold-rule"></div>
  <p>Partagez votre expérience · Enrichissez nos expositions</p>
</header>

<div class="container">

  <?php if ($stats['total'] > 0): ?>
  <div class="stats-row">
    <div class="stat-box"><div class="val"><?= $stats['total'] ?></div><div class="lbl">Avis collectés</div></div>
    <div class="stat-box"><div class="val"><?= number_format($stats['moyenne'],1) ?></div><div class="lbl">Note moyenne</div></div>
    <div class="stat-box"><div class="val" style="font-size:1.1rem;padding-top:.4rem"><?= $stats['categorie_top'] ?></div><div class="lbl">Catégorie phare</div></div>
    <?php $sentiments = $stats['sentiments']; ?>
    <div class="stat-box"><div class="val"><?= $sentiments['positif'] ?? 0 ?></div><div class="lbl">Avis positifs</div></div>
    <div class="stat-box"><div class="val"><?= $sentiments['négatif'] ?? 0 ?></div><div class="lbl">Avis négatifs</div></div>
  </div>

  <div class="card" style="margin-bottom:2rem">
    <div class="card-title">Répartition des notes</div>
    <div class="note-dist">
      <?php $maxCount = max(array_values($stats['noteDist']) ?: [1]); ?>
      <?php foreach (array_reverse($stats['noteDist'], true) as $n => $c): ?>
      <div class="note-row">
        <span><?= $n ?>★</span>
        <div class="bar-bg"><div class="bar-fill" style="width:<?= $maxCount ? round($c/$maxCount*100) : 0 ?>%"></div></div>
        <span class="cnt"><?= $c ?></span>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <div class="grid-main">

    <div>
      <div class="card">
        <div class="card-title">Déposer un avis</div>

        <?php if ($success): ?>
        <div class="alert alert-success">Votre message a été enregistré avec succès. Merci !</div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
          <strong>Veuillez corriger les erreurs suivantes :</strong>
          <ul><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
        </div>
        <?php endif; ?>

        <form method="POST" action="#" novalidate>
          <input type="hidden" name="action" value="submit">

          <div class="field">
            <label>Nom complet *</label>
            <input type="text" name="nom" required
              pattern="[A-Za-zÀ-ÿ\s'\-]{2,50}"
              placeholder="Marie Dupont"
              value="<?= htmlspecialchars($formData['nom'] ?? '') ?>">
          </div>

          <div class="field">
            <label>Adresse e-mail *</label>
            <input type="email" name="email" required
              placeholder="marie@exemple.fr"
              value="<?= htmlspecialchars($formData['email'] ?? '') ?>">
          </div>

          <div class="field">
            <label>Votre note *</label>
            <div class="star-rating">
              <?php for ($i=5;$i>=1;$i--): ?>
              <input type="radio" name="note" id="star<?=$i?>" value="<?=$i?>"
                <?= ($formData['note'] ?? '') == $i ? 'checked' : '' ?>>
              <label for="star<?=$i?>" title="<?=$i?> étoile<?=$i>1?'s':''?>">★</label>
              <?php endfor; ?>
            </div>
          </div>

          <div class="field">
            <label>Catégorie *</label>
            <select name="categorie" required>
              <option value="">— Sélectionnez —</option>
              <?php foreach (['Organisation','Contenu','Accueil'] as $c): ?>
              <option value="<?=$c?>" <?= ($formData['categorie'] ?? '') === $c ? 'selected' : '' ?>><?=$c?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="field">
            <label>Message * (min. 15 caractères)</label>
            <textarea name="message" id="msgInput" required minlength="15"
              placeholder="Partagez votre expérience de la visite…"><?= htmlspecialchars($formData['message'] ?? '') ?></textarea>
            <div class="char-count"><span id="charNum">0</span> caractères</div>
          </div>

          <button type="submit" class="btn-submit">Soumettre mon avis</button>
        </form>
      </div>
    </div>

    <div>
      <div class="card">
        <div class="card-title">Avis des visiteurs (<?= count($messages) ?>)</div>

        <form method="GET" action="">
          <div class="filter-row">
            <select name="note">
              <option value="">Toutes les notes</option>
              <?php for ($i=1; $i<=5; $i++): ?>
              <option value="<?= $i ?>" <?= ($_GET['note'] ?? '') == (string)$i ? 'selected' : '' ?>><?= $i ?> ★</option>
              <?php endfor; ?>
            </select>
            <select name="categorie">
              <option value="">Toutes catégories</option>
              <?php foreach (['Organisation','Contenu','Accueil'] as $c): ?>
              <option value="<?=$c?>" <?= ($_GET['categorie'] ?? '') === $c ? 'selected' : '' ?>><?=$c?></option>
              <?php endforeach; ?>
            </select>
            <select name="sentiment">
              <option value="">Tous sentiments</option>
              <option value="positif" <?= ($_GET['sentiment'] ?? '') === 'positif' ? 'selected' : '' ?>>Positif</option>
              <option value="négatif" <?= ($_GET['sentiment'] ?? '') === 'négatif' ? 'selected' : '' ?>>Négatif</option>
              <option value="neutre"  <?= ($_GET['sentiment'] ?? '') === 'neutre'  ? 'selected' : '' ?>>Neutre</option>
            </select>
            <button type="submit" class="btn-filter">Filtrer</button>
            <a href="?" class="btn-reset">Reset</a>
          </div>
        </form>

        <?php if (empty($messages)): ?>
        <p class="no-msg">Aucun avis ne correspond à vos critères.</p>
        <?php else: ?>
        <div class="message-list">
          <?php foreach ($messages as $m): ?>
          <div class="msg-card">
            <div class="msg-header">
              <div>
                <div class="msg-author"><?= htmlspecialchars($m['nom']) ?></div>
                <div class="msg-meta">
                  <?= $m['date'] ?> &nbsp;·&nbsp;
                  <span class="cat-badge"><?= htmlspecialchars($m['categorie']) ?></span>
                  &nbsp; <?= sentimentBadge($m['sentiment']) ?>
                </div>
              </div>
              <div class="msg-stars"><?= stars($m['note']) ?></div>
            </div>
            <div class="msg-body">"<?= nl2br(htmlspecialchars($m['message'])) ?>"</div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>

  </div><!-- .grid-main -->
</div><!-- .container -->

<footer>
  <div>Livre d'or intelligent · Musée des Expositions · <?= date('Y') ?></div>
  <div style="margin-top:.4rem;opacity:.6">Données stockées localement · aucun cookie tiers</div>
</footer>

<script>
// Met à jour le compteur de caractères en direct.
const ta = document.getElementById('msgInput');
const cn = document.getElementById('charNum');
function updateCount() { cn.textContent = ta.value.length; }
ta.addEventListener('input', updateCount);
updateCount();

// Vérifie les champs avant l'envoi du formulaire.
document.querySelector('form[method=POST]').addEventListener('submit', function(e) {
  const nom   = this.nom.value.trim();
  const email = this.email.value.trim();
  const msg   = this.message.value.trim();
  const note  = this.note?.value;
  const errs  = [];
  if (!nom || nom.length < 2)                                errs.push('Nom trop court.');
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email))            errs.push('Email invalide.');
  if (msg.length < 15)                                       errs.push('Message trop court (min. 15 car.).');
  if (!note)                                                 errs.push('Veuillez sélectionner une note.');
  if (errs.length) {
    e.preventDefault();
    alert('Erreur(s) :\n• ' + errs.join('\n• '));
  }
});
</script>
</body>
</html>