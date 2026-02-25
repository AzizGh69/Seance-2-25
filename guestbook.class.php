<?php

class GuestBook {
    private string $file = 'livredor.txt';

    // ─── Sentiment lexicons ────────────────────────────────────────────────
    private array $positiveWords = [
        'excellent','magnifique','superbe','parfait','merveilleux','incroyable',
        'fantastique','bravo','génial','beau','belle','bien','super','formidable',
        'agréable','enrichissant','intéressant','passionnant','remarquable',
        'exceptionnel','impressionnant','recommend','adoré','ravi','ravie',
        'satisfait','satisfaite','top','wouah','wow','amazing','great'
    ];
    private array $negativeWords = [
        'mauvais','nul','horrible','terrible','décevant','ennuyeux','boring',
        'médiocre','catastrophique','désagréable','déçu','déçue','dommage',
        'insatisfait','problème','insuffisant','décevante','bof','moyen',
        'manque','manquait','trop long','mal','pire','regret','regrette'
    ];

    // ─── Save ──────────────────────────────────────────────────────────────
    public function saveMessage(array $data): array {
        // Validate required fields
        $errors = [];
        $nom     = trim($data['nom'] ?? '');
        $email   = trim($data['email'] ?? '');
        $message = trim($data['message'] ?? '');
        $note    = (int)($data['note'] ?? 0);
        $categorie = trim($data['categorie'] ?? '');

        if (empty($nom))                          $errors[] = "Le nom est obligatoire.";
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email invalide.";
        if (strlen($message) < 15)                $errors[] = "Le message doit contenir au moins 15 caractères.";
        if ($note < 1 || $note > 5)               $errors[] = "La note doit être entre 1 et 5.";
        $cats = ['Organisation','Contenu','Accueil'];
        if (!in_array($categorie, $cats))         $errors[] = "Catégorie invalide.";

        if (!empty($errors)) return ['success' => false, 'errors' => $errors];

        // Anti-spam: duplicate check (same email + same message today)
        if ($this->isDuplicate($email, $message)) {
            return ['success' => false, 'errors' => ["Message en doublon détecté – veuillez patienter avant de soumettre à nouveau."]];
        }

        // Spam heuristic: all-caps, repeated chars, very short meaningful ratio
        if ($this->isSpam($message)) {
            return ['success' => false, 'errors' => ["Message détecté comme spam. Veuillez rédiger un commentaire authentique."]];
        }

        $date = date('Y-m-d');
        $line = implode('|', [
            $date,
            $this->sanitize($nom),
            $this->sanitize($email),
            $note,
            $this->sanitize($categorie),
            $this->sanitize($message)
        ]) . "\n";

        file_put_contents($this->file, $line, FILE_APPEND | LOCK_EX);
        return ['success' => true];
    }

    // ─── Read ──────────────────────────────────────────────────────────────
    public function readMessages(array $filters = []): array {
        if (!file_exists($this->file)) return [];
        $lines = file($this->file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $messages = [];
        foreach ($lines as $line) {
            $parts = explode('|', $line);
            if (count($parts) < 6) continue;
            $msg = [
                'date'      => $parts[0],
                'nom'       => $parts[1],
                'email'     => $parts[2],
                'note'      => (int)$parts[3],
                'categorie' => $parts[4],
                'message'   => $parts[5],
                'sentiment' => $this->analyzeSentiment($parts[5]),
            ];
            // Filters
            if (isset($filters['note']) && $filters['note'] !== '' && $msg['note'] !== (int)$filters['note']) continue;
            if (!empty($filters['note_min']) && $msg['note'] < (int)$filters['note_min']) continue;
            if (!empty($filters['categorie']) && $msg['categorie'] !== $filters['categorie'])  continue;
            if (!empty($filters['sentiment']) && $msg['sentiment'] !== $filters['sentiment'])  continue;
            $messages[] = $msg;
        }
        // Sort descending by date
        usort($messages, fn($a,$b) => strcmp($b['date'], $a['date']));
        return $messages;
    }

    // ─── Stats ─────────────────────────────────────────────────────────────
    public function getStats(): array {
        $all = $this->readMessages();
        if (empty($all)) return ['total'=>0,'moyenne'=>0,'categorie_top'=>'N/A','sentiments'=>[]];

        $total = count($all);
        $sumNotes = array_sum(array_column($all, 'note'));
        $moyenne  = round($sumNotes / $total, 2);

        $cats = array_count_values(array_column($all, 'categorie'));
        arsort($cats);
        $categorie_top = array_key_first($cats);

        $sentiments = array_count_values(array_column($all, 'sentiment'));

        // Notes distribution
        $noteDist = array_fill(1, 5, 0);
        foreach ($all as $m) $noteDist[$m['note']]++;

        return compact('total','moyenne','categorie_top','sentiments','cats','noteDist');
    }

    // ─── Sentiment Analysis ────────────────────────────────────────────────
    public function analyzeSentiment(string $text): string {
        $text  = mb_strtolower($text);
        $pos   = 0; $neg = 0;
        foreach ($this->positiveWords as $w) if (str_contains($text, $w)) $pos++;
        foreach ($this->negativeWords as $w) if (str_contains($text, $w)) $neg++;
        if ($pos > $neg) return 'positif';
        if ($neg > $pos) return 'négatif';
        return 'neutre';
    }

    // ─── Helpers ───────────────────────────────────────────────────────────
    private function sanitize(string $s): string {
        return str_replace(['|',"\n","\r"], ['',' ',' '], htmlspecialchars(strip_tags($s)));
    }

    private function isDuplicate(string $email, string $message): bool {
        $today = date('Y-m-d');
        $msgs  = $this->readMessages();
        foreach ($msgs as $m) {
            if ($m['date'] === $today && $m['email'] === htmlspecialchars($email)
                && trim($m['message']) === trim(htmlspecialchars($message))) return true;
        }
        return false;
    }

    private function isSpam(string $text): bool {
        // Too many repeated characters e.g. "aaaaaaa"
        if (preg_match('/(.)\1{5,}/', $text)) return true;
        // More than 70% uppercase
        $letters = preg_replace('/[^a-zA-Z]/', '', $text);
        if (strlen($letters) > 10) {
            $upper = strlen(preg_replace('/[^A-Z]/', '', $letters));
            if ($upper / strlen($letters) > 0.7) return true;
        }
        // Suspicious URL spam
        if (preg_match_all('/https?:\/\//', $text) > 3) return true;
        return false;
    }
}