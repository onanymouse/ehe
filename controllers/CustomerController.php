<?php
require_once 'helpers/routeros_api.class.php';

class CustomerController {
    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    public function index() {
        $role = $_SESSION['role'];
        $my_id = $_SESSION['user_id'];
        $where_role = ($role == 'kolektor') ? " AND collector_id = '$my_id'" : "";
        $stats = $this->conn->query("SELECT COUNT(*) as total, SUM(CASE WHEN status='active' THEN 1 ELSE 0 END) as active, SUM(CASE WHEN status='nonactive' THEN 1 ELSE 0 END) as nonactive, SUM(CASE WHEN status='isolated' THEN 1 ELSE 0 END) as isolated, SUM(CASE WHEN MONTH(created_at)=MONTH(CURRENT_DATE()) AND YEAR(created_at)=YEAR(CURRENT_DATE()) THEN 1 ELSE 0 END) as new_this_month FROM customers WHERE 1=1 $where_role")->fetch();
        $areas = $this->conn->query("SELECT * FROM areas ORDER BY name ASC")->fetchAll();
        $collectors = $this->conn->query("SELECT * FROM users WHERE role='kolektor' ORDER BY fullname ASC")->fetchAll();
        require_once 'views/customer/index.php';
    }

    // --- API DATATABLES SERVER SIDE ---
    public function get_data_ajax() {
        ini_set('display_errors', 0); error_reporting(0); while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        try {
            $role = $_SESSION['role']; $my_id = $_SESSION['user_id'];
            $draw = isset($_GET['draw']) ? intval($_GET['draw']) : 1;
            $start = isset($_GET['start']) ? intval($_GET['start']) : 0;
            $length = isset($_GET['length']) ? intval($_GET['length']) : 10;
            $search = isset($_GET['search']['value']) ? $_GET['search']['value'] : '';
            $f_area = isset($_GET['area']) ? clean($_GET['area']) : '';
            $f_coll = isset($_GET['collector']) ? clean($_GET['collector']) : '';
            $f_stat = isset($_GET['status']) ? clean($_GET['status']) : '';

            $sql_base = " FROM customers c LEFT JOIN packages p ON c.package_id=p.id LEFT JOIN routers r ON c.router_id=r.id LEFT JOIN areas a ON c.area_id=a.id LEFT JOIN users u ON c.collector_id=u.id WHERE 1=1 ";
            $params = [];

            if($role == 'kolektor') { $sql_base .= " AND c.collector_id = ? "; $params[] = $my_id; }
            if(!empty($f_area)) { $sql_base .= " AND c.area_id = ? "; $params[] = $f_area; }
            if(!empty($f_stat)) { $sql_base .= " AND c.status = ? "; $params[] = $f_stat; }
            if(!empty($f_coll) && $role == 'admin') { $sql_base .= " AND c.collector_id = ? "; $params[] = $f_coll; }
            if(!empty($search)) {
                $sql_base .= " AND (c.name LIKE ? OR c.customer_code LIKE ? OR c.pppoe_user LIKE ? OR c.phone LIKE ?) ";
                $p = "%$search%"; array_push($params, $p, $p, $p, $p);
            }

            $stmt_c = $this->conn->prepare("SELECT COUNT(c.id) " . $sql_base); $stmt_c->execute($params);
            $recordsTotal = $stmt_c->fetchColumn(); $recordsFiltered = $recordsTotal;

            $cols = [0=>'c.id', 1=>'c.name', 2=>'c.pppoe_user', 3=>'p.price', 4=>'c.due_date', 5=>'c.id'];
            $orderBy = isset($cols[$_GET['order'][0]['column'] ?? 1]) ? $cols[$_GET['order'][0]['column'] ?? 1] : 'c.name';
            $ordDir = $_GET['order'][0]['dir'] ?? 'asc';

            $sql = "SELECT c.*, p.name as package_name, p.price, r.name as router_name, a.name as area_name, u.fullname as collector_name " . $sql_base . " ORDER BY $orderBy $ordDir LIMIT $start, $length";
            $stmt = $this->conn->prepare($sql); $stmt->execute($params); $data = $stmt->fetchAll();

            $formatted = []; $no = $start + 1; $bulan_ini = date('Y-m');
            foreach($data as $row) {
                $is_paid = false;
                if($row['status']=='active' || $row['status']=='isolated') {
                    $cek = $this->conn->prepare("SELECT id FROM invoices WHERE customer_id=? AND period_month=? AND status='paid' LIMIT 1");
                    $cek->execute([$row['id'], $bulan_ini]); $is_paid = ($cek->rowCount() > 0);
                }
                $row_cls = ($row['status']=='isolated') ? "bg-danger-light" : (($row['status']=='nonactive') ? "bg-secondary-light text-muted" : "");
                
                $col_pel = "<span class='text-bold ".($row['status']=='nonactive'?'text-secondary':'text-primary')."' style='font-size:1.1em'>".htmlspecialchars($row['name'])."</span><br><small class='text-muted'><i class='fas fa-id-card'></i> {$row['customer_code']}<br><i class='fab fa-whatsapp'></i> {$row['phone']}</small>";
                $col_con = $row['is_mikrotik'] ? "<div style='font-size:0.9em'><i class='fas fa-user-circle text-info'></i> <b>{$row['pppoe_user']}</b><br><i class='fas fa-server text-secondary'></i> {$row['router_name']}</div>" : "<span class='badge badge-secondary'>Manual</span>";
                $col_pak = "<b>{$row['package_name']}</b><br><span class='text-success font-weight-bold'>Rp ".number_format($row['price'],0,',','.')."</span>";
                
                $lunas_bdg = $is_paid ? "<span class='badge badge-success mb-1'>LUNAS BULAN INI</span>" : "<span class='badge badge-warning mb-1'>Belum Bayar</span>";
                $bdg_stat = ($row['status']=='active') ? "<span class='badge badge-primary'>ACTIVE</span>" : (($row['status']=='isolated') ? "<span class='badge badge-danger'>TERISOLIR</span>" : "<span class='badge badge-secondary'>NON-AKTIF</span>");
                $col_inf = "<small>Jatuh Tempo: <b>Tgl {$row['due_date']}</b></small><br>$lunas_bdg<div class='mt-1'>$bdg_stat</div>";

                $btns = "<div class='btn-group'><button type='button' class='btn btn-sm btn-default' onclick='viewDetail({$row['id']})' title='Detail'><i class='fas fa-eye'></i></button>";
                if(($role=='admin'||$role=='teknisi') && $row['is_mikrotik']) {
                    $btns .= "<button type='button' class='btn btn-sm btn-info' onclick='checkConnection({$row['id']}, \"".htmlspecialchars($row['name'])."\")'><i class='fas fa-stethoscope'></i></button><button type='button' class='btn btn-sm btn-dark' onclick='startTraffic({$row['id']}, \"{$row['pppoe_user']}\")'><i class='fas fa-chart-area'></i></button>";
                }
                if($role=='admin') {
                    $btns .= "<a href='index.php?page=customer&action=edit&id={$row['id']}' class='btn btn-sm btn-warning'><i class='fas fa-edit'></i></a><a href='index.php?page=customer&action=delete&id={$row['id']}' class='btn btn-sm btn-danger' onclick='return confirm(\"Hapus?\")'><i class='fas fa-trash'></i></a>";
                }
                $btns .= "</div>";
                $formatted[] = ["DT_RowClass"=>$row_cls, "0"=>$no++, "1"=>$col_pel, "2"=>$col_con, "3"=>$col_pak, "4"=>$col_inf, "5"=>$btns];
            }
            echo json_encode(["draw"=>$draw, "recordsTotal"=>$recordsFiltered, "recordsFiltered"=>$recordsFiltered, "data"=>$formatted]);
        } catch (Exception $e) { echo json_encode(["error"=>$e->getMessage()]); } exit;
    }

