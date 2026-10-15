<?php
declare(strict_types=1);
require __DIR__.'/app/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: private, no-store');
if(!installed()) { http_response_code(503);echo json_encode(['error'=>'Installation requise'],JSON_UNESCAPED_UNICODE);exit; }
if(!current_user()) { http_response_code(401);echo json_encode(['error'=>'Authentification requise'],JSON_UNESCAPED_UNICODE);exit; }
$resource=(string)($_GET['resource']??'');
try {
  switch($resource) {
    case 'summary':
      $data=[
        'components'=>count_query('SELECT COUNT(*) FROM components'),
        'spools'=>count_query('SELECT COUNT(*) FROM filament_spools WHERE status<>"archived"'),
        'projects'=>count_query('SELECT COUNT(*) FROM projects'),
        'locations'=>count_query('SELECT COUNT(*) FROM storage_locations'),
      ];break;
    case 'locations':
      $data=db()->query('SELECT id,parent_id,code,label,kind,width_mm,height_mm,depth_mm,pos_x_mm,pos_y_mm FROM storage_locations ORDER BY id LIMIT 1000')->fetchAll();break;
    case 'components':
      $data=db()->query('SELECT id,name,mpn,manufacturer,category,datasheet_url FROM components ORDER BY id DESC LIMIT 200')->fetchAll();break;
    case 'spools':
      $data=db()->query('SELECT s.id,s.code,s.initial_weight_g,s.remaining_weight_g,s.status,p.brand,p.material,p.color_name,p.color_hex FROM filament_spools s JOIN filament_products p ON p.id=s.product_id ORDER BY s.id DESC LIMIT 200')->fetchAll();break;
    default:
      http_response_code(404);
      echo json_encode(['error'=>'Ressource inconnue'],JSON_UNESCAPED_UNICODE);exit;
  }
  echo json_encode(['data'=>$data], JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE|JSON_THROW_ON_ERROR);
} catch(Throwable $e) {
  error_log('Hydroaktif API failure: '.get_class($e));
  http_response_code(500);
  echo json_encode(['error'=>'Erreur serveur'],JSON_UNESCAPED_UNICODE);
}
