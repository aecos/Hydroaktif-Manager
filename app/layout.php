<?php
function icon(string $name, int $size=18): string {
  $paths=[
   'home'=>'<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
   'printer'=>'<rect x="6" y="2" width="12" height="7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a3 3 0 0 1 3-3h14a3 3 0 0 1 3 3v5a2 2 0 0 1-2 2h-2"/><path d="M6 14h12v8H6zM18 12h.01"/>',
   'cpu'=>'<rect x="5" y="5" width="14" height="14" rx="2"/><path d="M9 9h6v6H9zM9 1v4m6-4v4M9 19v4m6-4v4M19 9h4m-4 6h4M1 9h4m-4 6h4"/>',
   'projects'=>'<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 9h18M7 5V3h6l2 2"/>',
   'storage'=>'<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M3 15h18M10 6h4m-4 6h4m-4 6h4"/>',
   'tool'=>'<path d="M14 7a5 5 0 0 0-6.7 6.7l-5.1 5.1a2 2 0 0 0 2.8 2.8l5.1-5.1A5 5 0 0 0 17 9l-3 3-3-3 3-3z"/>',
   'settings'=>'<circle cx="12" cy="12" r="3"/><path d="M19 12a7 7 0 0 0-.2-1.8l2-1.5-2-3.5-2.4 1A7 7 0 0 0 13 5.1L12.6 2h-4l-.5 3.1A7 7 0 0 0 5.6 6.3l-2.4-1-2 3.5 2 1.5A7 7 0 0 0 3 12a7 7 0 0 0 .2 1.8l-2 1.5 2 3.5 2.4-1A7 7 0 0 0 8 18.9l.5 3.1h4l.5-3.1a7 7 0 0 0 2.5-1.2l2.4 1 2-3.5-2-1.5A7 7 0 0 0 19 12z" transform="translate(1 0) scale(.94)"/>',
   'search'=>'<circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/>',
   'plus'=>'<path d="M12 5v14M5 12h14"/>',
   'logout'=>'<path d="M10 17l5-5-5-5m5 5H3m9-9h7a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-7"/>',
   'menu'=>'<path d="M3 6h18M3 12h18M3 18h18"/>',
   'arrow'=>'<path d="m9 18 6-6-6-6"/>',
   'box'=>'<path d="m3 7 9-5 9 5v10l-9 5-9-5zM3 7l9 5 9-5M12 12v10"/>',
   'calendar'=>'<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M7 2v6m10-6v6M3 10h18"/>',
   'alert'=>'<path d="m12 3 10 18H2zM12 9v5m0 4h.01"/>',
   'layers'=>'<path d="m12 2 10 6-10 6L2 8zM2 12l10 6 10-6M2 16l10 6 10-6"/>',
   'link'=>'<path d="M10 13a5 5 0 0 0 7 .5l3-3a5 5 0 0 0-7-7l-2 2M14 11a5 5 0 0 0-7-.5l-3 3a5 5 0 0 0 7 7l2-2"/>',
   'database'=>'<ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5v14c0 4 18 4 18 0V5M3 12c0 4 18 4 18 0"/>',
   'check'=>'<path d="m4 12 5 5L20 6"/>',
   'external'=>'<path d="M14 3h7v7M10 14 21 3M20 14v6H4V4h6"/>'
  ];
  return '<svg width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.($paths[$name]??$paths['box']).'</svg>';
}
function navlink(string $p, string $label, string $icon, string $active): string {
 $selected=$p===$active?' is-active':'';
 return '<a class="nav-link'.$selected.'" href="index.php?page='.e($p).'">'.icon($icon).'<span>'.e($label).'</span>'.icon('arrow',14).'</a>';
}
function layout_start(string $title, string $subtitle, string $page): void {
 $flash=take_flash(); $name=setting('workshop_name','Hydroaktif Manager'); $user=current_user();
 ?><!doctype html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title><?=e($title)?> · Hydroaktif Manager</title><link rel="stylesheet" href="assets/css/app.css"><script src="assets/js/app.js" defer></script></head>
 <body class="app-body"><div class="app-shell">
 <aside class="sidebar" id="sidebar"><a class="brand" href="index.php"><span class="brand-mark">H</span><span class="brand-copy">HYDROAKTIF<small>MANAGER</small></span></a>
 <div class="sidebar-scroll"><div class="sidebar-section"><div class="nav-label">ESPACE DE TRAVAIL</div><?=navlink('dashboard','Tableau de bord','home',$page)?></div>
 <div class="sidebar-section"><div class="nav-label">GESTION DE L’ATELIER</div><?=navlink('filaments','Impression 3D','printer',$page)?><?=navlink('components','Électronique','cpu',$page)?><?=navlink('projects','Projets','projects',$page)?><?=navlink('storage','Rangements','storage',$page)?><?=navlink('equipment','Équipements','tool',$page)?></div>
 <div class="sidebar-section"><div class="nav-label">CONFIGURATION</div><?=navlink('settings','Administration','settings',$page)?></div></div>
 <div class="sidebar-bottom"><div class="version-pill"><span class="live-dot"></span> VERSION 0.1 · SOCLE INITIAL</div><div class="sidebar-user"><div class="avatar"><?=e(mb_strtoupper(mb_substr($user['display_name'],0,1)))?></div><span><?=e($user['display_name'])?><small><?=e($user['role'])?></small></span><form method="post" action="index.php"><?=csrf_input()?><input type="hidden" name="action" value="logout"><button type="submit" title="Se déconnecter" class="icon-button icon-light"><?=icon('logout')?></button></form></div></div>
 </aside><div class="sidebar-backdrop" id="sidebar-backdrop"></div><div class="main-layout"><header class="topbar"><button class="icon-button mobile-menu" id="menu-toggle" type="button" aria-label="Ouvrir le menu"><?=icon('menu',22)?></button><div class="top-crumb">HYDROAKTIF <span>/</span> <?=e(mb_strtoupper($title))?></div><div class="top-right"><span class="online-status"><span class="live-dot"></span> Atelier en ligne</span><div class="avatar avatar-small"><?=e(mb_strtoupper(mb_substr($user['display_name'],0,1)))?></div></div></header>
 <main class="page"><div class="page-head"><div><div class="eyebrow">HYDROAKTIF MANAGER</div><h1><?=e($title)?></h1><p class="muted"><?=e($subtitle)?></p></div><div class="head-date"><?=e((new DateTimeImmutable('now'))->format('d/m/Y'))?></div></div>
 <?php if($flash): ?><div class="alert alert-<?=e($flash['kind'])?>" role="status"><?=e($flash['text'])?></div><?php endif ?>
 <?php
}
function layout_end(): void { ?>
 </main><footer class="footer">Hydroaktif Manager <span>·</span> Gestion d’atelier auto-hébergée <span>·</span> PHP / MySQL</footer></div></div></body></html>
 <?php }
function placeholder(string $heading, string $description): void { ?>
 <div class="empty-state"><span class="empty-icon"><?=icon('layers',32)?></span><h3><?=e($heading)?></h3><p><?=e($description)?></p></div>
<?php }
