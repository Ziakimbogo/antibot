<?php
// Configuration
$redirect_bot = "https://www.google.com"; // Redirection des bots
$redirect_human = ""; // Laissez vide pour continuer normalement, ou définissez URL pour rediriger les humains

class AntiBotProtection {
    private $config = [
        'whitelist_bots' => [
            'Googlebot',
            'Bingbot',
            'Slurp',
            'DuckDuckBot',
            'Baiduspider',
            'YandexBot',
        ],
        'whitelist_ips' => [],
        'log_attempts' => true,
        'log_file' => 'bot_attempts.log',
    ];
    
    private $suspicion_score = 0;
    private $is_bot = false;
    
    public function __construct(array $custom_config = []) {
        $this->config = array_merge($this->config, $custom_config);
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['antibot'])) {
            $_SESSION['antibot'] = [
                'first_visit' => time(),
                'visit_count' => 0,
                'previous_ip' => $this->getClientIP(),
                'fingerprint' => '',
                'request_count' => 1,
                'request_start_time' => time(),
            ];
        } else {
            $_SESSION['antibot']['visit_count']++;
            $this->checkRequestRate();
        }
    }
    
    public function check() {
        // Vérifier si l'IP est whitelistée
        if (in_array($this->getClientIP(), $this->config['whitelist_ips'])) {
            return false; // Pas un bot
        }
        
        // Exécuter toutes les vérifications
        $this->checkUserAgent();
        $this->checkHeaders();
        $this->checkFingerprint();
        $this->checkSessionConsistency();
        
        // Déterminer si c'est un bot
        $this->is_bot = ($this->suspicion_score >= 30);
        
        if ($this->is_bot && $this->config['log_attempts']) {
            $this->logAttempt("Bot détecté, score: {$this->suspicion_score}");
        }
        
        return $this->is_bot;
    }
    
    private function checkUserAgent() {
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        if (empty($user_agent)) {
            $this->suspicion_score += 25;
            return;
        }
        
        foreach ($this->config['whitelist_bots'] as $bot) {
            if (stripos($user_agent, $bot) !== false) {
                return;
            }
        }
        
        $bot_signatures = [
            'bot', 'crawl', 'spider', 'scrape', 'fetch', 'http', 'java', 'wget', 'curl', 'phantom',
            'python', 'requests', 'go-http', 'ruby', 'perl', 'selenium', 'puppeteer', 'headless',
            'archive', 'harvester'
        ];
        
        foreach ($bot_signatures as $signature) {
            if (stripos($user_agent, $signature) !== false) {
                $this->suspicion_score += 30;
                return;
            }
        }
        
        if (strlen($user_agent) < 30 || strlen($user_agent) > 500) {
            $this->suspicion_score += 10;
        }
    }
    
    private function checkHeaders() {
        if (empty($_SERVER['HTTP_ACCEPT']) || empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $this->suspicion_score += 15;
        }
        
        if (empty($_SERVER['HTTP_CACHE_CONTROL']) && empty($_SERVER['HTTP_PRAGMA'])) {
            $this->suspicion_score += 5;
        }
    }
    
    private function checkRequestRate() {
        if (!isset($_SESSION['antibot']['request_count'])) {
            $_SESSION['antibot']['request_count'] = 1;
            $_SESSION['antibot']['request_start_time'] = time();
            return;
        }
        
        $elapsed = time() - $_SESSION['antibot']['request_start_time'];
        if ($elapsed > 60) {
            $_SESSION['antibot']['request_count'] = 1;
            $_SESSION['antibot']['request_start_time'] = time();
            return;
        }
        
        $_SESSION['antibot']['request_count']++;
        
        if ($_SESSION['antibot']['request_count'] > 30) {
            $this->suspicion_score += 20;
        }
    }
    
    private function checkFingerprint() {
        $current_fingerprint = md5(
            ($_SERVER['HTTP_USER_AGENT'] ?? '') .
            ($_SERVER['HTTP_ACCEPT'] ?? '') .
            ($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '') .
            ($_SERVER['HTTP_ACCEPT_ENCODING'] ?? '')
        );
        
        if (!empty($_SESSION['antibot']['fingerprint'])) {
            if ($_SESSION['antibot']['fingerprint'] !== $current_fingerprint) {
                $this->suspicion_score += 20;
            }
        }
        
        $_SESSION['antibot']['fingerprint'] = $current_fingerprint;
    }
    
    private function checkSessionConsistency() {
        $current_ip = $this->getClientIP();
        if (!empty($_SESSION['antibot']['previous_ip']) && 
            $_SESSION['antibot']['previous_ip'] !== $current_ip) {
            $this->suspicion_score += 10;
        }
        
        $_SESSION['antibot']['previous_ip'] = $current_ip;
        
        if (time() - $_SESSION['antibot']['first_visit'] < 60 && $_SESSION['antibot']['visit_count'] > 10) {
            $this->suspicion_score += 15;
        }
    }
    
    private function getClientIP() {
        $ip_keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
        
        foreach ($ip_keys as $key) {
            if (isset($_SERVER[$key]) && filter_var($_SERVER[$key], FILTER_VALIDATE_IP)) {
                return $_SERVER[$key];
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    private function logAttempt($message) {
        if (!$this->config['log_attempts']) {
            return;
        }
        
        $log_entry = date('[Y-m-d H:i:s]') . ' IP: ' . $this->getClientIP() . ' - ' . $message . PHP_EOL;
        file_put_contents($this->config['log_file'], $log_entry, FILE_APPEND);
    }
}

// Exécution automatique lors de l'inclusion
$anti_bot = new AntiBotProtection();
$is_bot = $anti_bot->check();

if ($is_bot) {
    // Rediriger les bots vers Google
    header("Location: $redirect_bot");
    exit;
} else if (!empty($redirect_human)) {
    // Rediriger les humains si spécifié
    header("Location: $redirect_human");
    exit;
}
// Sinon, continuer l'exécution normale de la page pour les humains
?>
