<?php
// Page de saisie des avis visiteurs.
// Envoie les données au traitement principal.
require_once 'guestbook.class.php';

$errors   = [];
$success  = false;
$formData = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = [
        'nom'       => $_POST['nom']       ?? '',
        'email'     => $_POST['email']     ?? '',
        'message'   => $_POST['message']   ?? '',
        'note'      => $_POST['note']      ?? '',
        'categorie' => $_POST['categorie'] ?? '',
    ];
    $gb     = new GuestBook();
    $result = $gb->saveMessage($formData);
    if ($result['success']) { $success = true; $formData = []; }
    else                    { $errors  = $result['errors']; }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Formulaire – Livre d'or</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,600;1,400&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
  :root{--ink:#1a1209;--cream:#f5f0e8;--parch:#ede7d4;--gold:#c8a84b;--gold2:#e8c96d;--rust:#8b3a2a;--sage:#5a6e4e;--mist:#8fa3a0;}
  *{box-sizing:border-box;margin:0;padding:0}
  body{background:var(--cream);font-family:'DM Sans',sans-serif;font-weight:300;min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:2rem}
  .wrapper{width:100%;max-width:520px}
  h1{font-family:'Cormorant Garamond',serif;font-size:2rem;color:var(--ink);text-align:center;margin-bottom:.3rem}
  h1 em{color:var(--gold);font-style:italic}
  .sub{text-align:center;color:var(--mist);font-size:.85rem;margin-bottom:2rem;letter-spacing:.05em}
  .card{background:#fdf9f2;border:1px solid rgba(200,168,75,.25);padding:2rem;box-shadow:0 4px 32px rgba(26,18,9,.12)}
  .field{margin-bottom:1.2rem}
  label{display:block;font-size:.75rem;text-transform:uppercase;letter-spacing:.1em;color:var(--mist);margin-bottom:.4rem}
  input,textarea,select{width:100%;padding:.7rem .9rem;background:var(--parch);border:1px solid rgba(200,168,75,.3);font-family:'DM Sans',sans-serif;font-size:.92rem;color:var(--ink);outline:none;transition:border-color .2s,box-shadow .2s}
  input:focus,textarea:focus,select:focus{border-color:var(--gold);box-shadow:0 0 0 3px rgba(200,168,75,.15)}
  textarea{min-height:110px;resize:vertical}
  .char-count{font-size:.72rem;color:var(--mist);text-align:right;margin-top:.2rem}
  .star-rating{display:flex;flex-direction:row-reverse;justify-content:flex-end;gap:.25rem}
  .star-rating input{display:none}
  .star-rating label{font-size:2rem;cursor:pointer;color:#ccc;transition:color .15s;text-transform:none;letter-spacing:0}
  .star-rating input:checked~label,.star-rating label:hover,.star-rating label:hover~label{color:var(--gold)}
  .btn{width:100%;padding:.85rem;background:linear-gradient(135deg,var(--ink),#2d1f0a);color:var(--gold2);border:none;font-family:'Cormorant Garamond',serif;font-size:1.1rem;letter-spacing:.15em;text-transform:uppercase;cursor:pointer;margin-top:.5rem;transition:opacity .2s}
  .btn:hover{opacity:.88}
  .alert{padding:.8rem 1rem;margin-bottom:1rem;font-size:.88rem}
  .alert-error{background:#fdf0ed;border-left:3px solid var(--rust);color:var(--rust)}
  .alert-success{background:#eef5ec;border-left:3px solid var(--sage);color:var(--sage)}
  .alert ul{padding-left:1.2rem;margin-top:.3rem}
  .back{display:block;text-align:center;margin-top:1rem;font-size:.85rem;color:var(--mist);text-decoration:none}
  .back:hover{color:var(--gold)}
</style>
</head>
<body>
<div class="wrapper">
  <h1>Livre d'or <em>intelligent</em></h1>
  <p class="sub">Musée des Expositions – Formulaire de saisie</p>

  <div class="card">
    <?php if ($success): ?>
    <div class="alert alert-success">Merci ! Votre avis a été enregistré avec succès.</div>
    <?php endif; ?>
    <?php if (!empty($errors)): ?>
    <div class="alert alert-error">
      <strong>Erreur(s) :</strong>
      <ul><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
    </div>
    <?php endif; ?>

    <form method="POST" action="form.php" novalidate>
      <div class="field">
        <label>Nom complet *</label>
        <input type="text" name="nom" required pattern="[A-Za-zÀ-ÿ\s'\-]{2,50}"
          placeholder="Ex. : Marie Dupont"
          value="<?= htmlspecialchars($formData['nom'] ?? '') ?>">
      </div>
      <div class="field">
        <label>Adresse e-mail *</label>
        <input type="email" name="email" required placeholder="marie@exemple.fr"
          value="<?= htmlspecialchars($formData['email'] ?? '') ?>">
      </div>
      <div class="field">
        <label>Votre note *</label>
        <div class="star-rating">
          <?php for($i=5;$i>=1;$i--): ?>
          <input type="radio" id="s<?=$i?>" name="note" value="<?=$i?>"
            <?= ($formData['note'] ?? '') == $i ? 'checked' : '' ?>>
          <label for="s<?=$i?>">★</label>
          <?php endfor; ?>
        </div>
      </div>
      <div class="field">
        <label>Catégorie *</label>
        <select name="categorie" required>
          <option value="">— Choisir —</option>
          <?php foreach(['Organisation','Contenu','Accueil'] as $c): ?>
          <option value="<?=$c?>" <?= ($formData['categorie'] ?? '') === $c ? 'selected' : '' ?>><?=$c?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label>Message * (min. 15 caractères)</label>
        <textarea name="message" id="msg" required minlength="15"
          placeholder="Décrivez votre expérience de visite…"><?= htmlspecialchars($formData['message'] ?? '') ?></textarea>
        <div class="char-count"><span id="cn">0</span> / 500 caractères</div>
      </div>
      <button type="submit" class="btn">Soumettre</button>
    </form>
  </div>
  <a href="index.php" class="back">Retour au livre d'or</a>
</div>
<script>
const ta=document.getElementById('msg'),cn=document.getElementById('cn');
ta.addEventListener('input',()=>cn.textContent=ta.value.length);
document.querySelector('form').addEventListener('submit',e=>{
  const errs=[];
  if(!document.querySelector('[name=nom]').value.trim()) errs.push('Nom obligatoire.');
  if(!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(document.querySelector('[name=email]').value)) errs.push('Email invalide.');
  if(!document.querySelector('[name=note]:checked')) errs.push('Sélectionnez une note.');
  if(document.getElementById('msg').value.trim().length<15) errs.push('Message trop court.');
  if(errs.length){e.preventDefault();alert(errs.join('\n'));}
});
</script>
</body>
</html>