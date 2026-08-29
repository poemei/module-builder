<?php
/* [AI:GPT-5.6 Sol | 2026-08-29 02:30:00 UTC] */
class module_builder extends controller
{
 public function admin($params=[]):void
 {
  $this->require_admin(9);
  require_once USERROOT.'/modules/module_builder/lib/module_package_builder.php';
  $b=new module_package_builder(); $message=$error=null;
  if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){ $this->require_csrf(); try{$message=$this->act($b);}catch(Throwable $e){http_response_code(400);$error=$e->getMessage();}}
  $selected=trim((string)($_REQUEST['project']??'')); $selected=$b->isValidSlug($selected)?$selected:'';
  $path=trim((string)($_REQUEST['path']??''));$content=null;
  if($selected&&$path){try{$content=$b->readFile($selected,$path);}catch(Throwable $e){$error=$e->getMessage();}}
  $this->view('admin/index',['projects'=>$b->listProjects(),'selected'=>$selected,'tree'=>$selected?$b->fileTree($selected):[],'path'=>$path,'content'=>$content,'validation'=>$selected?$b->validateProject($selected):null,'artifacts'=>$selected?$b->listArtifacts($selected):[],'certification'=>$b->certificationStatus(),'message'=>$message,'error'=>$error,'csrf_field'=>$this->csrf_field()]);
 }
 private function act(module_package_builder $b):string
 {
  $a=(string)($_POST['action']??'');$p=(string)($_POST['project']??'');
  switch($a){
   case 'create_project':$b->createProject($_POST);return 'Project created.';
   case 'edit_project':$b->editProject($p,$_POST);return 'Project updated.';
   case 'delete_project':$b->deleteProject($p);$_REQUEST['project']='';return 'Project deleted.';
   case 'save_file':$b->writeFile($p,(string)$_POST['path'],(string)$_POST['content']);return 'File saved.';
   case 'create_file':$b->createFile($p,(string)$_POST['path']);return 'File created.';
   case 'create_directory':$b->createDirectory($p,(string)$_POST['path']);return 'Directory created.';
   case 'rename_path':$b->renamePath($p,(string)$_POST['path'],(string)$_POST['new_path']);return 'Path renamed.';
   case 'delete_path':$b->deletePath($p,(string)$_POST['path']);return 'Path deleted.';
   case 'build_release':return 'Unsigned release built: '.basename($b->buildRelease($p));
   case 'sign_release':$b->signRelease($p,(string)$_POST['artifact'],$_FILES['private_key']??[]);return 'Release signed; key not retained.';
  } throw new InvalidArgumentException('Unknown action.');
 }
}
/* [End AI:GPT-5.6 Sol] */
