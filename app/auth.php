<?php
function authenticate(string $email, string $password): bool {
    $rate = $_SESSION['login_rate'] ?? ['count'=>0,'until'=>0];
    if ($rate['until'] > time()) throw new RuntimeException('Trop de tentatives. Réessaie dans quelques minutes.');
    $stmt = db()->prepare('SELECT id, display_name, email, role, password_hash FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([mb_strtolower(trim($email))]);
    $user = $stmt->fetch();
    if (!$user || !password_verify($password, $user['password_hash'])) {
        $n = (int)$rate['count'] + 1;
        $_SESSION['login_rate']=['count'=>$n,'until'=>$n>=5?time()+300:0];
        return false;
    }
    session_regenerate_id(true);
    $_SESSION['login_rate']=['count'=>0,'until'=>0];
    unset($user['password_hash']); $_SESSION['user'] = $user;
    return true;
}
function logout_user(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(),'',time()-42000,$p['path'],$p['domain'],$p['secure'],$p['httponly']);
    }
    session_destroy();
}
