<?php
declare(strict_types=1);
require __DIR__.'/app/bootstrap.php';
if (!installed()) { header('Location: install.php',true,302); exit; }
require __DIR__.'/app/auth.php';
require __DIR__.'/app/layout.php';
require __DIR__.'/app/actions.php';
require __DIR__.'/app/views.php';
$page=(string)($_GET['page']??'dashboard');
$valid_pages=['dashboard','filaments','components','projects','storage','equipment','settings','login'];
if (!in_array($page,$valid_pages,true)) { http_response_code(404); $page='dashboard'; }
if ($_SERVER['REQUEST_METHOD']==='POST') {
    check_csrf();
    $action=(string)($_POST['action']??'');
    if ($action==='login') {
        try {
            if (authenticate((string)($_POST['email']??''),(string)($_POST['password']??''))) go();
            flash('Adresse e-mail ou mot de passe incorrect.','error');
        } catch (Throwable $ex) { flash($ex->getMessage(),'error'); }
        go('login');
    }
    if ($action==='logout') { logout_user(); header('Location: index.php?page=login',true,303); exit; }
    require_login();
    try { handle_action($action); }
    catch (Throwable $ex) {
        error_log('Hydroaktif action failure: '.get_class($ex).' '. $ex->getMessage());
        $message= $ex instanceof InvalidArgumentException ? $ex->getMessage() : 'Opération impossible. Vérifie les données saisies et les contraintes de la base.';
        flash($message,'error');
    }
    go(in_array((string)($_POST['return_page']??''),$valid_pages,true)?(string)$_POST['return_page']:'dashboard');
}
if ($page==='login') { if (current_user()) go(); render_login(); exit; }
require_login();
match($page) {
 'dashboard'=>view_dashboard(), 'filaments'=>view_filaments(), 'components'=>view_components(),
 'projects'=>view_projects(), 'storage'=>view_storage(), 'equipment'=>view_equipment(),
 'settings'=>view_settings(), default=>view_dashboard()
};
