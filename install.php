<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';
if (installed()) { header('Location: index.php', true, 302); exit; }
$issues=[]; $error='';
$required=['pdo_mysql','mbstring','json','openssl'];
foreach ($required as $ext) if (!extension_loaded($ext)) $issues[]='Extension PHP manquante : '.$ext;
if (PHP_VERSION_ID < 80200) $issues[]='PHP 8.2 minimum nécessaire.';
if (!is_writable(__DIR__ . '/config')) $issues[]='Le répertoire config/ doit être accessible en écriture pendant l’installation.';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    check_csrf();
    try {
        if ($issues) throw new RuntimeException('Corrige les prérequis avant installation.');
        $host=trim((string)($_POST['db_host']??''));
        $port=(int)($_POST['db_port']??3306);
        $name=trim((string)($_POST['db_name']??''));
        $user=trim((string)($_POST['db_user']??''));
        $pass=(string)($_POST['db_pass']??'');
        $email=mb_strtolower(trim((string)($_POST['email']??'')));
        $display=trim((string)($_POST['display_name']??''));
        $password=(string)($_POST['password']??'');
        $workshop=trim((string)($_POST['workshop']??'Hydroaktif Manager'));
        if (!$host || !$name || !$user || !$display || !$workshop || $port<1 || $port>65535 || !filter_var($email,FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Vérifie les informations de connexion et le compte administrateur.');
        }
        if (strlen($password)<12) throw new InvalidArgumentException('Le mot de passe doit comporter au moins 12 caractères.');
        if (strlen($host)>255 || strlen($name)>100 || strlen($user)>150) throw new InvalidArgumentException('Paramètres MySQL trop longs.');
        $dsn=sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',$host,$port,$name);
        $pdo=new PDO($dsn,$user,$pass,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>false]);
        if ((int)$pdo->query('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name="users"')->fetchColumn()>0) {
            throw new RuntimeException('Cette base contient déjà une table users. Pour éviter de modifier une installation existante, utilise une base vide.');
        }
        foreach (require __DIR__ . '/database/schema.php' as $statement) $pdo->exec($statement);
        $stmt=$pdo->prepare('INSERT INTO users (display_name,email,password_hash,role) VALUES (?,?,?,"admin")');
        $stmt->execute([$display,$email,password_hash($password,PASSWORD_ARGON2ID)]);
        $stmt=$pdo->prepare('INSERT INTO settings (`key`,value) VALUES (?,?)');
        $stmt->execute(['workshop_name',$workshop]);
        $cfg=['host'=>$host,'port'=>$port,'database'=>$name,'username'=>$user,'password'=>$pass];
        $config_path=__DIR__.'/config/database.php';
        $temp_path=__DIR__.'/config/.db-'.bin2hex(random_bytes(6)).'.tmp';
        $content="<?php\n// Fichier privé généré par l'installateur. Ne pas publier.\nreturn ".var_export($cfg,true).";\n";
        if (file_put_contents($temp_path,$content,LOCK_EX)===false || !rename($temp_path,$config_path)) {
            throw new RuntimeException('Impossible d’écrire config/database.php. Vérifie les permissions.');
        }
        @chmod($config_path,0600);
        if (file_put_contents(__DIR__.'/config/installed.lock', 'installed'.PHP_EOL, LOCK_EX)===false) {
            @unlink($config_path);
            throw new RuntimeException('Impossible de verrouiller l’installation.');
        }
        session_regenerate_id(true);
        flash('Installation terminée. Connecte-toi à ton atelier.');
        header('Location: index.php?page=login',true,303); exit;
    } catch (Throwable $ex) {
        // Ne pas dévoiler les secrets de connexion dans les erreurs PDO.
        $error= $ex instanceof PDOException ? 'Connexion MySQL ou création des tables impossible. Vérifie les identifiants, les droits et que la base est vide.' : $ex->getMessage();
    }
}
?><!doctype html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>Installation · Hydroaktif Manager</title><link rel="stylesheet" href="assets/css/app.css"></head>
<body class="auth-body"><div class="setup-wrap"><div class="brand brand-big"><span class="brand-mark">H</span><span>Hydroaktif <b>Manager</b><small>Configuration initiale</small></span></div>
<div class="setup-card"><div class="eyebrow">INSTALLATION · ÉTAPE UNIQUE</div><h1>Bienvenue dans ton atelier.</h1><p class="muted">Ton hébergement PHP/MySQL suffit. Les tables seront créées dans une base <strong>vide, déjà existante</strong>.</p>
<?php if ($issues): ?><div class="alert alert-error"><strong>Prérequis à corriger</strong><ul><?php foreach($issues as $issue): ?><li><?=e($issue)?></li><?php endforeach ?></ul></div><?php else: ?><div class="alert alert-success">PHP <?=e(PHP_VERSION)?> · Extensions requises disponibles · Dossier config accessible</div><?php endif ?>
<?php if ($error): ?><div class="alert alert-error"><?=e($error)?></div><?php endif ?>
<form method="post" autocomplete="off" class="form-stack"><?=csrf_input()?>
<h2>01 — Base de données</h2><div class="form-grid"><label>Hôte MySQL<input required name="db_host" value="<?=e((string)($_POST['db_host']??'localhost'))?>" placeholder="localhost"></label><label>Port<input required name="db_port" type="number" min="1" max="65535" value="<?=e((string)($_POST['db_port']??'3306'))?>"></label><label>Nom de la base<input required name="db_name" value="<?=e((string)($_POST['db_name']??''))?>"></label><label>Utilisateur MySQL<input required name="db_user" value="<?=e((string)($_POST['db_user']??''))?>"></label><label class="full">Mot de passe MySQL<input name="db_pass" type="password" autocomplete="new-password"></label></div>
<h2>02 — Ton atelier</h2><div class="form-grid"><label>Nom de l'atelier<input required name="workshop" value="<?=e((string)($_POST['workshop']??'Hydroaktif Manager'))?>"></label><label>Nom affiché<input required name="display_name" value="<?=e((string)($_POST['display_name']??''))?>"></label><label>E-mail administrateur<input required type="email" name="email" value="<?=e((string)($_POST['email']??''))?>"></label><label>Mot de passe administrateur (12 caractères min.)<input required minlength="12" type="password" name="password" autocomplete="new-password"></label></div>
<button class="btn btn-primary btn-wide" type="submit" <?= $issues?'disabled':'' ?>>Créer Hydroaktif Manager <span aria-hidden="true">→</span></button></form>
<p class="fineprint">Sécurité : utilise HTTPS, un mot de passe unique et réserve une base de données dédiée. Le fichier de configuration et l'installateur seront verrouillés après installation.</p></div></div></body></html>
