<?php
/* [AI:GPT-5.6 Sol | 2026-08-29 02:30:00 UTC] */
class module_package_builder
{
 private const SELF='module_builder'; private const LIMIT=1048576;
 private const TEXT=['php','json','md','txt','css','js','html','xml','sql','svg','yml','yaml'];
 private string $modules; private string $releases;
 public function __construct(?string $modules=null,?string $releases=null){$this->modules=$modules??USERROOT.'/modules';$this->releases=$releases??dirname(USERROOT).'/releases';$this->mkdir($this->modules);$this->mkdir($this->releases);}
 public function isValidSlug(string $s):bool{return(bool)preg_match('/^[a-z][a-z0-9_]{1,62}$/',$s);}
 public function listProjects():array{$out=[];foreach(glob($this->modules.'/*',GLOB_ONLYDIR)?:[]as$d){$s=basename($d);if($s===self::SELF||!$this->isValidSlug($s)||is_link($d))continue;$m=$this->json($d.'/module.json',false);$out[]=['slug'=>$s,'name'=>(string)($m['name']??$s),'version'=>(string)($m['version']??''),'description'=>(string)($m['description']??'')];}usort($out,fn($a,$b)=>strcmp($a['slug'],$b['slug']));return$out;}
 public function createProject(array $in):void
 {
  $s=strtolower(trim((string)($in['slug']??'')));$n=trim((string)($in['name']??''));$v=trim((string)($in['version']??''));$this->meta($s,$n,$v);if($s===self::SELF)throw new InvalidArgumentException('Reserved slug.');
  $r=$this->modules.'/'.$s;if(file_exists($r))throw new RuntimeException('Project exists.');foreach(['/controllers','/models','/views/admin']as$d)$this->mkdir($r.$d);
  $files=['controllers/'.$s.'.php','models/'.$s.'_model.php','views/index.php','views/admin/index.php'];
  try{$this->write($r.'/module.json',$this->encode(['module'=>$s,'name'=>$n,'version'=>$v,'description'=>trim((string)($in['description']??'')),'routes'=>['index'],'files'=>$files]));$this->write($r.'/controllers/'.$s.'.php',$this->controller($s));$this->write($r.'/models/'.$s.'_model.php',$this->model($s));$this->write($r.'/views/index.php',$this->publicView($n));$this->write($r.'/views/admin/index.php',$this->adminView($n));}catch(Throwable$e){$this->remove($r);throw$e;}
 }
 public function editProject(string$s,array$in):void{$r=$this->root($s);$m=$this->json($r.'/module.json');$n=trim((string)($in['name']??''));$v=trim((string)($in['version']??''));$this->meta($s,$n,$v);$m['module']=$s;$m['name']=$n;$m['version']=$v;$m['description']=trim((string)($in['description']??''));$this->write($r.'/module.json',$this->encode($m));}
 public function deleteProject(string$s):void{$this->remove($this->root($s));}
 public function fileTree(string$s):array{$r=$this->root($s);$o=[];$it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($r,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::SELF_FIRST);foreach($it as$i){if($i->isLink())continue;$o[]=['path'=>str_replace('\\','/',substr($i->getPathname(),strlen($r)+1)),'directory'=>$i->isDir()];}usort($o,fn($a,$b)=>strcmp($a['path'],$b['path']));return$o;}
 public function readFile(string$s,string$p):string{$f=$this->existing($s,$p,true);$this->editable($f);$c=file_get_contents($f);if(!is_string($c))throw new RuntimeException('Read failed.');return$c;}
 public function writeFile(string$s,string$p,string$c):void{if(strlen($c)>self::LIMIT)throw new RuntimeException('Editor limit exceeded.');$f=$this->existing($s,$p,true);$this->editable($f);$this->write($f,$c);}
 public function createFile(string$s,string$p):void{$f=$this->fresh($s,$p);$this->editable($f);if(file_exists($f)||!touch($f))throw new RuntimeException('Create failed.');}
 public function createDirectory(string$s,string$p):void{$d=$this->fresh($s,$p);if(file_exists($d)||!mkdir($d,0755))throw new RuntimeException('Create failed.');}
 public function renamePath(string$s,string$p,string$n):void{$a=$this->existing($s,$p,false);$b=$this->fresh($s,$n);if(file_exists($b)||!rename($a,$b))throw new RuntimeException('Rename failed.');}
 public function deletePath(string$s,string$p):void{$f=$this->existing($s,$p,false);if(is_dir($f))$this->remove($f);elseif(!unlink($f))throw new RuntimeException('Delete failed.');}
 public function validateProject(string$s):array
 {
  $r=$this->root($s);$m=$this->json($r.'/module.json',false);$e=[];$c=$r.'/controllers/'.$s.'.php';$v=$r.'/views/index.php';
  if(($m['module']??null)!==$s)$e[]='module metadata must match slug.';foreach(['name','version','routes','files']as$f)if(!array_key_exists($f,$m))$e[]='Missing metadata: '.$f;
  if(!is_file($c))$e[]='Required controller missing.';else{$x=(string)file_get_contents($c);if(!preg_match('/function\s+index\s*\(/',$x))$e[]='Controller index() missing.';if(!preg_match('/function\s+admin\s*\(/',$x))$e[]='Controller admin() missing.';}
  if(!is_file($v))$e[]='views/index.php missing.';else{$x=(string)file_get_contents($v);foreach(["APPROOT . '/views/inc/head.php'","APPROOT . '/views/inc/foot.php'"]as$w)if(!str_contains($x,$w))$e[]='Public wrapper missing: '.$w;}
  if(!is_array($m['routes']??null)||!in_array('index',$m['routes'],true))$e[]='routes[] must include index.';if(!is_array($m['files']??null)||!in_array('views/index.php',$m['files'],true))$e[]='files must include views/index.php.';return['valid'=>$e===[],'errors'=>$e];
 }
 public function buildRelease(string$s):string
 {
  if(!$this->validateProject($s)['valid'])throw new RuntimeException('Validation failed.');if(!class_exists('ZipArchive'))throw new RuntimeException('ZIP extension required.');
  $r=$this->root($s);$m=$this->json($r.'/module.json');$base=$s.'-'.$m['version'];$out=$this->artifactRoot($s,true);$zipPath=$out.'/'.$base.'.zip';$tmp=$zipPath.'.tmp-'.bin2hex(random_bytes(5));$z=new ZipArchive();if($z->open($tmp,ZipArchive::CREATE|ZipArchive::OVERWRITE)!==true)throw new RuntimeException('ZIP create failed.');
  try{foreach($this->fileTree($s)as$entry){if(!$entry['directory']){$f=$this->existing($s,$entry['path'],true);if(!$z->addFile($f,$s.'/'.$entry['path']))throw new RuntimeException('ZIP add failed.');}}}finally{$z->close();}
  if(!rename($tmp,$zipPath))throw new RuntimeException('ZIP finalize failed.');$hash=hash_file('sha256',$zipPath);$this->write($out.'/'.$base.'.sha256',$hash.'  '.basename($zipPath).PHP_EOL);$this->write($out.'/'.$base.'.manifest.json',$this->encode(['module'=>$s,'version'=>$m['version'],'artifact'=>basename($zipPath),'sha256'=>$hash,'signed'=>false,'built_at'=>gmdate('c')]));return$zipPath;
 }
 public function listArtifacts(string$s):array{$r=$this->artifactRoot($s,false);if(!is_dir($r))return[];$o=[];foreach(scandir($r)?:[]as$n){$p=$r.'/'.$n;if($n==='.'||$n==='..'||!is_file($p)||is_link($p))continue;$o[]=['name'=>$n,'size'=>filesize($p),'modified'=>filemtime($p)];}usort($o,fn($a,$b)=>$b['modified']<=>$a['modified']);return$o;}
 public function certificationStatus():array
 {
  $id=$this->json(USERROOT.'/data/certified_developer.json',false);$url=trim((string)(getenv('CHAOS_CERTIFICATION_ENDPOINT')?:''));$s=['certified'=>false,'signing'=>false,'message'=>'Full development and unsigned packaging available; signing is not configured.'];if($url===''||$id===[])return$s;if(!$this->https($url)){$s['message']='Certification endpoint must be public HTTPS.';return$s;}
  $q=http_build_query(['developer_id'=>(string)($id['developer_id']??''),'domain'=>(string)($id['domain']??($_SERVER['HTTP_HOST']??'')),'key_id'=>(string)($id['key_id']??''),'capability'=>'module_signing']);$raw=@file_get_contents($url.'?'.$q,false,stream_context_create(['http'=>['timeout'=>5,'ignore_errors'=>true]]));$r=is_string($raw)?json_decode($raw,true):null;if(!is_array($r)){$s['message']='Certification status unavailable; unsigned development remains available.';return$s;}$s['certified']=($r['certified']??false)===true;$s['signing']=$s['certified']&&($r['signing']['module']??false)===true;$s['key_id']=(string)($r['key_id']??'');$s['message']=$s['signing']?'Certified signing authorized; private keys are never retained.':'Unsigned development remains fully available; signing not authorized.';return$s;
 }
 public function signRelease(string$s,string$a,array$u):void
 {
  if(!$this->certificationStatus()['signing'])throw new RuntimeException('Signing not authorized.');if(!preg_match('/^[a-z][a-z0-9_]{1,62}-[0-9A-Za-z.+_-]+\.zip$/',$a))throw new InvalidArgumentException('Invalid artifact.');if(($u['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK||!is_uploaded_file((string)($u['tmp_name']??'')))throw new RuntimeException('ChAoS MVC-issued key required.');
  $p=$this->artifactRoot($s,false).'/'.$a;if(!is_file($p)||is_link($p))throw new RuntimeException('Artifact missing.');$pem=file_get_contents((string)$u['tmp_name']);$key=is_string($pem)?openssl_pkey_get_private($pem):false;$pem=null;if($key===false)throw new RuntimeException('Invalid key.');$sig='';if(!openssl_sign(hash_file('sha256',$p),$sig,$key,OPENSSL_ALGO_SHA256))throw new RuntimeException('Signing failed.');$this->write($p.'.sig',base64_encode($sig).PHP_EOL);
 }
 private function root(string$s):string{if(!$this->isValidSlug($s)||$s===self::SELF)throw new InvalidArgumentException('Invalid project.');$base=realpath($this->modules);$r=is_link($this->modules.'/'.$s)?false:realpath($this->modules.'/'.$s);if($base===false||$r===false||!str_starts_with($r,$base.DIRECTORY_SEPARATOR))throw new RuntimeException('Project outside module root.');return$r;}
 private function path(string$p):string{$p=trim(str_replace('\\','/',$p),'/');if($p===''||strlen($p)>240||str_contains($p,'..')||str_contains($p,"\0")||!preg_match('#^[A-Za-z0-9._/-]+$#',$p))throw new InvalidArgumentException('Invalid relative path.');return$p;}
 private function existing(string$s,string$p,bool$file):string{$r=$this->root($s);$x=$r.DIRECTORY_SEPARATOR.str_replace('/',DIRECTORY_SEPARATOR,$this->path($p));$y=is_link($x)?false:realpath($x);if($y===false||!str_starts_with($y,$r.DIRECTORY_SEPARATOR)||($file&&!is_file($y)))throw new RuntimeException('Path outside project or missing.');return$y;}
 private function fresh(string$s,string$p):string{$r=$this->root($s);$x=$r.DIRECTORY_SEPARATOR.str_replace('/',DIRECTORY_SEPARATOR,$this->path($p));$parent=is_link(dirname($x))?false:realpath(dirname($x));if($parent===false||!str_starts_with($parent,$r.DIRECTORY_SEPARATOR))throw new RuntimeException('Destination outside project.');return$x;}
 private function editable(string$p):void{if(!in_array(strtolower(pathinfo($p,PATHINFO_EXTENSION)),self::TEXT,true))throw new InvalidArgumentException('Unsupported editor file type.');if(is_file($p)&&filesize($p)>self::LIMIT)throw new RuntimeException('Editor limit exceeded.');}
 private function meta(string$s,string$n,string$v):void{if(!$this->isValidSlug($s))throw new InvalidArgumentException('Invalid lowercase slug.');if($n===''||strlen($n)>100)throw new InvalidArgumentException('Name required.');if(!preg_match('/^[0-9]+\.[0-9]+\.[0-9]+(?:[-+][0-9A-Za-z.-]+)?$/',$v))throw new InvalidArgumentException('Semantic version required.');}
 private function artifactRoot(string$s,bool$create):string{$this->root($s);$r=$this->releases.'/'.$s;if($create)$this->mkdir($r);return$r;}
 private function json(string$p,bool$required=true):array{if(!is_file($p)||is_link($p)){if($required)throw new RuntimeException('JSON missing.');return[];}$x=file_get_contents($p);$d=is_string($x)?json_decode($x,true):null;if(!is_array($d)){if($required)throw new RuntimeException('JSON invalid.');return[];}return$d;}
 private function encode(array$d):string{$x=json_encode($d,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);if(!is_string($x))throw new RuntimeException('JSON encode failed.');return$x.PHP_EOL;}
 private function write(string$p,string$c):void{$t=$p.'.tmp-'.bin2hex(random_bytes(5));if(file_put_contents($t,$c,LOCK_EX)===false||!rename($t,$p)){@unlink($t);throw new RuntimeException('Write failed.');}}
 private function mkdir(string$p):void{if(!is_dir($p)&&!mkdir($p,0755,true))throw new RuntimeException('Directory create failed.');}
 private function remove(string$r):void{if(is_link($r)){if(!unlink($r))throw new RuntimeException('Delete failed.');return;}if(!is_dir($r)){if(is_file($r)&&!unlink($r))throw new RuntimeException('Delete failed.');return;}$it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($r,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST);foreach($it as$i){$p=$i->getPathname();if(!(($i->isLink()||$i->isFile())?unlink($p):rmdir($p)))throw new RuntimeException('Delete failed.');}if(!rmdir($r))throw new RuntimeException('Delete failed.');}
 private function https(string$u):bool{$p=parse_url($u);if(!is_array($p)||strtolower((string)($p['scheme']??''))!=='https'||empty($p['host'])||isset($p['user'])||isset($p['pass']))return false;$h=strtolower((string)$p['host']);if($h==='localhost'||str_ends_with($h,'.local'))return false;$ip=filter_var($h,FILTER_VALIDATE_IP);return$ip===false||filter_var($ip,FILTER_VALIDATE_IP,FILTER_FLAG_NO_PRIV_RANGE|FILTER_FLAG_NO_RES_RANGE)!==false;}
 private function controller(string$s):string{return"<?php\n/* [AI:GPT-5.6 Sol | ".gmdate('Y-m-d H:i:s')." UTC] */\nclass {$s} extends controller\n{\n public function index(array \$params=[]):void{\$this->view('index',['params'=>\$params]);}\n public function admin(array \$params=[]):void{\$this->require_admin(7);\$this->view('admin/index',['params'=>\$params]);}\n}\n/* [End AI:GPT-5.6 Sol] */\n";}
 private function model(string$s):string{return"<?php\n/* [AI:GPT-5.6 Sol | ".gmdate('Y-m-d H:i:s')." UTC] */\nclass {$s}_model extends model{}\n/* [End AI:GPT-5.6 Sol] */\n";}
 private function publicView(string$n):string{$n=htmlspecialchars($n,ENT_QUOTES,'UTF-8');return"<?php require APPROOT . '/views/inc/head.php'; ?>\n<?php /* [AI:GPT-5.6 Sol | ".gmdate('Y-m-d H:i:s')." UTC] */ ?>\n<main class=\"container py-5\"><h1>{$n}</h1></main>\n<?php /* [End AI:GPT-5.6 Sol] */ ?>\n<?php require APPROOT . '/views/inc/foot.php'; ?>\n";}
 private function adminView(string$n):string{$n=htmlspecialchars($n,ENT_QUOTES,'UTF-8');return"<?php /* [AI:GPT-5.6 Sol | ".gmdate('Y-m-d H:i:s')." UTC] */ ?>\n<section class=\"container-fluid py-4\"><h1>{$n}</h1><p>Module administration.</p></section>\n<?php /* [End AI:GPT-5.6 Sol] */ ?>\n";}
}
/* [End AI:GPT-5.6 Sol] */
