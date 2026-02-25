<?php

class GuestBook
{
    private string $file = 'livredor.txt';

    // Liste de mots positifs utilisée pour détecter le sentiment.
    private array $positiveWords = [
        'excellent', 'magnifique', 'superbe', 'parfait', 'merveilleux', 'incroyable',
        'fantastique', 'bravo', 'génial', 'beau', 'belle', 'bien', 'super', 'formidable',
        'agréable', 'enrichissant', 'intéressant', 'passionnant', 'remarquable',
        'exceptionnel', 'impressionnant', 'recommend', 'adoré', 'ravi', 'ravie',
        'satisfait', 'satisfaite', 'top', 'wouah', 'wow', 'amazing', 'great'
    ];

    // Liste de mots négatifs utilisée pour détecter le sentiment.
    private array $negativeWords = [
        'mauvais', 'nul', 'horrible', 'terrible', 'décevant', 'ennuyeux', 'boring',
        'médiocre', 'catastrophique', 'désagréable', 'déçu', 'déçue', 'dommage',
        'insatisfait', 'problème', 'insuffisant', 'décevante', 'bof', 'moyen',
        'manque', 'manquait', 'trop long', 'mal', 'pire', 'regret', 'regrette'
    ];

    public function saveMessage(array $data): array
    {
        $errors = [];

        $nom = trim($data['nom'] ?? '');
        $email = trim($data['email'] ?? '');
        $message = trim($data['message'] ?? '');
        $note = (int)($data['note'] ?? 0);
        $categorie = trim($data['categorie'] ?? '');

        if ($nom === '') {
            $errors[] = 'Le nom est obligatoire.';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email invalide.';
        }

        if (strlen($message) < 15) {
            $errors[] = 'Le message doit contenir au moins 15 caractères.';
        }

        if ($note < 1 || $note > 5) {
            $errors[] = 'La note doit être entre 1 et 5.';
        }

        $allowedCategories = ['Organisation', 'Contenu', 'Accueil'];
        if (!in_array($categorie, $allowedCategories, true)) {
            $errors[] = 'Catégorie invalide.';
        }

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        // Bloque un doublon immédiat le même jour.
        if ($this->isDuplicate($email, $message)) {
            return [
                'success' => false,
                'errors' => ['Message en doublon détecté – veuillez patienter avant de soumettre à nouveau.']
            ];
        }

        // Vérifie les cas de spam les plus courants.
        if ($this->isSpam($message)) {
            return [
                'success' => false,
                'errors' => ['Message détecté comme spam. Veuillez rédiger un commentaire authentique.']
            ];
        }

        $date = date('Y-m-d');

        $line = implode('|', [
            $date,
            $this->cleanText($nom),
            $this->cleanText($email),
            $note,
            $this->cleanText($categorie),
            $this->cleanText($message)
        ]) . "\n";

        file_put_contents($this->file, $line, FILE_APPEND | LOCK_EX);

        return ['success' => true];
    }

    public function readMessages(array $filters = []): array
    {
        if (!file_exists($this->file)) {
            return [];
        }

        $lines = file($this->file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $messages = [];

        foreach ($lines as $line) {
            $parts = explode('|', $line);

            if (count($parts) < 6) {
                continue;
            }

            $msg = [
                'date' => $parts[0],
                'nom' => $parts[1],
                'email' => $parts[2],
                'note' => (int)$parts[3],
                'categorie' => $parts[4],
                'message' => $parts[5],
                'sentiment' => $this->analyzeSentiment($parts[5]),
            ];

            if (isset($filters['note']) && $filters['note'] !== '' && $msg['note'] !== (int)$filters['note']) {
                continue;
            }

            if (!empty($filters['note_min']) && $msg['note'] < (int)$filters['note_min']) {
                continue;
            }

            if (!empty($filters['categorie']) && $msg['categorie'] !== $filters['categorie']) {
                continue;
            }

            if (!empty($filters['sentiment']) && $msg['sentiment'] !== $filters['sentiment']) {
                continue;
            }

            $messages[] = $msg;
        }

        // Trie les messages du plus récent au plus ancien.
        usort($messages, function ($a, $b) {
            return strcmp($b['date'], $a['date']);
        });

        return $messages;
    }

    public function getStats(): array
    {
        $all = $this->readMessages();

        if (empty($all)) {
            return [
                'total' => 0,
                'moyenne' => 0,
                'categorie_top' => 'N/A',
                'sentiments' => []
            ];
        }

        $total = count($all);
        $sumNotes = array_sum(array_column($all, 'note'));
        $moyenne = round($sumNotes / $total, 2);

        $cats = array_count_values(array_column($all, 'categorie'));
        arsort($cats);
        $categorie_top = array_key_first($cats);

        $sentiments = array_count_values(array_column($all, 'sentiment'));

        $noteDist = array_fill(1, 5, 0);
        foreach ($all as $message) {
            $noteDist[$message['note']]++;
        }

        return compact('total', 'moyenne', 'categorie_top', 'sentiments', 'cats', 'noteDist');
    }

    public function analyzeSentiment(string $text): string
    {
        $text = mb_strtolower($text);
        $positiveCount = 0;
        $negativeCount = 0;

        foreach ($this->positiveWords as $word) {
            if (str_contains($text, $word)) {
                $positiveCount++;
            }
        }

        foreach ($this->negativeWords as $word) {
            if (str_contains($text, $word)) {
                $negativeCount++;
            }
        }

        if ($positiveCount > $negativeCount) {
            return 'positif';
        }

        if ($negativeCount > $positiveCount) {
            return 'négatif';
        }

        return 'neutre';
    }

    // Nettoie le texte pour le format du fichier texte.
    private function cleanText(string $text): string
    {
        $text = strip_tags($text);
        $text = str_replace(["\r", "\n", "|"], ' ', $text);

        return trim($text);
    }

    private function isDuplicate(string $email, string $message): bool
    {
        $today = date('Y-m-d');
        $cleanEmail = $this->cleanText($email);
        $cleanMessage = $this->cleanText($message);
        $messages = $this->readMessages();

        foreach ($messages as $savedMessage) {
            if ($savedMessage['date'] !== $today) {
                continue;
            }

            if ($savedMessage['email'] !== $cleanEmail) {
                continue;
            }

            if (trim($savedMessage['message']) === trim($cleanMessage)) {
                return true;
            }
        }

        return false;
    }

    private function isSpam(string $text): bool
    {
        // Exemple : "aaaaaaa"
        if (preg_match('/(.)\1{5,}/', $text)) {
            return true;
        }

        // Plus de 70% de lettres en majuscules.
        $letters = preg_replace('/[^a-zA-Z]/', '', $text);
        if (strlen($letters) > 10) {
            $upperLetters = strlen(preg_replace('/[^A-Z]/', '', $letters));
            if ($upperLetters / strlen($letters) > 0.7) {
                return true;
            }
        }

        // Trop de liens dans le message.
        if (preg_match_all('/https?:\/\//', $text) > 3) {
            return true;
        }

        return false;
    }
}