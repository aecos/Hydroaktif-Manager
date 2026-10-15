<?php
function required_field(string $name, int $max=190): string {
    $x=val($name,$max);
    if ($x==='') throw new InvalidArgumentException('Le champ « '.$name.' » est obligatoire.');
    return $x;
}
function optional_id(string $name): ?int {
    $s=(string)($_POST[$name]??'');
    if ($s==='') return null;
    if (!ctype_digit($s) || (int)$s<1) throw new InvalidArgumentException('Identifiant invalide.');
    return (int)$s;
}
function positive_id(string $name): int {
    $v=optional_id($name);
    if (!$v) throw new InvalidArgumentException('Sélectionne une valeur pour '.$name.'.');
    return $v;
}
function optional_money(string $name): ?float {
    return trim((string)($_POST[$name]??''))==='' ? null : numeric($name,10000000);
}
function enum_value(string $name, array $allowed): string {
    $s=val($name,40);
    if (!in_array($s,$allowed,true)) throw new InvalidArgumentException('Valeur non autorisée pour '.$name.'.');
    return $s;
}
function apply_component_movement(PDO $pdo, int $cid, int $lid, int $delta, string $note): void {
    if ($delta===0) throw new InvalidArgumentException('La variation du stock ne peut pas être nulle.');
    $exists=$pdo->prepare('SELECT id FROM components WHERE id=?');$exists->execute([$cid]);
    if (!$exists->fetchColumn()) throw new InvalidArgumentException('Composant introuvable.');
    $exists=$pdo->prepare('SELECT id FROM storage_locations WHERE id=?');$exists->execute([$lid]);
    if (!$exists->fetchColumn()) throw new InvalidArgumentException('Emplacement introuvable.');
    $pdo->prepare('INSERT IGNORE INTO component_stocks (component_id,location_id,quantity) VALUES (?,?,0)')->execute([$cid,$lid]);
    $q=$pdo->prepare('SELECT id,quantity FROM component_stocks WHERE component_id=? AND location_id=? FOR UPDATE');$q->execute([$cid,$lid]);
    $stock=$q->fetch();
    $new=(int)$stock['quantity']+$delta;
    if ($new<0) throw new InvalidArgumentException('Stock insuffisant à cet emplacement.');
    if ($new>4294967295) throw new InvalidArgumentException('Stock trop élevé.');
    $pdo->prepare('UPDATE component_stocks SET quantity=? WHERE id=?')->execute([$new,$stock['id']]);
    $pdo->prepare('INSERT INTO component_movements (component_id,location_id,delta_quantity,note,actor_id) VALUES (?,?,?,?,?)')->execute([$cid,$lid,$delta,$note,(int)current_user()['id']]);
}
function handle_action(string $action): void {
    require_editor();
    $pdo=db();
    switch($action) {
        case 'component_add':
            $name=required_field('name'); $mpn=val('mpn'); $manufacturer=val('manufacturer');
            $category=val('category'); $desc=val('description',2000);
            $datasheet=val('datasheet_url',1500);
            if($datasheet!=='' && !filter_var($datasheet,FILTER_VALIDATE_URL)) throw new InvalidArgumentException('URL datasheet invalide.');
            $quantityRaw=numeric('quantity',10000000);
            if(floor($quantityRaw)!==$quantityRaw) throw new InvalidArgumentException('La quantité initiale doit être entière.');
            $quantity=(int)$quantityRaw;
            $location=optional_id('location_id');
            if($quantity>0 && !$location) throw new InvalidArgumentException('Sélectionne un emplacement pour le stock initial.');
            $pdo->beginTransaction();
            try {
                $pdo->prepare('INSERT INTO components (name,mpn,manufacturer,category,description,datasheet_url) VALUES (?,?,?,?,?,?)')->execute([$name,$mpn?:null,$manufacturer?:null,$category?:null,$desc?:null,$datasheet?:null]);
                if($quantity>0) apply_component_movement($pdo,(int)$pdo->lastInsertId(),$location,$quantity,'Stock initial');
                $pdo->commit();
            } catch(Throwable $e){$pdo->rollBack();throw $e;}
            flash('Composant ajouté à l’inventaire.');return;
        case 'component_move':
            $id=positive_id('component_id'); $lid=positive_id('location_id');
            $direction=enum_value('direction',['in','out']);
            $qty=numeric('quantity',10000000);
            if($qty<1 || floor($qty)!==$qty) throw new InvalidArgumentException('Entre une quantité entière positive.');
            $delta=(int)$qty*($direction==='out'?-1:1);
            $pdo->beginTransaction();
            try { apply_component_movement($pdo,$id,$lid,$delta,val('note',255));$pdo->commit(); }
            catch(Throwable $e){$pdo->rollBack();throw $e;}
            flash('Mouvement de stock enregistré.');return;
        case 'filament_product_add':
            $brand=required_field('brand',120); $line=required_field('product_line',160);
            $material=required_field('material',40); $color=required_field('color_name',120);
            $hex=strtoupper(val('color_hex',7));
            if(!preg_match('/^#[0-9A-F]{6}$/',$hex)) throw new InvalidArgumentException('Code couleur hexadécimal invalide.');
            $diam=numeric('diameter_mm',10);if($diam<=0) throw new InvalidArgumentException('Diamètre invalide.');
            $pdo->prepare('INSERT INTO filament_products (brand,product_line,material,color_name,color_hex,diameter_mm) VALUES (?,?,?,?,?,?)')->execute([$brand,$line,$material,$color,$hex,$diam]);
            flash('Référence de filament créée. Tu peux maintenant ajouter une bobine.');return;
        case 'spool_add':
            $product=positive_id('product_id'); $code=required_field('code',100);
            $initial=numeric('initial_weight_g',100000);$remaining=numeric('remaining_weight_g',100000);
            $tare=numeric('tare_weight_g',100000);
            if($initial<=0||$remaining>$initial)throw new InvalidArgumentException('Poids initial et restant incohérents.');
            $location=optional_id('location_id'); $price=optional_money('purchase_price');
            $status=enum_value('status',['sealed','open','empty','archived']);
            if($status==='empty' && $remaining>0) throw new InvalidArgumentException('Une bobine vide doit avoir 0 g restant.');
            $pdo->prepare('INSERT INTO filament_spools (product_id,code,initial_weight_g,remaining_weight_g,tare_weight_g,purchase_price,status,location_id) VALUES (?,?,?,?,?,?,?,?)')->execute([$product,$code,$initial,$remaining,$tare,$price,$status,$location]);
            flash('Bobine ajoutée.');return;
        case 'spool_adjust':
            $id=positive_id('spool_id');$new=numeric('remaining_weight_g',100000);
            $pdo->beginTransaction();
            try {
                $q=$pdo->prepare('SELECT initial_weight_g,remaining_weight_g FROM filament_spools WHERE id=? FOR UPDATE');$q->execute([$id]);$row=$q->fetch();
                if(!$row)throw new InvalidArgumentException('Bobine introuvable.');
                if($new>(float)$row['initial_weight_g'])throw new InvalidArgumentException('Le poids restant dépasse le poids initial.');
                $status=$new==0?'empty':'open';
                $pdo->prepare('UPDATE filament_spools SET remaining_weight_g=?,status=? WHERE id=?')->execute([$new,$status,$id]);
                $pdo->prepare('INSERT INTO filament_movements (spool_id,previous_weight_g,new_weight_g,note,actor_id) VALUES (?,?,?,?,?)')->execute([$id,$row['remaining_weight_g'],$new,val('note',255),(int)current_user()['id']]);
                $pdo->commit();
            }catch(Throwable $e){$pdo->rollBack();throw $e;}
            flash('Poids restant mis à jour.');return;
        case 'project_add':
            $title=required_field('title');$desc=val('description',4000);$budget=optional_money('budget');
            $pdo->prepare('INSERT INTO projects (title,description,budget) VALUES (?,?,?)')->execute([$title,$desc?:null,$budget]);
            flash('Projet créé.');return;
        case 'project_status':
            $id=positive_id('project_id'); $status=enum_value('status',['planned','in_progress','on_hold','done']);
            $pdo->prepare('UPDATE projects SET status=? WHERE id=?')->execute([$status,$id]);
            flash('Statut du projet mis à jour.');return;
        case 'location_add':
            $code=required_field('code',100);$label=required_field('label');
            $kind=enum_value('kind',['zone','shelf','cabinet','drawer','box','compartment']);
            $parent=optional_id('parent_id');
            $width=optional_money('width_mm');$height=optional_money('height_mm');$depth=optional_money('depth_mm');
            if($parent){$q=$pdo->prepare('SELECT id FROM storage_locations WHERE id=?');$q->execute([$parent]);if(!$q->fetch())throw new InvalidArgumentException('Parent introuvable.');}
            $pdo->prepare('INSERT INTO storage_locations (code,label,kind,parent_id,width_mm,height_mm,depth_mm) VALUES (?,?,?,?,?,?,?)')->execute([$code,$label,$kind,$parent,$width,$height,$depth]);
            flash('Emplacement créé.');return;
        case 'equipment_add':
            $name=required_field('name');$category=val('category',100);$maker=val('manufacturer',120);$model=val('model',160);
            $location=optional_id('location_id');
            $pdo->prepare('INSERT INTO equipment (name,category,manufacturer,model,location_id) VALUES (?,?,?,?,?)')->execute([$name,$category?:null,$maker?:null,$model?:null,$location]);
            flash('Équipement enregistré.');return;
        case 'settings_update':
            if((current_user()['role']??'')!=='admin')throw new InvalidArgumentException('Administrateur requis.');
            $name=required_field('workshop_name');
            $pdo->prepare('INSERT INTO settings (`key`,value) VALUES (?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)')->execute(['workshop_name',$name]);
            flash('Paramètres enregistrés.');return;
        default: throw new InvalidArgumentException('Action inconnue.');
    }
}
