<?php
require_once __DIR__ . '/Database.php';

class LicenseManager {
    private $db;
    private $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function generate($data) {
        $product = $data['product_name'] ?? 'MyTool';
        $type = strtoupper($data['license_type'] ?? 'BASIC');
        $maxAct = (int)($data['max_activations'] ?? 1);
        $qty = min((int)($data['quantity'] ?? 1), 50);
        $email = $data['customer_email'] ?? null;
        
        $prefix = ['TRIAL'=>'TR','BASIC'=>'BS','PRO'=>'PR','ENTERPRISE'=>'EN','LIFETIME'=>'LT'];
        $days = ['TRIAL'=>30,'BASIC'=>365,'PRO'=>365,'ENTERPRISE'=>730,'LIFETIME'=>null];
        $exp = isset($days[$type]) && $days[$type] ? date('Y-m-d H:i:s', strtotime("+{$days[$type]} days")) : null;
        
        $keys = [];
        for ($i = 0; $i < $qty; $i++) {
            $key = ($prefix[$type] ?? 'XX') . '-';
            for ($s = 0; $s < 4; $s++) {
                for ($c = 0; $c < 4; $c++) $key .= $this->chars[random_int(0, 31)];
                if ($s < 3) $key .= '-';
            }
            $key .= '-' . $this->checksum($key);
            
            $this->db->query(
                "INSERT INTO licenses (license_key, product_name, license_type, max_activations, expires_at, customer_email) VALUES ($1,$2,$3,$4,$5,$6)",
                [$key, $product, $type, $maxAct, $exp, $email]
            );
            $keys[] = ['key'=>$key, 'type'=>$type, 'expires'=>$exp];
        }
        return ['success'=>true, 'count'=>count($keys), 'licenses'=>$keys];
    }
    
    public function validate($key) {
        $lic = $this->db->fetch("SELECT * FROM licenses WHERE license_key=$1", [$key]);
        if (!$lic) return ['valid'=>false, 'message'=>'License tidak ditemukan'];
        if ($lic['status']==='revoked') return ['valid'=>false, 'message'=>'License dicabut'];
        if ($lic['expires_at'] && strtotime($lic['expires_at']) < time()) return ['valid'=>false, 'message'=>'License expired'];
        return ['valid'=>true, 'license_type'=>$lic['license_type'], 'product'=>$lic['product_name'], 'expires'=>$lic['expires_at'], 'left'=>$lic['max_activations']-$lic['current_activations']];
    }
    
    public function activate($key, $hwid) {
        $v = $this->validate($key);
        if (!$v['valid']) return $v;
        $lic = $this->db->fetch("SELECT * FROM licenses WHERE license_key=$1", [$key]);
        if ($this->db->fetch("SELECT id FROM activations WHERE license_id=$1 AND hardware_id=$2", [$lic['id'], $hwid])) return ['success'=>true, 'message'=>'Sudah aktif'];
        if ($lic['current_activations'] >= $lic['max_activations']) return ['success'=>false, 'message'=>'Batas aktivasi penuh'];
        $this->db->query("INSERT INTO activations (license_id, hardware_id, ip_address) VALUES ($1,$2,$3)", [$lic['id'], $hwid, $_SERVER['REMOTE_ADDR']??'']);
        $this->db->query("UPDATE licenses SET current_activations=current_activations+1 WHERE id=$1", [$lic['id']]);
        return ['success'=>true, 'message'=>'Aktivasi berhasil', 'type'=>$lic['license_type']];
    }
    
    public function deactivate($key, $hwid) {
        $lic = $this->db->fetch("SELECT id FROM licenses WHERE license_key=$1", [$key]);
        if (!$lic) return ['success'=>false];
        $this->db->query("DELETE FROM activations WHERE license_id=$1 AND hardware_id=$2", [$lic['id'], $hwid]);
        $this->db->query("UPDATE licenses SET current_activations=GREATEST(current_activations-1,0) WHERE id=$1", [$lic['id']]);
        return ['success'=>true];
    }
    
    public function revoke($key) {
        $this->db->query("UPDATE licenses SET status='revoked' WHERE license_key=$1", [$key]);
        return ['success'=>true];
    }
    
    public function getAll() { return $this->db->fetchAll("SELECT * FROM licenses ORDER BY created_at DESC LIMIT 100"); }
    
    public function getStats() {
        return [
            'total'=>$this->db->fetch("SELECT COUNT(*) as c FROM licenses")['c'],
            'active'=>$this->db->fetch("SELECT COUNT(*) as c FROM licenses WHERE status='active'")['c'],
            'used'=>$this->db->fetch("SELECT COUNT(*) as c FROM licenses WHERE current_activations>0")['c']
        ];
    }
    
    private function checksum($k) {
        $s=0; foreach(str_split(str_replace('-','',$k)) as $c) $s+=ord($c);
        $r=''; for($i=0;$i<4;$i++) $r.=$this->chars[($s*($i+7))%32];
        return $r;
    }
}
