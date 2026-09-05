<?php
if (!class_exists('builder_release_signer', false)) {
    require_once __DIR__ . '/builder_release_signer.php';
}
if (!class_exists('builder_certification_client', false)) {
    require_once __DIR__ . '/builder_certification_client.php';
}
/* [AI:GPT-5.6 Sol | 2026-08-29 02:30:00 UTC] */
class module_package_builder
{
 private const SELF='module_builder'; private const LIMIT=1048576;
 private const TEXT=['php','json','md','txt','css','js','html','xml','sql','svg','yml','yaml'];
 private string $modules; private string $releases; private string $configFile;
 public function __construct(?string $modules=null,?string $releases=null,?string $configFile=null){$this->modules=$modules??USERROOT.'/modules';$this->releases=$releases??dirname(USERROOT).'/releases';$this->configFile=$configFile??__DIR__.'/../data/certification.json';$this->mkdir($this->modules);$this->mkdir($this->releases);$this->mkdir(dirname($this->configFile));}
 public function builderConfig():array{return$this->json($this->configFile,false);}
 public function configRequired():bool{$c=$this->builderConfig();return!isset($c['developer'],$c['domain'],$c['algorithm'],$c['key_id'],$c['transport_key'])||$c['developer']===''||$c['domain']===''||$c['key_id']===''||!in_array($c['algorithm'],['rsa-sha256','openpgp'],true)||!preg_match('/^[a-f0-9]{64}$/',(string)$c['transport_key']);}
 public function saveBuilderConfig(array$in):void{$old=$this->builderConfig();$developer=trim((string)($in['developer']??''));$domain=strtolower(trim((string)($in['domain']??'')));$algorithm=strtolower(trim((string)($in['algorithm']??'')));$keyId=strtolower(trim((string)($in['key_id']??'')));$transportKey=strtolower(trim((string)($in['transport_key']??$old['transport_key']??'')));if($developer===''||filter_var('https://'.$domain,FILTER_VALIDATE_URL)===false||!in_array($algorithm,['rsa-sha256','openpgp'],true)||!preg_match('/^[a-z0-9][a-z0-9_-]{2,63}$/',$keyId)||!preg_match('/^[a-f0-9]{64}$/',$transportKey))throw new InvalidArgumentException('Enter a valid developer, domain, signing algorithm, key ID, and transport key.');$base=['developer'=>$developer,'domain'=>$domain,'algorithm'=>$algorithm,'key_id'=>$keyId,'transport_key'=>$transportKey];$this->write($this->configFile,$this->encode($base));$result=(new builder_certification_client($this->releases.'/.certification-cache'))->verify($developer,$domain,'module',$algorithm,$keyId);$this->write($this->configFile,$this->encode($base+['public_key'=>is_string($result['public_key']??null)?$result['public_key']:'','fingerprint'=>(string)($result['fingerprint']??''),'credential_id'=>(string)($result['credential_id']??''),'verification_state'=>(string)($result['state']??'unavailable'),'verified_at'=>(string)($result['verified_at']??'')]));}
 public function isValidSlug(string $s):bool{return(bool)preg_match('/^[a-z][a-z0-9_]{1,62}$/',$s);}
 public function listProjects():array{$out=[];foreach(glob($this->modules.'/*',GLOB_ONLYDIR)?:[]as$d){$s=basename($d);if($s===self::SELF||!$this->isValidSlug($s)||is_link($d))continue;$m=$this->json($d.'/module.json',false);if(is_array($m['signing']??null))$m['signing']['type']??=$m['signing']['algorithm']??'none';$out[]=['slug'=>$s,'name'=>(string)($m['name']??$s),'version'=>(string)($m['version']??''),'description'=>(string)($m['description']??''),'update_url'=>(string)($m['update_url']??''),'creator'=>(string)($m['creator']??''),'domain'=>(string)($m['domain']??''),'certified'=>(string)($m['certified']??'No'),'package_hosts'=>(array)($m['package_hosts']??[]),'signing'=>is_array($m['signing']??null)?$m['signing']:[]];}usort($out,fn($a,$b)=>strcmp($a['slug'],$b['slug']));return$out;}
 public function createProject(array $in):void
 {
  $in=array_replace(['creator'=>'','domain'=>'','signing_type'=>'none','signing_key_id'=>''],$in);$cfg=$this->builderConfig();if(!$this->configRequired()){$in['creator']=$cfg['developer'];$in['domain']=$cfg['domain'];$in['signing_type']=$cfg['algorithm'];$in['signing_key_id']=$cfg['key_id'];}$s=strtolower(trim((string)($in['slug']??'')));$n=trim((string)($in['name']??''));$v=trim((string)($in['version']??''));$in['certified']='No';$in['signing_sha256']=hash('sha256',random_bytes(32));$in['signing_fingerprint']=$in['signing_sha256'];$in['signing_public_key']='';$in=$this->preloadCertifiedIdentity($in);if($in['signing_key_id']!==''&&($in['signing_public_key']??'')===''){$in['signing_type']='none';$in['signing_key_id']='';}$metadata=$this->projectMetadata($s,$n,$v,$in);$metadata['certified']=$this->certificationFor($metadata)?'Yes':'No';if($s===self::SELF)throw new InvalidArgumentException('Reserved slug.');
  $usesDatabase=isset($in['uses_database']);$tableInput=trim((string)($in['database_tables']??''));$tables=$usesDatabase?$this->databaseTables($s,$tableInput===''?$s:$tableInput):[];
  $r=$this->modules.'/'.$s;if(file_exists($r))throw new RuntimeException('Project exists.');$directories=['/controllers','/views/admin','/docs'];if($usesDatabase)$directories=array_merge($directories,['/models','/sql','/sql/patches']);foreach($directories as$d)$this->mkdir($r.$d);
  $files=['controllers/'.$s.'.php','views/admin/'.$s.'.php','views/index.php','docs/CHANGELOG.md'];if($usesDatabase)$files=['controllers/'.$s.'.php','models/'.$s.'_model.php','views/admin/'.$s.'.php','views/index.php','sql/schema.sql','sql/patches/.gitkeep','docs/CHANGELOG.md'];if($usesDatabase)$metadata['database_tables']=$tables;$metadata['files']=$files;$metadata['routes']=['index'];
  try{$this->write($r.'/module.json',$this->encode($metadata));$this->write($r.'/controllers/'.$s.'.php',$this->controller($s,$usesDatabase));if($usesDatabase){$this->write($r.'/models/'.$s.'_model.php',$this->model($s,$tables,$v));$this->write($r.'/sql/schema.sql',$this->schema($tables,$v));$this->write($r.'/sql/patches/.gitkeep','');}$this->write($r.'/views/index.php',$this->publicView($n));$this->write($r.'/views/admin/'.$s.'.php',$this->adminView($n,$s,$usesDatabase));$this->write($r.'/docs/CHANGELOG.md',$this->changelog($v));}catch(Throwable$e){$this->remove($r);throw$e;}
 }
 public function editProject(string$s,array$in):void{$r=$this->root($s);$m=$this->json($r.'/module.json');$n=trim((string)($in['name']??''));$v=trim((string)($in['version']??''));foreach(['type','fingerprint','sha256','key_id','public_key']as$field){$in['signing_'.$field]??=$m['signing'][$field]??($field==='type'?($m['signing']['algorithm']??'none'):'');}$in=$this->preloadCertifiedIdentity($in);$updated=$this->projectMetadata($s,$n,$v,$in);if(isset($in['package_hosts']))$updated['package_hosts']=array_values(array_filter(preg_split('/[\s,]+/',strtolower(trim((string)$in['package_hosts'])))?:[]));if(array_key_exists('database_tables',$m))$updated['database_tables']=$m['database_tables'];$updated['files']=$m['files']??['controllers/'.$s.'.php','views/admin/'.$s.'.php','views/index.php','docs/CHANGELOG.md'];$updated['routes']=$m['routes']??['index'];$final=array_replace($m,$updated);$final['certified']=$this->certificationFor($final)?'Yes':'No';$this->write($r.'/module.json',$this->encode($final));}
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
  try{$this->meta($s,(string)($m['name']??''),(string)($m['version']??''));}catch(InvalidArgumentException $error){$e[]=$error->getMessage();}
  if(($m['module']??null)!==$s)$e[]='module metadata must match slug.';
  foreach(['name','module','version','description','update_url','creator','domain','certified','signing','files','routes']as$f)if(!array_key_exists($f,$m))$e[]='Missing metadata: '.$f;
  $usesDatabase=array_key_exists('database_tables',$m);
  if(isset($m['update_url'])&&(filter_var($m['update_url'],FILTER_VALIDATE_URL)===false||parse_url($m['update_url'],PHP_URL_SCHEME)!=='https'))$e[]='update_url must be valid HTTPS.';
  if(isset($m['certified'])&&!in_array($m['certified'],['Yes','No'],true))$e[]='certified must be Yes or No.';
  $signing=$m['signing']??null;if(!is_array($signing))$e[]='signing metadata must be an object.';else{
   foreach(['key_id','public_key']as$f)if(!array_key_exists($f,$signing))$e[]='Missing signing metadata: '.$f;
   if(isset($signing['algorithm'],$signing['type'])&&strtolower($signing['algorithm'])!==strtolower($signing['type']))$e[]='Conflicting signing algorithms.';
   $type=strtolower((string)($signing['algorithm']??$signing['type']??'none'));if($type==='pgp')$type='openpgp';$fingerprint=(string)($signing['fingerprint']??'');$sha=(string)($signing['sha256']??'');$keyId=(string)($signing['key_id']??'');$publicKey=(string)($signing['public_key']??'');
   if(!in_array($type,['none','sha256','rsa-sha256','openpgp'],true))$e[]='signing.type must be none, rsa-sha256, or openpgp.';
   if(strlen($fingerprint)>255)$e[]='signing.fingerprint must not exceed 255 characters.';
   if($sha!==''&&!preg_match('/^[a-f0-9]{64}$/',$sha))$e[]='Optional public-key sha256 must be lowercase SHA-256.';
   if($keyId!==''&&!preg_match('/^[a-z0-9][a-z0-9_-]{2,63}$/',$keyId))$e[]='signing.key_id is invalid.';
   if($type==='rsa-sha256'&&$publicKey!==''){$pem=str_starts_with($publicKey,'-----BEGIN ')?$publicKey:base64_decode($publicKey,true);if($pem===false||!str_contains($pem,'-----BEGIN PUBLIC KEY-----'))$e[]='RSA signing.public_key must be base64 public PEM.';}
   if($type==='openpgp'&&$publicKey!==''&&!str_starts_with($publicKey,'-----BEGIN PGP PUBLIC KEY BLOCK-----')&&base64_decode($publicKey,true)===false)$e[]='OpenPGP signing.public_key must be compact base64 data.';
   if(($keyId==='')!==($publicKey===''))$e[]='signing.key_id and signing.public_key must be supplied together.';
  }
  if(!is_file($c))$e[]='Required controller missing.';else{$x=(string)file_get_contents($c);if(!preg_match('/function\s+index\s*\(/',$x))$e[]='Controller index() missing.';if(!preg_match('/function\s+admin\s*\(/',$x))$e[]='Controller admin() missing.';if($usesDatabase)foreach(['install_sql','update_sql','delete_data','require_csrf']as$required)if(!str_contains($x,$required))$e[]='Controller lifecycle missing: '.$required.'.';}
  if(!is_file($v))$e[]='views/index.php missing.';else{$x=(string)file_get_contents($v);foreach(["theme::render('head'","theme::render('foot'"]as$w)if(!str_contains($x,$w))$e[]='Theme integration missing: '.$w;}
  if(!is_file($r.'/'.$admin))$e[]=$admin.' missing.';
  if(!is_array($m['routes']??null)||!in_array('index',$m['routes'],true))$e[]='routes[] must include index.';
  foreach(['controllers/'.$s.'.php',$admin,'views/index.php','docs/CHANGELOG.md']as$f)if(!is_array($m['files']??null)||!in_array($f,$m['files'],true))$e[]='files must include '.$f.'.';
  if($usesDatabase){$tables=$m['database_tables'];if(!is_array($tables)||$tables===[])$e[]='database_tables must declare at least one owned table.';else foreach($tables as$table)if(!is_string($table)||($table!==$s&&!str_starts_with($table,$s.'_'))||!preg_match('/^[a-z][a-z0-9_]{1,62}$/',$table))$e[]='Invalid module-owned database table: '.(string)$table.'.';foreach(['models/'.$s.'_model.php','sql/schema.sql','sql/patches/.gitkeep']as$f)if(!is_array($m['files']??null)||!in_array($f,$m['files'],true))$e[]='files must include '.$f.'.';if(!is_file($r.'/sql/schema.sql'))$e[]='sql/schema.sql missing.';if(!is_file($r.'/sql/patches/.gitkeep'))$e[]='sql/patches/.gitkeep missing.';}
  if(is_file($r.'/'.$admin)&&!str_contains((string)file_get_contents($r.'/'.$admin),'/admin/uninstall'))$e[]='Admin Core Nuke control missing.';
  if(!is_file($r.'/docs/CHANGELOG.md'))$e[]='docs/CHANGELOG.md missing.';
  return['valid'=>$e===[],'errors'=>$e];
 }
 public function buildRelease(string$s):string
 {
  $validation=$this->validateProject($s);
  if(!$validation['valid'])throw new RuntimeException("Project validation failed:\n- ".implode("\n- ",$validation['errors']));
  if(!class_exists('ZipArchive'))throw new RuntimeException('ZIP extension required.');
  $r=$this->root($s);$m=$this->json($r.'/module.json');$m['certified']=$this->certificationFor($m)?'Yes':'No';$m['signing']['algorithm']=$m['signing']['algorithm']??$m['signing']['type']??'none';unset($m['signing']['type']);$this->write($r.'/module.json',$this->encode($m));$base=$s.'-'.$m['version'];$out=$this->artifactRoot($s,true);$zipPath=$out.'/'.$base.'.zip';$tmp=$zipPath.'.tmp-'.bin2hex(random_bytes(5));$z=new ZipArchive();if($z->open($tmp,ZipArchive::CREATE|ZipArchive::OVERWRITE)!==true)throw new RuntimeException('ZIP create failed.');
  try{foreach($this->fileTree($s)as$entry){if(!$entry['directory']){$f=$this->existing($s,$entry['path'],true);if(!$z->addFile($f,$s.'/'.$entry['path']))throw new RuntimeException('ZIP add failed.');}}}finally{$z->close();}
  if(!rename($tmp,$zipPath))throw new RuntimeException('ZIP finalize failed.');if(is_file($zipPath.'.sig')||is_link($zipPath.'.sig')){if(!unlink($zipPath.'.sig'))throw new RuntimeException('Cannot invalidate the previous signature.');}builder_release_signer::invalidatePublication($zipPath,$s);$hash=hash_file('sha256',$zipPath);$this->write($out.'/'.$base.'.sha256',$hash.'  '.basename($zipPath).PHP_EOL);$this->write($out.'/'.$base.'.manifest.json',$this->encode(['module'=>$s,'version'=>$m['version'],'artifact'=>basename($zipPath),'sha256'=>$hash,'signed'=>false,'built_at'=>gmdate('c')]));return$zipPath;
 }
 public function listArtifacts(string$s):array{$r=$this->artifactRoot($s,false);if(!is_dir($r))return[];$o=[];foreach(scandir($r)?:[]as$n){$p=$r.'/'.$n;if($n==='.'||$n==='..'||!is_file($p)||is_link($p))continue;$release=str_ends_with($n,'.manifest.json')?$this->json($p,false):[];$o[]=['name'=>$n,'size'=>filesize($p),'modified'=>filemtime($p),'verification'=>is_array($release['verification']??null)?$release['verification']:[],'signature_algorithm'=>($release['signed']??false)===true?(string)($release['signature_algorithm']??''):'','signature'=>is_string($release['signature']??null)?$release['signature']:''];}usort($o,fn($a,$b)=>$b['modified']<=>$a['modified']);return$o;}
 public function artifactFile(string$s,string$n):string
 {
  if($n!==basename($n)||!preg_match('/^[A-Za-z0-9._-]{1,240}$/',$n))throw new InvalidArgumentException('Invalid artifact.');
  $root=$this->artifactRoot($s,false);$resolvedRoot=is_link($root)?false:realpath($root);$candidate=$root.'/'.$n;$resolved=is_link($candidate)?false:realpath($candidate);
  if($resolvedRoot===false||$resolved===false||!str_starts_with($resolved,$resolvedRoot.DIRECTORY_SEPARATOR)||!is_file($resolved))throw new RuntimeException('Artifact was not found.');
  return$resolved;
 }
 private function preloadCertifiedIdentity(array $input):array
 {
  $cfg=$this->builderConfig();if(!$this->configRequired()&&!empty($cfg['public_key'])&&($input['creator']??'')===$cfg['developer']&&strtolower((string)($input['domain']??''))===$cfg['domain']&&strtolower((string)($input['signing_type']??''))===$cfg['algorithm']&&strtolower((string)($input['signing_key_id']??''))===$cfg['key_id']){$input['signing_public_key']=$cfg['public_key'];$input['signing_fingerprint']=$cfg['fingerprint']??($input['signing_fingerprint']??'');}
  $algorithm=strtolower(trim((string)($input['signing_type']??'')));if($algorithm==='pgp')$algorithm='openpgp';
  $result=(new builder_certification_client($this->releases.'/.certification-cache'))->verify(
   (string)($input['creator']??''),(string)($input['domain']??''),'module',$algorithm,(string)($input['signing_key_id']??'')
  );
  if(($result['certified']??false)===true&&is_string($result['public_key']??null)&&$result['public_key']!==''){
   $input['signing_public_key']=$result['public_key'];
   if(is_string($result['fingerprint']??null)&&$result['fingerprint']!=='')$input['signing_fingerprint']=$result['fingerprint'];
  }
  return$input;
 }
 private function certificationFor(array $metadata):bool
 {
  $trust=is_array($metadata['signing']??null)?$metadata['signing']:[];
  $algorithm=strtolower((string)($trust['algorithm']??$trust['type']??''));
  if($algorithm==='pgp')$algorithm='openpgp';
  $result=(new builder_certification_client($this->releases.'/.certification-cache'))->verify(
   (string)($metadata['creator']??''),(string)($metadata['domain']??''),'module',$algorithm,(string)($trust['key_id']??'')
  );
  return($result['certified']??false)===true&&($result['signing']??false)===true;
 }
 public function certificationStatus(string $slug=''):array
 {
  $status=['state'=>'not_verified','certified'=>false,'signing'=>false,'message'=>'Certification: Not Verified. Select a project and configure its developer identity; Builder and signing remain available.'];
  $c=$this->builderConfig();if(!$this->configRequired())return(new builder_certification_client($this->releases.'/.certification-cache'))->verify($c['developer'],$c['domain'],'module',$c['algorithm'],$c['key_id']);
  if(!$this->isValidSlug($slug)||$slug===self::SELF){$c=$this->builderConfig();if($this->configRequired())return$status;return(new builder_certification_client($this->releases.'/.certification-cache'))->verify($c['developer'],$c['domain'],'module',$c['algorithm'],$c['key_id']);}
  try{$metadata=$this->json($this->root($slug).'/module.json');}
  catch(Throwable $error){return$status;}
  $trust=is_array($metadata['signing']??null)?$metadata['signing']:[];
  $algorithm=strtolower((string)($trust['algorithm']??$trust['type']??''));
  if($algorithm==='pgp')$algorithm='openpgp';
  return(new builder_certification_client($this->releases.'/.certification-cache'))->verify(
   (string)($metadata['creator']??''),
   (string)($metadata['domain']??''),
   'module',
   $algorithm,
   (string)($trust['key_id']??'')
  );
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
 public function buildAndSignRelease(string$s,array$upload,string$passphrase,string$download=''):string
 {
  if(($upload['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK||!is_uploaded_file((string)($upload['tmp_name']??'')))throw new RuntimeException('The encrypted private-key.pem upload is required.');
  $pem=file_get_contents((string)$upload['tmp_name']);
  if(!is_string($pem)||strlen($pem)>self::LIMIT)throw new RuntimeException('The private key could not be read or exceeds 1 MiB.');
  try{return$this->buildAndSignReleaseWithPem($s,$pem,$passphrase,$download);}
  finally{$pem=null;}
 }

 /**
  * Build and sign from PEM already held in memory.
  */
 public function buildAndSignReleaseWithPem(string$s,string$pem,string$passphrase,string$download=''):string
 {
  $root=$this->root($s);$metadata=$this->json($root.'/module.json');
  builder_release_signer::publication($metadata,$download);
  builder_release_signer::requireBackend(builder_release_signer::algorithm((array)($metadata['signing']??[])));
  $artifact=$this->buildRelease($s);$metadata=$this->json($this->root($s).'/module.json');
  $manifest=builder_release_signer::sign('module',$metadata,$artifact,$download,$pem,$passphrase);
  $signaturePath=$artifact.'.sig';
  try {
   foreach(builder_release_signer::releaseFiles('module',$metadata,$manifest,$artifact) as $path=>$contents)$this->write($path,$contents);
   $manifest['verification']=builder_release_signer::verifyWrittenRelease('module',$metadata,$manifest,$artifact);
   $manifest['verification']=builder_release_signer::stageLocalRelease('module',$metadata,$manifest,$artifact);
  } catch (Throwable $exception) {
   builder_release_signer::invalidatePublication($artifact,$s);
   throw $exception;
  }
  $base=basename($artifact,'.zip');
  $manifestPath=$this->artifactRoot($s,false).'/'.$base.'.manifest.json';
  $this->write($manifestPath,$this->encode($manifest));

  $pem=null;
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
  $type=strtolower(trim((string)($in['signing_type']??'none')));
  if($type==='sha256')$type='none';if($type==='pgp')$type='openpgp';
  $fingerprint=trim((string)($in['signing_fingerprint']??''));
  $sha=strtolower(trim((string)($in['signing_sha256']??'')));
  $keyId=strtolower(trim((string)($in['signing_key_id']??'')));
  $publicInput=(string)($in['signing_public_key']??'');
  $publicKey=$type==='rsa-sha256'?$this->normalizePublicKey($publicInput):(str_starts_with(trim($publicInput),'-----BEGIN ')?base64_encode(trim($publicInput)):preg_replace('/\s+/','',trim($publicInput)));
  $publicKey=is_string($publicKey)?$publicKey:'';

  if($description===''||strlen($description)>500)throw new InvalidArgumentException('Description is required and must be 500 characters or fewer.');
  if(filter_var($updateUrl,FILTER_VALIDATE_URL)===false||strtolower((string)parse_url($updateUrl,PHP_URL_SCHEME))!=='https')throw new InvalidArgumentException('A valid HTTPS update URL is required.');
  if($creator===''||strlen($creator)>100)throw new InvalidArgumentException('Creator is required and must be 100 characters or fewer.');
  if(!preg_match('/^(?=.{1,253}$)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/',$domain))throw new InvalidArgumentException('A valid domain name is required.');
  if(!in_array($certified,['Yes','No'],true))throw new InvalidArgumentException('Certified must be Yes or No.');
  if(!in_array($type,['none','sha256','rsa-sha256','openpgp'],true))throw new InvalidArgumentException('Signature algorithm must be None, RSA-SHA256, or OpenPGP.');
  if(strlen($fingerprint)>255)throw new InvalidArgumentException('Signing fingerprint must not exceed 255 characters.');

  if($sha!==''&&!preg_match('/^[a-f0-9]{64}$/',$sha))throw new InvalidArgumentException('Optional public-key SHA-256 must be 64 lowercase hexadecimal characters.');
  if($keyId!==''&&!preg_match('/^[a-z0-9][a-z0-9_-]{2,63}$/',$keyId))throw new InvalidArgumentException('Signing key ID is invalid.');
  if($type==='rsa-sha256'&&$publicKey!==''){$decoded=base64_decode($publicKey,true);if($decoded===false||!str_contains($decoded,'-----BEGIN PUBLIC KEY-----')||!str_contains($decoded,'-----END PUBLIC KEY-----'))throw new InvalidArgumentException('RSA signing public key must be a base64-encoded public PEM.');}
  if($type==='openpgp'&&$publicKey!==''&&base64_decode($publicKey,true)===false)throw new InvalidArgumentException('OpenPGP public key must be compact base64 data.');
  if(($keyId==='')!==($publicKey===''))throw new InvalidArgumentException('Signing key ID and public PEM must be supplied together.');

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
    'algorithm'=>$type,
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
  $template=$db?<<<'PHP'
<?php
declare(strict_types=1);

/* [AI:GPT-5.6 Sol | {{TIME}} UTC] */
final class {{SLUG}} extends controller
{
    private const ADMIN_ACTIONS = [
        'install_sql',
        'update_sql',
        'delete_data',
    ];

    public function index(array $params = []): void
    {
        $model = $this->model('{{SLUG}}_model');

        if ($model->databaseState() !== 'current') {
            http_response_code(503);
            $this->error_page('This module is temporarily unavailable.');
        }

        $this->view('index', ['params' => $params]);
    }

    public function admin(array $params = []): void
    {
        $this->require_admin(7);
        $model = $this->model('{{SLUG}}_model');
        $state = $model->databaseState();

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $this->require_csrf();
            $action = (string) ($_POST['action'] ?? '');

            if (!in_array($action, self::ADMIN_ACTIONS, true)) {
                http_response_code(400);
                $this->error_page('Invalid module action.');
            }

            if ($action === 'install_sql') {
                if ($state !== 'missing') {
                    $this->error_page('Schema is already installed.');
                }
                $model->installSchema();
                header('Location: /admin/{{SLUG}}?installed=1');
                exit;
            }

            if ($action === 'update_sql') {
                if ($state !== 'update') {
                    $this->error_page('No schema update is pending.');
                }
                $model->updateSchema();
                header('Location: /admin/{{SLUG}}?updated=1');
                exit;
            }

            if ($state !== 'current') {
                $this->error_page('Complete the database lifecycle action first.');
            }

            if ($action === 'delete_data') {
                $model->deleteData();
                header('Location: /admin/{{SLUG}}?deleted_data=1');
                exit;
            }
        }

        $this->view('admin/{{SLUG}}', [
            'database_state' => $state,
            'installed' => isset($_GET['installed']),
            'updated' => isset($_GET['updated']),
            'deleted_data' => isset($_GET['deleted_data']),
        ]);
    }
}
/* [End AI:GPT-5.6 Sol] */
PHP
:<<<'PHP'
<?php
declare(strict_types=1);

/* [AI:GPT-5.6 Sol | {{TIME}} UTC] */
final class {{SLUG}} extends controller
{
    public function index(array $params = []): void
    {
        $this->view('index', ['params' => $params]);
    }

    public function admin(array $params = []): void
    {
        $this->require_admin(7);
        $this->view('admin/{{SLUG}}', ['params' => $params]);
    }
}
/* [End AI:GPT-5.6 Sol] */
PHP;
  return str_replace(['{{SLUG}}','{{TIME}}'],[$s,gmdate('Y-m-d H:i:s')],$template)."\n";
 }
 private function model(string$s,array$tables,string$v):string
 {
  $tableLines=implode(",\n",array_map(static fn(string$table):string=>"        '{$table}'",$tables));
  $stateTable=$tables[0];$deletes=[];foreach(array_reverse($tables)as$table)$deletes[]=$table===$stateTable?"        \$this->query('DELETE FROM `{$table}` WHERE `id` <> 1');":"        \$this->query('DELETE FROM `{$table}`');";
  $template=<<<'PHP'
<?php
declare(strict_types=1);

/* [AI:GPT-5.6 Sol | {{TIME}} UTC] */
final class {{SLUG}}_model extends model
{
    private const TABLES = [
{{TABLES}},
    ];
    private const STATE_TABLE = '{{STATE_TABLE}}';

    public function databaseState(): string
    {
        foreach (self::TABLES as $table) {
            if (!$this->tableExists($table)) {
                return 'missing';
            }
        }

        $current = $this->schemaVersion();
        $target = $this->targetVersion();

        if ($current === null || $target === '') {
            return 'invalid';
        }
        if ($current === $target) {
            return 'current';
        }

        return $this->patchFile($current, $target) !== null
            ? 'update'
            : 'invalid';
    }

    public function installSchema(): void
    {
        $this->executeSqlFile(__DIR__ . '/../sql/schema.sql');
    }

    public function updateSchema(): void
    {
        $current = $this->schemaVersion();
        $target = $this->targetVersion();
        $patch = $current === null ? null : $this->patchFile($current, $target);

        if ($patch === null) {
            throw new RuntimeException('No valid schema migration path exists.');
        }

        $this->executeSqlFile($patch);
        $this->query(
            'UPDATE `' . self::STATE_TABLE . '` SET `schema_version` = :version WHERE `id` = 1',
            ['version' => $target]
        );
    }

    public function deleteData(): void
    {
{{DELETES}}
    }

    private function schemaVersion(): ?string
    {
        $row = $this->fetch(
            'SELECT `schema_version` FROM `' . self::STATE_TABLE . '` WHERE `id` = 1 LIMIT 1'
        );
        $version = is_array($row) ? trim((string) ($row['schema_version'] ?? '')) : '';

        return preg_match('/^[0-9]+\.[0-9]+\.[0-9]+(?:[-+][0-9A-Za-z.-]+)?$/', $version) === 1
            ? $version
            : null;
    }

    private function targetVersion(): string
    {
        $raw = file_get_contents(__DIR__ . '/../module.json');
        $metadata = is_string($raw) ? json_decode($raw, true) : null;
        $version = is_array($metadata) ? trim((string) ($metadata['version'] ?? '')) : '';

        return preg_match('/^[0-9]+\.[0-9]+\.[0-9]+(?:[-+][0-9A-Za-z.-]+)?$/', $version) === 1
            ? $version
            : '';
    }

    private function patchFile(string $current, string $target): ?string
    {
        $file = __DIR__ . '/../sql/patches/' . $current . '-to-' . $target . '.sql';
        return is_file($file) && !is_link($file) ? $file : null;
    }

    private function executeSqlFile(string $file): void
    {
        $sql = is_file($file) ? file_get_contents($file) : false;
        if (!is_string($sql) || trim($sql) === '') {
            throw new RuntimeException('SQL file could not be read.');
        }
        $statements = preg_split('/;\s*(?:\r?\n|$)/', $sql);
        if (!is_array($statements)) {
            throw new RuntimeException('SQL file could not be parsed.');
        }
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if ($statement !== '') {
                $this->query($statement);
            }
        }
    }

    private function tableExists(string $table): bool
    {
        return (bool) $this->fetch(
            'SELECT 1 FROM information_schema.tables '
            . 'WHERE table_schema = :schema AND table_name = :table_name LIMIT 1',
            ['schema' => DB_NAME, 'table_name' => $table]
        );
    }
}
/* [End AI:GPT-5.6 Sol] */
PHP;
  return str_replace(['{{SLUG}}','{{TIME}}','{{TABLES}}','{{STATE_TABLE}}','{{DELETES}}'],[$s,gmdate('Y-m-d H:i:s'),$tableLines,$stateTable,implode("\n",$deletes)],$template)."\n";
 }
 private function publicView(string$n):string
 {
  $n=htmlspecialchars($n,ENT_QUOTES,'UTF-8');$template=<<<'PHP'
<?php
/* [AI:GPT-5.6 Sol | {{TIME}} UTC] */
if (!theme::render('head', get_defined_vars())) {
    require APPROOT . '/views/inc/head.php';
}
?>
<main class="container py-5">
    <h1>{{NAME}}</h1>
</main>
<?php
if (!theme::render('foot', get_defined_vars())) {
    require APPROOT . '/views/inc/foot.php';
}
/* [End AI:GPT-5.6 Sol] */
PHP;
  return str_replace(['{{NAME}}','{{TIME}}'],[$n,gmdate('Y-m-d H:i:s')],$template)."\n";
 }

 private function changelog(string$v):string
 {
  return "# Changelog\n\n## {$v} — ".gmdate('Y-m-d')."\n\n### Added\n\n- Initial module implementation.\n";
 }
 private function schema(array$tables,string$v):string{$sql='';foreach($tables as$i=>$table){$sql.="CREATE TABLE IF NOT EXISTS `{$table}` (\n `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,\n".($i===0?" `schema_version` VARCHAR(64) NULL,\n":'')." `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,\n PRIMARY KEY (`id`)\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;\n\n";}return$sql."INSERT INTO `{$tables[0]}` (`id`, `schema_version`) VALUES (1, '{$v}') ON DUPLICATE KEY UPDATE `schema_version` = VALUES(`schema_version`);\n";}
 private function adminView(string$n,string$s,bool$db):string
 {
  $n=htmlspecialchars($n,ENT_QUOTES,'UTF-8');$lifecycle=$db?<<<'PHP'
<?php $state = (string) ($data['database_state'] ?? 'missing'); ?>
<?php if ($state === 'missing') : ?>
    <form method="post" action="/admin/{{SLUG}}">
        <?= $this->csrf_field(); ?>
        <input type="hidden" name="action" value="install_sql">
        <button type="submit">Install SQL</button>
    </form>
<?php elseif ($state === 'update') : ?>
    <form method="post" action="/admin/{{SLUG}}">
        <?= $this->csrf_field(); ?>
        <input type="hidden" name="action" value="update_sql">
        <button type="submit">Update SQL</button>
    </form>
<?php elseif ($state === 'invalid') : ?>
    <p role="alert">The database schema version is invalid or no migration path exists.</p>
<?php else : ?>
    <p>Module administration.</p>
    <form method="post" action="/admin/{{SLUG}}" onsubmit="return confirm('Delete all module data?');">
        <?= $this->csrf_field(); ?>
        <input type="hidden" name="action" value="delete_data">
        <button type="submit">Delete Data</button>
    </form>
<?php endif; ?>
PHP:'<p>Module administration.</p>';
  $template=<<<'PHP'
<?php
/* [AI:GPT-5.6 Sol | {{TIME}} UTC] */
if (!theme::render('head', get_defined_vars())) {
    require APPROOT . '/views/inc/head.php';
}
?>
<main class="container py-4">
    <h1>{{NAME}}</h1>
{{LIFECYCLE}}
    <form method="post" action="/admin/uninstall" onsubmit="return confirm('Nuke this module?');">
        <?= $this->csrf_field(); ?>
        <input type="hidden" name="module" value="{{SLUG}}">
        <button type="submit">Nuke</button>
    </form>
</main>
<?php
if (!theme::render('foot', get_defined_vars())) {
    require APPROOT . '/views/inc/foot.php';
}
/* [End AI:GPT-5.6 Sol] */
PHP;
  return str_replace(['{{NAME}}','{{SLUG}}','{{TIME}}','{{LIFECYCLE}}'],[$n,$s,gmdate('Y-m-d H:i:s'),$lifecycle],$template)."\n";
 }
}
/* [End AI:GPT-5.6 Sol] */