    private function checkAdminAccess() { if($_SESSION['role']!='admin') { setFlash('error','Akses Ditolak'); redirect('index.php?page=customer'); exit; } }

    // --- FITUR BARU: AJAX GET ONU LIST ---
        // --- FITUR BARU: AJAX GET ONU LIST (OPTIMIZED) ---
    public function get_onu_list() {
        // 1. PENTING: Lepas Session Lock agar web tidak macet
        session_write_close();
        
        // 2. Matikan error text & bersihkan buffer
        ini_set('display_errors', 0); 
        error_reporting(0);
        while (ob_get_level()) { ob_end_clean(); }
        
        header('Content-Type: application/json');

        try {
            $olt_id = isset($_GET['olt_id']) ? $_GET['olt_id'] : 0;
            
            // 3. Ambil data seperlunya saja (Interface, SN, State) agar ringan
            // Pastikan Anda sudah menjalankan SQL Index di Langkah 1
            $sql = "SELECT interface, sn, state FROM olt_onus WHERE olt_id = ? ORDER BY interface ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$olt_id]);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode($data);

        } catch (Exception $e) {
            echo json_encode([]); // Return kosong jika error
        }
        exit;
    }


    // --- CRUD CREATE ---
    public function create() {
        $this->checkAdminAccess();
        $packages=$this->conn->query("SELECT * FROM packages")->fetchAll();
        $routers=$this->conn->query("SELECT * FROM routers")->fetchAll();
        $olts=$this->conn->query("SELECT * FROM olts ORDER BY name ASC")->fetchAll(); // Load OLT
        $areas=$this->conn->query("SELECT * FROM areas")->fetchAll();
        $collectors=$this->conn->query("SELECT * FROM users WHERE role='kolektor'")->fetchAll();
        $last=$this->conn->query("SELECT id FROM customers ORDER BY id DESC LIMIT 1")->fetch();
        $next_id=($last)?$last['id']+1:1; $customer_code="CUST-".sprintf("%04s",$next_id);
        require_once 'views/customer/create.php';
    }

