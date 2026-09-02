<?php
/* [AI:GPT-5.6 Sol | 2026-08-29 02:30:00 UTC] */
class module_package_builder
{
 private const SELF='module_builder'; private const LIMIT=1048576;
 private const TEXT=['php','json','md','txt','css','js','html','xml','sql','svg','yml','yaml'];
 private string $modules; private string $releases;
 public function __construct(?string $modules=null,?string $releases=null){$this->modules=$modules??USERROOT.'/modules';$this->releases=$releases??dirname(USERROOT).'/releases';$this->mkdir($this->modules);$this->mkdir($this->releases);}
 public function isValidSlug(string $s):bool{return(bool)preg_match('/^[a-z][a-z0-9_]{1,62}$/',$s);}
 public function listProjects():array{$out=[];foreach(glob($this->modules.'/*',GLOB_ONLYDIR)?:[]as$d){$s=basename($d);if($s===self::SELF||!$this->isValidSlug($s)||is_link($d))continue;$m=$this->json($d.'/module.json',false);$out[]=['slug'=>$s,'name'=>(string)($m['name']??$s),'version'=>(string)($m['version']??''),'description'=>(string)($m['description']??''),'update_url'=>(string)($m['update_url']??''),'creator'=>(string)($m['creator']??''),'domain'=>(string)($m['domain']??''),'certified'=>(string)($m['certified']??'No'),'signing'=>is_array($m['signing']??null)?$m['signing']:[]];}usort($out,fn($a,$b)=>strcmp($a['slug'],$b['slug']));return$out;}
 public function createProject(array $in):void
 {
<<<<<<< HEAD
  $s=strtolower(trim((string)($in['slug']??'')));$n=trim((string)($in['name']??''));$v=trim((string)($in['version']??''));$in['certified']='No';$in['signing_sha256']=hash('sha256',random_bytes(32));$in['signing_key_id']='';$in['signing_public_key']='';$metadata=$this->projectMetadata($s,$n,$v,$in);if($s===self::SELF)throw new InvalidArgumentException('Reserved slug.');
  $usesDatabase=isset($in['uses_database']);$tableInput=trim((string)($in['database_tables']??''));$tables=$usesDatabase?$this->databaseTables($s,$tableInput===''?$s:$tableInput):[];
  $r=$this->modules.'/'.$s;if(file_exists($r))throw new RuntimeException('Project exists.');$directories=['/controllers','/views/admin','/docs'];if($usesDatabase)$directories=array_merge($directories,['/models','/sql','/sql/patches']);foreach($directories as$d)$this->mkdir($r.$d);
  $files=['controllers/'.$s.'.php','views/admin/'.$s.'.php','views/index.php','docs/CHANGELOG.md'];if($usesDatabase)$files=array_merge(['controllers/'.$s.'.php','models/'.$s.'_model.php','views/admin/'.$s.'.php','views/index.php','sql/schema.sql','docs/CHANGELOG.md']);if($usesDatabase)$metadata['database_tables']=$tables;$metadata['files']=$files;$metadata['routes']=['index'];
  try{$this->write($r.'/module.json',$this->encode($metadata));$this->write($r.'/controllers/'.$s.'.php',$this->controller($s,$usesDatabase));if($usesDatabase){$this->write($r.'/models/'.$s.'_model.php',$this->model($s,$tables,$v));$this->write($r.'/sql/schema.sql',$this->schema($tables,$v));}$this->write($r.'/views/index.php',$this->publicView($n));$this->write($r.'/views/admin/'.$s.'.php',$this->adminView($n,$s,$usesDatabase));$this->write($r.'/docs/CHANGELOG.md',$this->changelog($v));}catch(Throwable$e){$this->remove($r);throw$e;}
=======
  $s=strtolower(trim((string)($in['slug']??'')));$n=trim((string)($in['name']??''));$v=trim((string)($in['version']??''));$in['certified']='No';$in['signing_sha256']='';$in['signing_key_id']='';$in['signing_public_key']='';$metadata=$this->projectMetadata($s,$n,$v,$in);if($s===self::SELF)throw new InvalidArgumentException('Reserved slug.');
  $r=$this->modules.'/'.$s;if(file_exists($r))throw new RuntimeException('Project exists.');foreach(['/controllers','/models','/views/admin','/docs']as$d)$this->mkdir($r.$d);
  $files=['controllers/'.$s.'.php','models/'.$s.'_model.php','views/admin/'.$s.'.php','views/index.php','docs/CHANGELOG.md'];$metadata['files']=$files;$metadata['routes']=['index'];
  try{$this->write($r.'/module.json',$this->encode($metadata));$this->write($r.'/controllers/'.$s.'.php',$this->controller($s));$this->write($r.'/models/'.$s.'_model.php',$this->model($s));$this->write($r.'/views/index.php',$this->publicView($n));$this->write($r.'/views/admin/'.$s.'.php',$this->adminView($n));$this->write($r.'/docs/CHANGELOG.md',$this->changelog($v));}catch(Throwable$e){$this->remove($r);throw$e;}
>>>>>>> 7dc5abd9ee720cad8c2b41799f07e245319daed0
 }
 public function editProject(string$s,array$in):void{$r=$this->root($s);$m=$this->json($r.'/module.json');$n=trim((string)($in['name']??''));$v=trim((string)($in['version']??''));$updated=$this->projectMetadata($s,$n,$v,$in);if(array_key_exists('database_tables',$m))$updated['database_tables']=$m['database_tables'];$updated['files']=$m['files']??['controllers/'.$s.'.php','views/admin/'.$s.'.php','views/index.php','docs/CHANGELOG.md'];$updated['routes']=$m['routes']??['index'];$this->write($r.'/module.json',$this->encode($updated));}
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
  $r=$this->root($s);$m=$this->json($r.'/module.json',false);$e=[];$c=$r.'/controllers/'.$s.'.php';$v=$r.'/views/index.php';$admin='views/admin/'.$s.'.php';
  if(($m['module']??null)!==$s)$e[]='module metadata must match slug.';
  foreach(['name','module','version','description','update_url','creator','domain','certified','signing','files','routes']as$f)if(!array_key_exists($f,$m))$e[]='Missing metadata: '.$f;
  $usesDatabase=array_key_exists('database_tables',$m);
  if(isset($m['update_url'])&&(filter_var($m['update_url'],FILTER_VALIDATE_URL)===false||parse_url($m['update_url'],PHP_URL_SCHEME)!=='https'))$e[]='update_url must be valid HTTPS.';
  if(isset($m['certified'])&&!in_array($m['certified'],['Yes','No'],true))$e[]='certified must be Yes or No.';
  $signing=$m['signing']??null;if(!is_array($signing))$e[]='signing metadata must be an object.';else{
   foreach(['type','fingerprint','sha256','key_id','public_key']as$f)if(!array_key_exists($f,$signing))$e[]='Missing signing metadata: '.$f;
   $type=(string)($signing['type']??'');$fingerprint=(string)($signing['fingerprint']??'');$sha=(string)($signing['sha256']??'');$keyId=(string)($signing['key_id']??'');$publicKey=(string)($signing['public_key']??'');
   if(!in_array($type,['sha256','rsa-sha256','openpgp'],true))$e[]='signing.type must be sha256, rsa-sha256, or openpgp.';
   if(strlen($fingerprint)>255)$e[]='signing.fingerprint must not exceed 255 characters.';
   if(!preg_match('/^[a-f0-9]{64}$/',$sha))$e[]='signing.sha256 is required and must be lowercase SHA-256.';
   if($keyId!==''&&!preg_match('/^[a-z0-9][a-z0-9_-]{2,63}$/',$keyId))$e[]='signing.key_id is invalid.';
   if($type==='rsa-sha256'&&$publicKey!==''){$pem=base64_decode($publicKey,true);if($pem===false||!str_contains($pem,'-----BEGIN PUBLIC KEY-----'))$e[]='RSA signing.public_key must be base64 public PEM.';}
   if($type==='openpgp'&&$publicKey!==''&&base64_decode($publicKey,true)===false)$e[]='OpenPGP signing.public_key must be compact base64 data.';
   if(($keyId==='')!==($publicKey===''))$e[]='signing.key_id and signing.public_key must be supplied together.';
   if(($m['certified']??'No')==='Yes'&&($sha===''||$keyId===''||$publicKey===''))$e[]='Certified modules require complete signing metadata.';
  }
  if(!is_file($c))$e[]='Required controller missing.';else{$x=(string)file_get_contents($c);if(!preg_match('/function\s+index\s*\(/',$x))$e[]='Controller index() missing.';if(!preg_match('/function\s+admin\s*\(/',$x))$e[]='Controller admin() missing.';if($usesDatabase)foreach(['install_sql','update_sql','delete_data','require_csrf']as$required)if(!str_contains($x,$required))$e[]='Controller lifecycle missing: '.$required.'.';}
  if(!is_file($v))$e[]='views/index.php missing.';else{$x=(string)file_get_contents($v);foreach(["APPROOT . '/views/inc/head.php'","APPROOT . '/views/inc/foot.php'"]as$w)if(!str_contains($x,$w))$e[]='Public wrapper missing: '.$w;}
  if(!is_file($r.'/'.$admin))$e[]=$admin.' missing.';
  if(!is_array($m['routes']??null)||!in_array('index',$m['routes'],true))$e[]='routes[] must include index.';
  foreach(['controllers/'.$s.'.php',$admin,'views/index.php','docs/CHANGELOG.md']as$f)if(!is_array($m['files']??null)||!in_array($f,$m['files'],true))$e[]='files must include '.$f.'.';
  if($usesDatabase){$tables=$m['database_tables'];if(!is_array($tables)||$tables===[])$e[]='database_tables must declare at least one owned table.';else foreach($tables as$table)if(!is_string($table)||($table!==$s&&!str_starts_with($table,$s.'_'))||!preg_match('/^[a-z][a-z0-9_]{1,62}$/',$table))$e[]='Invalid module-owned database table: '.(string)$table.'.';foreach(['models/'.$s.'_model.php','sql/schema.sql']as$f)if(!is_array($m['files']??null)||!in_array($f,$m['files'],true))$e[]='files must include '.$f.'.';if(!is_file($r.'/sql/schema.sql'))$e[]='sql/schema.sql missing.';if(!is_dir($r.'/sql/patches'))$e[]='sql/patches directory missing.';}
  if(is_file($r.'/'.$admin)&&!str_contains((string)file_get_contents($r.'/'.$admin),'/admin/uninstall'))$e[]='Admin Core Nuke control missing.';
  if(!is_file($r.'/docs/CHANGELOG.md'))$e[]='docs/CHANGELOG.md missing.';
  return['valid'=>$e===[],'errors'=>$e];
 }
 public function buildRelease(string$s):string
 {
  if(!$this->validateProject($s)['valid'])throw new RuntimeException('Validation failed.');if(!class_exists('ZipArchive'))throw new RuntimeException('ZIP extension required.');
  $r=$this->root($s);$m=$this->json($r.'/module.json');$base=$s.'-'.$m['version'];$out=$this->artifactRoot($s,true);$zipPath=$out.'/'.$base.'.zip';$tmp=$zipPath.'.tmp-'.bin2hex(random_bytes(5));$z=new ZipArchive();if($z->open($tmp,ZipArchive::CREATE|ZipArchive::OVERWRITE)!==true)throw new RuntimeException('ZIP create failed.');
  try{foreach($this->fileTree($s)as$entry){if(!$entry['directory']){$f=$this->existing($s,$entry['path'],true);if(!$z->addFile($f,$s.'/'.$entry['path']))throw new RuntimeException('ZIP add failed.');}}}finally{$z->close();}
  if(!rename($tmp,$zipPath))throw new RuntimeException('ZIP finalize failed.');$hash=hash_file('sha256',$zipPath);$this->write($out.'/'.$base.'.sha256',$hash.'  '.basename($zipPath).PHP_EOL);$this->write($out.'/'.$base.'.manifest.json',$this->encode(['module'=>$s,'version'=>$m['version'],'artifact'=>basename($zipPath),'sha256'=>$hash,'signed'=>false,'built_at'=>gmdate('c')]));return$zipPath;
 }
 public function listArtifacts(string$s):array{$r=$this->artifactRoot($s,false);if(!is_dir($r))return[];$o=[];foreach(scandir($r)?:[]as$n){$p=$r.'/'.$n;if($n==='.'||$n==='..'||!is_file($p)||is_link($p))continue;$o[]=['name'=>$n,'size'=>filesize($p),'modified'=>filemtime($p)];}usort($o,fn($a,$b)=>$b['modified']<=>$a['modified']);return$o;}
 public function artifactFile(string$s,string$n):string
 {
  if($n!==basename($n)||!preg_match('/^[A-Za-z0-9._-]{1,240}$/',$n))throw new InvalidArgumentException('Invalid artifact.');
  $root=$this->artifactRoot($s,false);$resolvedRoot=is_link($root)?false:realpath($root);$candidate=$root.'/'.$n;$resolved=is_link($candidate)?false:realpath($candidate);
  if($resolvedRoot===false||$resolved===false||!str_starts_with($resolved,$resolvedRoot.DIRECTORY_SEPARATOR)||!is_file($resolved))throw new RuntimeException('Artifact was not found.');
  return$resolved;
 }
 public function certificationStatus():array
 {
  $id=$this->json(USERROOT.'/data/certified_developer.json',false);$url=trim((string)(getenv('CHAOS_CERTIFICATION_ENDPOINT')?:''));$s=['certified'=>false,'signing'=>false,'message'=>'Full development and unsigned packaging available; signing is not configured.'];if($url===''||$id===[])return$s;if(!$this->https($url)){$s['message']='Certification endpoint must be public HTTPS.';return$s;}
  $q=http_build_query(['developer_id'=>(string)($id['developer_id']??''),'domain'=>(string)($id['domain']??($_SERVER['HTTP_HOST']??'')),'key_id'=>(string)($id['key_id']??''),'capability'=>'module_signing']);$raw=@file_get_contents($url.'?'.$q,false,stream_context_create(['http'=>['timeout'=>5,'ignore_errors'=>true]]));$r=is_string($raw)?json_decode($raw,true):null;if(!is_array($r)){$s['message']='Certification status unavailable; unsigned development remains available.';return$s;}$s['certified']=($r['certified']??false)===true;$s['signing']=$s['certified']&&($r['signing']['module']??false)===true;$s['key_id']=(string)($r['key_id']??'');$s['message']=$s['signing']?'Certified signing authorized; private keys are never retained.':'Unsigned development remains fully available; signing not authorized.';return$s;
 }
 /**
  * Generate an encrypted 3072-bit RSA keypair for SHA-256 signatures.
  *
  * The returned PEM material exists only in memory and must be downloaded by
  * the caller. Module Builder never writes generated keys to disk.
  */
 public function generateSigningKeypair(string $passphrase,string $keyPrefix='developer'):array
 {
  if(!extension_loaded('openssl'))throw new RuntimeException('The PHP OpenSSL extension is required.');
  $keyPrefix=strtolower(trim($keyPrefix));
  if(!preg_match('/^[a-z0-9][a-z0-9_-]{1,31}$/',$keyPrefix))throw new InvalidArgumentException('Use a lowercase key ID prefix with letters, numbers, underscores, or hyphens.');
  if(strlen($passphrase)<12)throw new InvalidArgumentException('Use a key passphrase of at least 12 characters.');
  if(strlen($passphrase)>1024)throw new InvalidArgumentException('The key passphrase is too long.');

  $key=openssl_pkey_new([
   'config'=>dirname(__DIR__).'/config/openssl.cnf',
   'private_key_type'=>OPENSSL_KEYTYPE_RSA,
   'private_key_bits'=>3072,
   'digest_alg'=>'sha256',
  ]);
  if($key===false)throw new RuntimeException('OpenSSL could not generate the RSA keypair.');

  $private='';
  if(!openssl_pkey_export($key,$private,$passphrase,[
   'config'=>dirname(__DIR__).'/config/openssl.cnf',
   'digest_alg'=>'sha256',
  ]))
   throw new RuntimeException('OpenSSL could not export the encrypted private key.');

  $details=openssl_pkey_get_details($key);
  if(!is_array($details)||!isset($details['key']))
   throw new RuntimeException('OpenSSL could not export the public key.');

  $public=(string)$details['key'];
  $fingerprint=hash('sha256',$public);
  $keyId=$keyPrefix.'-'.substr($fingerprint,0,16);

  return[
   'format'=>'chaos-rsa-signing-keypair',
   'version'=>1,
   'algorithm'=>'RSA-SHA256',
   'type'=>'rsa-sha256',
   'rsa_bits'=>(int)($details['bits']??3072),
   'fingerprint_sha256'=>$fingerprint,
   'sha256'=>$fingerprint,
   'key_id'=>$keyId,
   'public_key'=>base64_encode($public),
   'created_at'=>gmdate('c'),
   'private_key_encrypted'=>true,
   'private_key_pem'=>$private,
   'public_key_pem'=>$public,
   'instructions'=>'Store this download securely. Module Builder has not retained a copy.',
  ];
 }

 /**
  * Build a standards-compliant ZIP in memory without writing key material.
  */
 public function buildSigningKeypairZip(array $pair):string
 {
  $private=$pair['private_key_pem']??null;
  $public=$pair['public_key_pem']??null;
  if(!is_string($private)||!str_contains($private,'BEGIN ENCRYPTED PRIVATE KEY'))
   throw new InvalidArgumentException('Encrypted private-key PEM is missing.');
  if(!is_string($public)||!str_contains($public,'BEGIN PUBLIC KEY'))
   throw new InvalidArgumentException('Public-key PEM is missing.');

  $metadata=[
   'format'=>(string)($pair['format']??'chaos-rsa-signing-keypair'),
   'version'=>(int)($pair['version']??1),
   'algorithm'=>(string)($pair['algorithm']??'RSA-SHA256'),
   'type'=>(string)($pair['type']??'rsa-sha256'),
   'rsa_bits'=>(int)($pair['rsa_bits']??3072),
   'fingerprint_sha256'=>(string)($pair['fingerprint_sha256']??''),
   'sha256'=>(string)($pair['sha256']??''),
   'key_id'=>(string)($pair['key_id']??''),
   'public_key'=>(string)($pair['public_key']??''),
   'created_at'=>(string)($pair['created_at']??gmdate('c')),
   'private_key_encrypted'=>true,
   'private_key_file'=>'private-key.pem',
   'public_key_file'=>'public-key.pem',
   'instructions'=>'Store private-key.pem securely. Module Builder has not retained a copy.',
  ];
  $json=json_encode($metadata,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
  if(!is_string($json))throw new RuntimeException('Key metadata could not be encoded.');

  $zip=$this->storedZip([
   'private-key.pem'=>$private,
   'public-key.pem'=>$public,
   'key-metadata.json'=>$json.PHP_EOL,
  ]);

  $private=null;
  $public=null;
  $metadata=[];
  $json=null;
  return$zip;
 }

 /**
  * Create a small uncompressed ZIP entirely in memory.
  */
 private function storedZip(array $files):string
 {
  $local='';
  $central='';
  $offset=0;
  $now=getdate();
  $year=max(1980,(int)$now['year']);
  $dosTime=((int)$now['hours']<<11)|((int)$now['minutes']<<5)|(int)floor((int)$now['seconds']/2);
  $dosDate=(($year-1980)<<9)|((int)$now['mon']<<5)|(int)$now['mday'];

  foreach($files as$name=>$data){
   if(!is_string($name)||!preg_match('/^[a-z0-9._-]+$/',$name)||!is_string($data))
    throw new InvalidArgumentException('Invalid in-memory ZIP entry.');
   $size=strlen($data);
   $crc=(int)hexdec(hash('crc32b',$data));
   $nameLength=strlen($name);
   $header=pack(
    'VvvvvvVVVvv',
    0x04034b50,20,0x0800,0,$dosTime,$dosDate,$crc,$size,$size,$nameLength,0
   );
   $local.=$header.$name.$data;
   $central.=pack(
    'VvvvvvvVVVvvvvvVV',
    0x02014b50,20,20,0x0800,0,$dosTime,$dosDate,$crc,$size,$size,
    $nameLength,0,0,0,0,0,$offset
   ).$name;
   $offset+=strlen($header)+$nameLength+$size;
  }

  $count=count($files);
  return$local.$central.pack(
   'VvvvvVVv',
   0x06054b50,0,0,$count,$count,strlen($central),strlen($local),0
  );
 }

 /**
  * Build and sign a release using a one-request uploaded encrypted PEM.
  */
 public function buildAndSignRelease(string$s,array$upload,string$passphrase):string
 {
  if(($upload['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK||!is_uploaded_file((string)($upload['tmp_name']??'')))throw new RuntimeException('The encrypted private-key.pem upload is required.');
  $pem=file_get_contents((string)$upload['tmp_name']);
  if(!is_string($pem))throw new RuntimeException('The private key could not be read.');
  try{return$this->buildAndSignReleaseWithPem($s,$pem,$passphrase);}
  finally{$pem=null;}
 }

 /**
  * Build and sign from PEM already held in memory.
  */
 public function buildAndSignReleaseWithPem(string$s,string$pem,string$passphrase):string
 {
  if($passphrase==='')throw new InvalidArgumentException('The private-key passphrase is required.');
  $root=$this->root($s);$metadata=$this->json($root.'/module.json');
  if(($metadata['certified']??'No')!=='Yes')throw new RuntimeException('The module must be marked certified before a signed release can be built.');
  $signing=$metadata['signing']??[];
  if(!is_array($signing)||(string)($signing['type']??'')!=='rsa-sha256'||!preg_match('/^[a-f0-9]{64}$/',(string)($signing['sha256']??''))||trim((string)($signing['key_id']??''))===''||trim((string)($signing['public_key']??''))==='')throw new RuntimeException('Complete RSA-SHA256 developer signing metadata is required.');

  $key=@openssl_pkey_get_private($pem,$passphrase);
  if($key===false)throw new RuntimeException('The private key or passphrase is invalid.');
  $details=openssl_pkey_get_details($key);
  if(!is_array($details)||!isset($details['key']))throw new RuntimeException('The private key public identity could not be read.');
  $public=(string)$details['key'];
  $expectedPublic=preg_replace('/\s+/','',(string)$signing['public_key'])??'';
  if(!hash_equals($expectedPublic,base64_encode($public)))throw new RuntimeException('The private key does not match module.json signing.public_key.');
  if(!hash_equals((string)$signing['sha256'],hash('sha256',$public)))throw new RuntimeException('The private key does not match module.json signing.sha256.');

  $artifact=$this->buildRelease($s);
  $signature='';
  $payload=hash_file('sha256',$artifact);
  if(!is_string($payload)||!openssl_sign($payload,$signature,$key,OPENSSL_ALGO_SHA256)){
   @unlink($artifact);
   throw new RuntimeException('The release could not be signed.');
  }

  $signaturePath=$artifact.'.sig';
  $this->write($signaturePath,base64_encode($signature).PHP_EOL);
  $base=basename($artifact,'.zip');
  $manifestPath=$this->artifactRoot($s,false).'/'.$base.'.manifest.json';
  $manifest=$this->json($manifestPath);
  $manifest['signed']=true;
  $manifest['signature']=basename($signaturePath);
  $manifest['signature_algorithm']='RSA-SHA256';
  $manifest['key_id']=(string)$signing['key_id'];
  $manifest['signed_at']=gmdate('c');
  $this->write($manifestPath,$this->encode($manifest));

  $pem=null;$public=null;$signature=null;$payload=null;
  return$artifact;
 }
 private function root(string$s):string{if(!$this->isValidSlug($s)||$s===self::SELF)throw new InvalidArgumentException('Invalid project.');$base=realpath($this->modules);$r=is_link($this->modules.'/'.$s)?false:realpath($this->modules.'/'.$s);if($base===false||$r===false||!str_starts_with($r,$base.DIRECTORY_SEPARATOR))throw new RuntimeException('Project outside module root.');return$r;}
 private function path(string$p):string{$p=trim(str_replace('\\','/',$p),'/');if($p===''||strlen($p)>240||str_contains($p,'..')||str_contains($p,"\0")||!preg_match('#^[A-Za-z0-9._/-]+$#',$p))throw new InvalidArgumentException('Invalid relative path.');return$p;}
 private function existing(string$s,string$p,bool$file):string{$r=$this->root($s);$x=$r.DIRECTORY_SEPARATOR.str_replace('/',DIRECTORY_SEPARATOR,$this->path($p));$y=is_link($x)?false:realpath($x);if($y===false||!str_starts_with($y,$r.DIRECTORY_SEPARATOR)||($file&&!is_file($y)))throw new RuntimeException('Path outside project or missing.');return$y;}
 private function fresh(string$s,string$p):string{$r=$this->root($s);$x=$r.DIRECTORY_SEPARATOR.str_replace('/',DIRECTORY_SEPARATOR,$this->path($p));$parent=is_link(dirname($x))?false:realpath(dirname($x));if($parent===false||!str_starts_with($parent,$r.DIRECTORY_SEPARATOR))throw new RuntimeException('Destination outside project.');return$x;}
 private function editable(string$p):void{if(!in_array(strtolower(pathinfo($p,PATHINFO_EXTENSION)),self::TEXT,true))throw new InvalidArgumentException('Unsupported editor file type.');if(is_file($p)&&filesize($p)>self::LIMIT)throw new RuntimeException('Editor limit exceeded.');}
 private function projectMetadata(string$s,string$n,string$v,array$in):array
 {
  $this->meta($s,$n,$v);
  $description=trim((string)($in['description']??''));
  $updateUrl=trim((string)($in['update_url']??''));
  $creator=trim((string)($in['creator']??''));
  $domain=strtolower(trim((string)($in['domain']??'')));
  $certified=(string)($in['certified']??'No');
  $type=strtolower(trim((string)($in['signing_type']??'sha256')));
  $fingerprint=trim((string)($in['signing_fingerprint']??''));
  $sha=strtolower(trim((string)($in['signing_sha256']??'')));
  $keyId=strtolower(trim((string)($in['signing_key_id']??'')));
  $publicInput=(string)($in['signing_public_key']??'');
  $publicKey=$type==='rsa-sha256'?$this->normalizePublicKey($publicInput):preg_replace('/\s+/','',trim($publicInput));
  $publicKey=is_string($publicKey)?$publicKey:'';

  if($description===''||strlen($description)>500)throw new InvalidArgumentException('Description is required and must be 500 characters or fewer.');
  if(filter_var($updateUrl,FILTER_VALIDATE_URL)===false||strtolower((string)parse_url($updateUrl,PHP_URL_SCHEME))!=='https')throw new InvalidArgumentException('A valid HTTPS update URL is required.');
  if($creator===''||strlen($creator)>100)throw new InvalidArgumentException('Creator is required and must be 100 characters or fewer.');
  if(!preg_match('/^(?=.{1,253}$)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/',$domain))throw new InvalidArgumentException('A valid domain name is required.');
  if(!in_array($certified,['Yes','No'],true))throw new InvalidArgumentException('Certified must be Yes or No.');
  if(!in_array($type,['sha256','rsa-sha256','openpgp'],true))throw new InvalidArgumentException('Signing type must be SHA-256, RSA-SHA256, or OpenPGP.');
  if(strlen($fingerprint)>255)throw new InvalidArgumentException('Signing fingerprint must not exceed 255 characters.');

  if(!preg_match('/^[a-f0-9]{64}$/',$sha))throw new InvalidArgumentException('Signing SHA-256 is required and must be 64 lowercase hexadecimal characters.');
  if($keyId!==''&&!preg_match('/^[a-z0-9][a-z0-9_-]{2,63}$/',$keyId))throw new InvalidArgumentException('Signing key ID is invalid.');
  if($type==='rsa-sha256'&&$publicKey!==''){$decoded=base64_decode($publicKey,true);if($decoded===false||!str_contains($decoded,'-----BEGIN PUBLIC KEY-----')||!str_contains($decoded,'-----END PUBLIC KEY-----'))throw new InvalidArgumentException('RSA signing public key must be a base64-encoded public PEM.');}
  if($type==='openpgp'&&$publicKey!==''&&base64_decode($publicKey,true)===false)throw new InvalidArgumentException('OpenPGP public key must be compact base64 data.');
  if(($keyId==='')!==($publicKey===''))throw new InvalidArgumentException('Signing key ID and public PEM must be supplied together.');
  if($certified==='Yes'&&($sha===''||$keyId===''||$publicKey===''))throw new InvalidArgumentException('Certified modules require complete signing metadata.');

  return[
   'name'=>$n,
   'module'=>$s,
   'version'=>$v,
   'description'=>$description,
   'update_url'=>$updateUrl,
   'creator'=>$creator,
   'domain'=>$domain,
   'certified'=>$certified,
   'signing'=>[
    'type'=>$type,
    'fingerprint'=>$fingerprint,
    'sha256'=>$sha,
    'key_id'=>$keyId,
    'public_key'=>$publicKey,
   ],
  ];
 }
 private function meta(string$s,string$n,string$v):void{if(!$this->isValidSlug($s))throw new InvalidArgumentException('Invalid lowercase slug.');if($n===''||strlen($n)>100)throw new InvalidArgumentException('Name required.');if(!preg_match('/^[0-9]+\.[0-9]+\.[0-9]+(?:[-+][0-9A-Za-z.-]+)?$/',$v))throw new InvalidArgumentException('Semantic version required.');}
 private function normalizePublicKey(string$value):string
 {
  $value=trim(str_replace(["\r\n","\r"],"\n",$value));if($value==='')return'';
  if(str_contains($value,'-----BEGIN PUBLIC KEY-----'))$pem=$value."\n";
  else{$compact=preg_replace('/\s+/','',$value)??'';$decoded=base64_decode($compact,true);if($decoded===false)throw new InvalidArgumentException('Signing public key must be PEM or base64 public-key data.');if(str_contains($decoded,'-----BEGIN PUBLIC KEY-----'))$pem=trim(str_replace(["\r\n","\r"],"\n",$decoded))."\n";else$pem="-----BEGIN PUBLIC KEY-----\n".chunk_split(base64_encode($decoded),64,"\n")."-----END PUBLIC KEY-----\n";}
  $key=@openssl_pkey_get_public($pem);if($key===false)throw new InvalidArgumentException('Signing public key is not valid.');$details=openssl_pkey_get_details($key);$canonical=is_array($details)?($details['key']??null):null;if(!is_string($canonical)||!str_contains($canonical,'-----BEGIN PUBLIC KEY-----'))throw new InvalidArgumentException('Signing public key is not valid.');return base64_encode($canonical);
 }
 private function databaseTables(string$s,string$value):array
 {
  $lines=preg_split('/[\r\n,]+/',$value);$tables=[];
  foreach(is_array($lines)?$lines:[]as$table){$table=strtolower(trim($table));if($table==='')continue;if(!preg_match('/^[a-z][a-z0-9_]{1,62}$/',$table)||($table!==$s&&!str_starts_with($table,$s.'_')))throw new InvalidArgumentException('Database tables must be the module slug or begin with the module slug and underscore.');$tables[]=$table;}
  $tables=array_values(array_unique($tables));if($tables===[])throw new InvalidArgumentException('At least one module-owned database table is required.');return$tables;
 }
 private function artifactRoot(string$s,bool$create):string{$this->root($s);$r=$this->releases.'/'.$s;if($create)$this->mkdir($r);return$r;}
 private function json(string$p,bool$required=true):array{if(!is_file($p)||is_link($p)){if($required)throw new RuntimeException('JSON missing.');return[];}$x=file_get_contents($p);$d=is_string($x)?json_decode($x,true):null;if(!is_array($d)){if($required)throw new RuntimeException('JSON invalid.');return[];}return$d;}
 private function encode(array$d):string{$x=json_encode($d,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);if(!is_string($x))throw new RuntimeException('JSON encode failed.');return$x.PHP_EOL;}
 private function write(string$p,string$c):void{$t=$p.'.tmp-'.bin2hex(random_bytes(5));if(file_put_contents($t,$c,LOCK_EX)===false||!rename($t,$p)){@unlink($t);throw new RuntimeException('Write failed.');}}
 private function mkdir(string$p):void{if(!is_dir($p)&&!mkdir($p,0755,true))throw new RuntimeException('Directory create failed.');}
 private function remove(string$r):void{if(is_link($r)){if(!unlink($r))throw new RuntimeException('Delete failed.');return;}if(!is_dir($r)){if(is_file($r)&&!unlink($r))throw new RuntimeException('Delete failed.');return;}$it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($r,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST);foreach($it as$i){$p=$i->getPathname();if(!(($i->isLink()||$i->isFile())?unlink($p):rmdir($p)))throw new RuntimeException('Delete failed.');}if(!rmdir($r))throw new RuntimeException('Delete failed.');}
 private function https(string$u):bool{$p=parse_url($u);if(!is_array($p)||strtolower((string)($p['scheme']??''))!=='https'||empty($p['host'])||isset($p['user'])||isset($p['pass']))return false;$h=strtolower((string)$p['host']);if($h==='localhost'||str_ends_with($h,'.local'))return false;$ip=filter_var($h,FILTER_VALIDATE_IP);return$ip===false||filter_var($ip,FILTER_VALIDATE_IP,FILTER_FLAG_NO_PRIV_RANGE|FILTER_FLAG_NO_RES_RANGE)!==false;}
 private function controller(string$s,bool$db):string
 {
  if(!$db)return"<?php\ndeclare(strict_types=1);\n/* [AI:GPT-5.6 Sol | ".gmdate('Y-m-d H:i:s')." UTC] */\nfinal class {$s} extends controller\n{\n public function index(array \$params=[]):void{\$this->view('index',['params'=>\$params]);}\n public function admin(array \$params=[]):void{\$this->require_admin(7);\$this->view('admin/{$s}',['params'=>\$params]);}\n}\n/* [End AI:GPT-5.6 Sol] */\n";
  return"<?php\ndeclare(strict_types=1);\n/* [AI:GPT-5.6 Sol | ".gmdate('Y-m-d H:i:s')." UTC] */\nfinal class {$s} extends controller\n{\n private const ADMIN_ACTIONS=['install_sql','update_sql','delete_data'];\n public function index(array \$params=[]):void{\$model=\$this->model('{$s}_model');if(\$model->databaseState()!=='current'){http_response_code(503);\$this->error_page('This module is temporarily unavailable.');}\$this->view('index',['params'=>\$params]);}\n public function admin(array \$params=[]):void{\$this->require_admin(7);\$model=\$this->model('{$s}_model');\$state=\$model->databaseState();if((\$_SERVER['REQUEST_METHOD']??'GET')==='POST'){\$this->require_csrf();\$action=(string)(\$_POST['action']??'');if(!in_array(\$action,self::ADMIN_ACTIONS,true)){http_response_code(400);\$this->error_page('Invalid module action.');}if(\$action==='install_sql'){if(\$state!=='missing')\$this->error_page('Schema is already installed.');\$model->installSchema();header('Location: /admin/{$s}?installed=1');exit;}if(\$action==='update_sql'){if(\$state!=='update')\$this->error_page('No schema update is pending.');\$model->updateSchema();header('Location: /admin/{$s}?updated=1');exit;}if(\$state!=='current')\$this->error_page('Complete the database lifecycle action first.');if(\$action==='delete_data'){\$model->deleteData();header('Location: /admin/{$s}?deleted_data=1');exit;}}\$this->view('admin/{$s}',['database_state'=>\$state,'installed'=>isset(\$_GET['installed']),'updated'=>isset(\$_GET['updated']),'deleted_data'=>isset(\$_GET['deleted_data'])]);}\n}\n/* [End AI:GPT-5.6 Sol] */\n";
 }
 private function model(string$s,array$tables,string$v):string
 {
  $tableExport=var_export(array_values($tables),true);$stateTable=$tables[0];$deletes='';foreach(array_reverse($tables)as$table)$deletes.=$table===$stateTable?"\$this->query('DELETE FROM `{$table}` WHERE `id` <> 1');":"\$this->query('DELETE FROM `{$table}`');";
  return"<?php\ndeclare(strict_types=1);\n/* [AI:GPT-5.6 Sol | ".gmdate('Y-m-d H:i:s')." UTC] */\nfinal class {$s}_model extends model\n{\n private const TABLES={$tableExport};\n private const STATE_TABLE='{$stateTable}';\n public function databaseState():string{foreach(self::TABLES as \$table)if(!\$this->tableExists(\$table))return'missing';return \$this->pendingPatch()!==null?'update':'current';}\n public function installSchema():void{\$this->executeSqlFile(__DIR__.'/../sql/schema.sql');}\n public function updateSchema():void{\$patch=\$this->pendingPatch();if(\$patch===null)throw new RuntimeException('No schema update is pending.');\$this->executeSqlFile(\$patch);\$this->query('UPDATE `'.self::STATE_TABLE.'` SET `schema_version` = :version WHERE `id` = 1',['version'=>\$this->targetVersion()]);}\n public function deleteData():void{{$deletes}}\n private function pendingPatch():?string{\$row=\$this->fetch('SELECT `schema_version` FROM `'.self::STATE_TABLE.'` WHERE `id` = 1 LIMIT 1');\$current=(string)(\$row['schema_version']??'');\$target=\$this->targetVersion();if(\$current===''||\$current===\$target)return null;\$file=__DIR__.'/../sql/patches/'.\$current.'-to-'.\$target.'.sql';return is_file(\$file)?\$file:null;}\n private function targetVersion():string{\$raw=file_get_contents(__DIR__.'/../module.json');\$metadata=is_string(\$raw)?json_decode(\$raw,true):null;return is_array(\$metadata)?(string)(\$metadata['version']??''):'';}\n private function executeSqlFile(string \$file):void{\$sql=is_file(\$file)?file_get_contents(\$file):false;if(!is_string(\$sql)||trim(\$sql)==='')throw new RuntimeException('SQL file could not be read.');\$statements=preg_split('/;\\s*(?:\\r?\\n|\$)/',\$sql);if(!is_array(\$statements))throw new RuntimeException('SQL file could not be parsed.');foreach(\$statements as \$statement){\$statement=trim(\$statement);if(\$statement!=='')\$this->query(\$statement);}}\n private function tableExists(string \$table):bool{return(bool)\$this->fetch('SELECT 1 FROM information_schema.tables WHERE table_schema = :schema AND table_name = :table_name LIMIT 1',['schema'=>DB_NAME,'table_name'=>\$table]);}\n}\n/* [End AI:GPT-5.6 Sol] */\n";
 }
 private function publicView(string$n):string{$n=htmlspecialchars($n,ENT_QUOTES,'UTF-8');return"<?php require APPROOT . '/views/inc/head.php'; ?>\n<?php /* [AI:GPT-5.6 Sol | ".gmdate('Y-m-d H:i:s')." UTC] */ ?>\n<main class=\"container py-5\"><h1>{$n}</h1></main>\n<?php /* [End AI:GPT-5.6 Sol] */ ?>\n<?php require APPROOT . '/views/inc/foot.php'; ?>\n";}

 private function changelog(string$v):string
 {
  return "# Changelog\n\n## {$v} — ".gmdate('Y-m-d')."\n\n### Added\n\n- Initial module implementation.\n";
 }
 private function schema(array$tables,string$v):string{$sql='';foreach($tables as$i=>$table){$sql.="CREATE TABLE IF NOT EXISTS `{$table}` (\n `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,\n".($i===0?" `schema_version` VARCHAR(64) NULL,\n":'')." `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,\n PRIMARY KEY (`id`)\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;\n\n";}return$sql."INSERT INTO `{$tables[0]}` (`id`, `schema_version`) VALUES (1, '{$v}') ON DUPLICATE KEY UPDATE `schema_version` = VALUES(`schema_version`);\n";}
 private function adminView(string$n,string$s,bool$db):string{$n=htmlspecialchars($n,ENT_QUOTES,'UTF-8');if(!$db)return"<?php /* [AI:GPT-5.6 Sol | ".gmdate('Y-m-d H:i:s')." UTC] */ ?>\n<section class=\"container-fluid py-4\"><h1>{$n}</h1><p>Module administration.</p><form method=\"post\" action=\"/admin/uninstall\"><?=\$this->csrf_field()?><input type=\"hidden\" name=\"module\" value=\"{$s}\"><button type=\"submit\">Nuke</button></form></section>\n<?php /* [End AI:GPT-5.6 Sol] */ ?>\n";return"<?php /* [AI:GPT-5.6 Sol | ".gmdate('Y-m-d H:i:s')." UTC] */ ?>\n<section class=\"container-fluid py-4\"><h1>{$n}</h1><?php \$state=(string)(\$data['database_state']??'missing');if(\$state==='missing'):?><form method=\"post\" action=\"/admin/{$s}\"><?=\$this->csrf_field()?><input type=\"hidden\" name=\"action\" value=\"install_sql\"><button type=\"submit\">Install SQL</button></form><?php elseif(\$state==='update'):?><form method=\"post\" action=\"/admin/{$s}\"><?=\$this->csrf_field()?><input type=\"hidden\" name=\"action\" value=\"update_sql\"><button type=\"submit\">Update SQL</button></form><?php else:?><p>Module administration.</p><form method=\"post\" action=\"/admin/{$s}\" onsubmit=\"return confirm('Delete all module data?');\"><?=\$this->csrf_field()?><input type=\"hidden\" name=\"action\" value=\"delete_data\"><button type=\"submit\">Delete Data</button></form><?php endif?><form method=\"post\" action=\"/admin/uninstall\" onsubmit=\"return confirm('Nuke this module?');\"><?=\$this->csrf_field()?><input type=\"hidden\" name=\"module\" value=\"{$s}\"><button type=\"submit\">Nuke</button></form></section>\n<?php /* [End AI:GPT-5.6 Sol] */ ?>\n";}
}
/* [End AI:GPT-5.6 Sol] */