    // --- CRUD STORE (Update simpan OLT & Interface) ---
    public function store() {
        $this->checkAdminAccess();
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $code=clean($_POST['customer_code']); $name=clean($_POST['name']); $phone=clean($_POST['phone']); $addr=clean($_POST['address']); $area=clean($_POST['area_id']); $pkg=clean($_POST['package_id']); $coll=clean($_POST['collector_id']); $due=clean($_POST['due_date']); 
            $is_mik=isset($_POST['is_mikrotik'])?1:0; $auto=isset($_POST['auto_isolir'])?1:0; 
            $router=clean($_POST['router_id']); $mode=isset($_POST['mikrotik_mode'])?$_POST['mikrotik_mode']:'create_new';
            $user=($mode=='create_new')?clean($_POST['pppoe_user_new']):clean($_POST['pppoe_user_existing']); $pass=($mode=='create_new')?$_POST['pppoe_password_new']:"existing_secret"; $prof=isset($_POST['mikrotik_profile_selected'])?$_POST['mikrotik_profile_selected']:'';
            
            // OLT Data
            $olt_id = !empty($_POST['olt_id']) ? clean($_POST['olt_id']) : NULL;
            $onu_iface = !empty($_POST['onu_interface']) ? clean($_POST['onu_interface']) : NULL;

            $this->conn->beginTransaction();
            try {
                if ($is_mik && !empty($router) && $mode=='create_new') {
                    if(empty($user)) throw new Exception("User PPPoE wajib!");
                    $rd=$this->conn->prepare("SELECT * FROM routers WHERE id=?"); $rd->execute([$router]); $r=$rd->fetch();
                    $API=new RouterosAPI(); $API->debug=false; $API->port=$r['port'];
                    if ($API->connect($r['ip_address'], $r['username'], $r['password'])) {
                        if(count($API->comm("/ppp/secret/print",["?name"=>$user]))>0) { $API->disconnect(); throw new Exception("User sudah ada!"); }
                        $API->comm("/ppp/secret/add",["name"=>$user,"password"=>$pass,"service"=>"pppoe","profile"=>$prof,"comment"=>"$name - $code"]); $API->disconnect();
                    } else { throw new Exception("Gagal konek Mikrotik."); }
                }
                // Simpan ke DB dengan kolom OLT
                $sql="INSERT INTO customers (customer_code,name,phone,address,area_id,package_id,collector_id,router_id,olt_id,onu_interface,is_mikrotik,pppoe_user,pppoe_password,due_date,auto_isolir,status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?, 'active')";
                $this->conn->prepare($sql)->execute([$code,$name,$phone,$addr,$area,$pkg,$coll,$router,$olt_id,$onu_iface,$is_mik,$user,$pass,$due,$auto]);
                $this->conn->commit(); setFlash('success','Disimpan'); redirect('index.php?page=customer');
            } catch (Exception $e) { $this->conn->rollBack(); setFlash('error',$e->getMessage()); echo "<script>window.history.back();</script>"; }
        }
    }

    // --- CRUD EDIT ---
    public function edit() {
        $this->checkAdminAccess(); $id=$_GET['id']; $c=$this->conn->prepare("SELECT * FROM customers WHERE id=?"); $c->execute([$id]); $customer=$c->fetch(); if(!$customer) redirect('index.php');
        $packages=$this->conn->query("SELECT * FROM packages")->fetchAll(); $routers=$this->conn->query("SELECT * FROM routers")->fetchAll(); 
        $olts=$this->conn->query("SELECT * FROM olts ORDER BY name ASC")->fetchAll(); // Load OLT
        $areas=$this->conn->query("SELECT * FROM areas")->fetchAll(); $collectors=$this->conn->query("SELECT * FROM users WHERE role='kolektor'")->fetchAll(); 
        require_once 'views/customer/edit.php';
    }

    // --- CRUD UPDATE ---
    public function update() {
        $this->checkAdminAccess();
        if ($_SERVER['REQUEST_METHOD']=='POST') {
            $id=$_POST['id']; $name=clean($_POST['name']); $phone=clean($_POST['phone']); $addr=clean($_POST['address']); $area=clean($_POST['area_id']); $pkg=clean($_POST['package_id']); $coll=clean($_POST['collector_id']); $due=clean($_POST['due_date']); $auto=isset($_POST['auto_isolir'])?1:0; $stat=clean($_POST['status']); $is_mik=isset($_POST['is_mikrotik'])?1:0; $router=clean($_POST['router_id']); $mode=isset($_POST['mikrotik_mode'])?$_POST['mikrotik_mode']:'connect_existing';
            $user=($is_mik)?(($mode=='create_new')?clean($_POST['pppoe_user_new']):clean($_POST['pppoe_user_existing'])):null;
            if(empty($user)&&isset($_POST['pppoe_user'])) $user=clean($_POST['pppoe_user']);
            $pass=$_POST['pppoe_password_new']; $code=clean($_POST['customer_code_hidden']);
            
            // OLT Data
            $olt_id = !empty($_POST['olt_id']) ? clean($_POST['olt_id']) : NULL;
            $onu_iface = !empty($_POST['onu_interface']) ? clean($_POST['onu_interface']) : NULL;

            $this->conn->beginTransaction();
            try {
                if ($is_mik && !empty($router) && $mode=='create_new') {
                    if(empty($user)) throw new Exception("User PPPoE wajib!");
                    $rd=$this->conn->prepare("SELECT * FROM routers WHERE id=?"); $rd->execute([$router]); $r=$rd->fetch();
                    $API=new RouterosAPI(); $API->debug=false; $API->port=$r['port'];
                    if ($API->connect($r['ip_address'], $r['username'], $r['password'])) {
                        if(count($API->comm("/ppp/secret/print",["?name"=>$user]))>0) { $API->disconnect(); throw new Exception("User sudah ada!"); }
                        $prof=isset($_POST['mikrotik_profile_selected'])?$_POST['mikrotik_profile_selected']:'default';
                        $API->comm("/ppp/secret/add",["name"=>$user,"password"=>$pass,"service"=>"pppoe","profile"=>$prof,"comment"=>"$name - $code"]); $API->disconnect();
                    } else { throw new Exception("Gagal konek Mikrotik."); }
                }
                $sql_pass=", pppoe_password=?"; $p_pass=[$pass]; if($mode=="connect_existing"){$sql_pass="";$p_pass=[];}
                // Update OLT ID & Interface
                $sql="UPDATE customers SET name=?, phone=?, address=?, area_id=?, package_id=?, collector_id=?, due_date=?, auto_isolir=?, status=?, is_mikrotik=?, router_id=?, olt_id=?, onu_interface=?, pppoe_user=? $sql_pass WHERE id=?";
                $params=[$name,$phone,$addr,$area,$pkg,$coll,$due,$auto,$stat,$is_mik,$router,$olt_id,$onu_iface,$user]; if(!empty($p_pass)) $params=array_merge($params,$p_pass); $params[]=$id;
                $this->conn->prepare($sql)->execute($params); $this->conn->commit(); setFlash('success','Update Berhasil'); redirect('index.php?page=customer');
            } catch (Exception $e) { $this->conn->rollBack(); setFlash('error',$e->getMessage()); echo "<script>window.history.back();</script>"; }
        }
    }

    public function delete() { $this->checkAdminAccess(); $id=$_GET['id']; try{$this->conn->prepare("DELETE FROM customers WHERE id=?")->execute([$id]); setFlash('success','Dihapus');}catch(Exception $e){} redirect('index.php?page=customer'); }

    // --- AJAX HELPERS (Detail, Check, Traffic) - SAMA SEPERTI SEBELUMNYA ---
    public function detail() { ini_set('display_errors',0); if(ob_get_length()) ob_clean(); header('Content-Type:application/json'); $id=$_GET['id']; $role=$_SESSION['role']; $my=$_SESSION['user_id']; $sql="SELECT c.*,p.name as package_name,p.price,r.name as router_name,r.ip_address as router_ip,a.name as area_name,u.fullname as collector_name FROM customers c LEFT JOIN packages p ON c.package_id=p.id LEFT JOIN routers r ON c.router_id=r.id LEFT JOIN areas a ON c.area_id=a.id LEFT JOIN users u ON c.collector_id=u.id WHERE c.id=?"; if($role=='kolektor') $sql.=" AND c.collector_id='$my'"; $d=$this->conn->prepare($sql); $d->execute([$id]); $row=$d->fetch(); if($row){ if($role!='admin')$row['pppoe_password']='******'; echo json_encode(['status'=>'success','data'=>$row]); } else echo json_encode(['status'=>'error','message'=>'Not Found']); exit; }
    public function check_status() { session_write_close(); ini_set('display_errors',0); if(ob_get_length()) ob_clean(); header('Content-Type:application/json'); $id=$_GET['id']; $rd=$this->conn->prepare("SELECT c.pppoe_user,c.is_mikrotik,r.* FROM customers c JOIN routers r ON c.router_id=r.id WHERE c.id=?"); $rd->execute([$id]); $data=$rd->fetch(); if(!$data||$data['is_mikrotik']==0){ echo json_encode(['status'=>'error','message'=>'Non-Mikrotik']); exit; } $API=new RouterosAPI(); $API->debug=false; $API->port=$data['port']; $API->timeout=2; if($API->connect($data['ip_address'],$data['username'],$data['password'])){ $act=$API->comm("/ppp/active/print",["?name"=>$data['pppoe_user']]); $sec=$API->comm("/ppp/secret/print",["?name"=>$data['pppoe_user']]); $API->disconnect(); $res=['status'=>'success','is_online'=>false,'is_disabled'=>false,'data_online'=>null]; if(isset($sec[0]['disabled'])&&$sec[0]['disabled']=='true') $res['is_disabled']=true; if(count($act)>0) { $res['is_online']=true; $res['data_online']=['ip'=>$act[0]['address']??'-','uptime'=>$act[0]['uptime']??'-','mac'=>$act[0]['caller-id']??'-']; } echo json_encode($res); } else echo json_encode(['status'=>'error','message'=>'Router Timeout']); exit; }
    public function traffic_api() { session_write_close(); ini_set('display_errors',0); if(ob_get_length()) ob_clean(); header('Content-Type:application/json'); $id=isset($_GET['id'])?$_GET['id']:0; $rd=$this->conn->prepare("SELECT c.pppoe_user,r.* FROM customers c JOIN routers r ON c.router_id=r.id WHERE c.id=?"); $rd->execute([$id]); $data=$rd->fetch(); if(!$data){ echo json_encode(['status'=>'error']); exit; } $API=new RouterosAPI(); $API->debug=false; $API->port=$data['port']; $API->timeout=2; if($API->connect($data['ip_address'],$data['username'],$data['password'])){ $u=$data['pppoe_user']; $iface="<pppoe-$u>"; $chk=$API->comm("/interface/print",["?name"=>$iface]); if(!count($chk)){ $iface=$u; $chk=$API->comm("/interface/print",["?name"=>$iface]); } if(count($chk)) { $tr=$API->comm("/interface/monitor-traffic",["interface"=>$iface,"once"=>""]); if(isset($tr[0]['rx-bits-per-second'])) echo json_encode(['status'=>'success','online'=>true,'rx_fmt'=>$this->fmt($tr[0]['rx-bits-per-second']),'tx_fmt'=>$this->fmt($tr[0]['tx-bits-per-second']),'rx_raw'=>$tr[0]['rx-bits-per-second'],'tx_raw'=>$tr[0]['tx-bits-per-second']]); else echo json_encode(['status'=>'success','online'=>false]); } else echo json_encode(['status'=>'success','online'=>false]); $API->disconnect(); } else echo json_encode(['status'=>'error','message'=>'Disconnect']); exit; }
    private function fmt($b){ if($b<1000)return $b." b"; if($b<1000000)return round($b/1000,1)." Kb"; return round($b/1000000,1)." Mb"; }
}
?>
