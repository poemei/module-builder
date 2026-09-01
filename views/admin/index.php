<?php
/* [AI:GPT-5.6 Sol | 2026-08-29 02:30:00 UTC] */
$e=static fn($v):string=>htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');
$map=[];
foreach($projects as$p)$map[$p['slug']]=$p;
$current=$map[$selected]??null;
$hasSigning=$current!==null
 &&($current['certified']??'No')==='Yes'
 &&preg_match('/^[a-f0-9]{64}$/',(string)($current['signing']['sha256']??''))===1
 &&trim((string)($current['signing']['key_id']??''))!==''
 &&trim((string)($current['signing']['public_key']??''))!=='';
require APPROOT . '/views/inc/head.php';
?>
<main class="container-fluid py-4">
 <div class="d-flex justify-content-between mb-4"><div><h1>Module Builder <small class="text-muted">1.0.6</small></h1><p>Live source: <code>user/modules/&lt;slug&gt;/</code>. Output: root <code>/releases</code>.</p></div><span class="badge <?= $certification['signing']?'bg-success':'bg-secondary' ?> p-2 align-self-start"><?= $certification['signing']?'Certified signing':'Unsigned development' ?></span></div>
 <?php if($message):?><div class="alert alert-success"><?=$e($message)?></div><?php endif?><?php if($error):?><div class="alert alert-danger"><?=$e($error)?></div><?php endif?>
 <div class="alert alert-light border"><strong>Certification:</strong> <?=$e($certification['message'])?></div>
 <section class="card mb-4">
  <div class="card-header fw-bold">RSA-SHA256 Signing Keypair</div>
  <div class="card-body">
   <p class="text-muted">Generate an encrypted 3072-bit RSA private key and matching public key. A ZIP containing private-key.pem, public-key.pem, and key-metadata.json downloads immediately; Module Builder never stores the keys.</p>
   <form method="post" class="row g-2 align-items-end" onsubmit="return confirm('The only copy of this keypair will be in the download. Continue?');">
    <?=$csrf_field?>
    <input type="hidden" name="action" value="generate_keypair">
    <div class="col-md-3"><label class="form-label">Key ID prefix<input class="form-control" name="key_id_prefix" value="developer" pattern="[a-z0-9][a-z0-9_-]{1,31}" required></label></div>
    <div class="col-md-6"><label class="form-label">Private-key passphrase<input class="form-control" type="password" name="key_passphrase" minlength="12" autocomplete="new-password" required></label></div>
    <div class="col-md-3"><button class="btn btn-outline-primary w-100">Generate and download</button></div>
   </form>
  </div>
 </section>
 <div class="row g-4"><aside class="col-xl-3">
  <div class="card mb-4"><div class="card-header fw-bold">Projects</div><div class="list-group list-group-flush"><?php foreach($projects as$p):?><a class="list-group-item list-group-item-action <?=$selected===$p['slug']?'active':''?>" href="/admin/module_builder?project=<?=rawurlencode($p['slug'])?>"><?=$e($p['name'])?><br><small><?=$e($p['slug'])?> / <?=$e($p['version'])?></small></a><?php endforeach?><?php if(!$projects):?><span class="list-group-item text-muted">No projects.</span><?php endif?></div></div>
  <div class="card"><div class="card-header fw-bold">Create project</div><form method="post" class="card-body">
   <?=$csrf_field?><input type="hidden" name="action" value="create_project">
   <label class="form-label">Slug<input class="form-control" name="slug" required pattern="[a-z][a-z0-9_]{1,62}"></label>
   <label class="form-label">Name<input class="form-control" name="name" required maxlength="100"></label>
   <label class="form-label">Version<input class="form-control" name="version" value="1.0.0" required></label>
   <label class="form-label">Description<textarea class="form-control" name="description" required maxlength="500"></textarea></label>
   <label class="form-label">Update URL<input class="form-control" type="url" name="update_url" placeholder="https://example.com/updates/module.json" required></label>
   <label class="form-label">Creator<input class="form-control" name="creator" required maxlength="100"></label>
   <label class="form-label">Domain<input class="form-control" name="domain" placeholder="example.com" required></label>
   <button class="btn btn-primary w-100">Create</button>
  </form></div>
 </aside><section class="col-xl-9"><?php if($current):?>
  <div class="card mb-4"><div class="card-header fw-bold">Project settings</div><form method="post" class="card-body row g-3">
   <?=$csrf_field?><input type="hidden" name="action" value="edit_project"><input type="hidden" name="project" value="<?=$e($selected)?>">
   <div class="col-md-6"><label class="form-label">Name<input class="form-control" name="name" value="<?=$e($current['name'])?>" required></label></div>
   <div class="col-md-3"><label class="form-label">Version<input class="form-control" name="version" value="<?=$e($current['version'])?>" required></label></div>
   <div class="col-12"><label class="form-label">Description<textarea class="form-control" name="description" required maxlength="500"><?=$e($current['description'])?></textarea></label></div>
   <div class="col-md-8"><label class="form-label">Update URL<input class="form-control" type="url" name="update_url" value="<?=$e($current['update_url'])?>" required></label></div>
   <div class="col-md-4"><label class="form-label">Creator<input class="form-control" name="creator" value="<?=$e($current['creator'])?>" required></label></div>
   <div class="col-md-8"><label class="form-label">Domain<input class="form-control" name="domain" value="<?=$e($current['domain'])?>" required></label></div>
   <div class="col-md-4"><label class="form-label">Certified<select class="form-select" name="certified"><option value="No" <?=$current['certified']==='No'?'selected':''?>>No</option><option value="Yes" <?=$current['certified']==='Yes'?'selected':''?>>Yes</option></select></label></div>
   <div class="col-12"><label class="form-label">Signing SHA-256<input class="form-control font-monospace" name="signing_sha256" value="<?=$e($current['signing']['sha256']??'')?>"></label></div>
   <div class="col-12"><label class="form-label">Signing key ID<input class="form-control font-monospace" name="signing_key_id" value="<?=$e($current['signing']['key_id']??'')?>"></label></div>
   <div class="col-12"><label class="form-label">Public key (PEM or base64)<textarea class="form-control font-monospace" name="signing_public_key" rows="5"><?=$e($current['signing']['public_key']??'')?></textarea></label></div>
   <div><button class="btn btn-outline-primary">Save metadata</button></div>
  </form></div>
  <div class="row g-4"><div class="col-lg-4"><div class="card h-100"><div class="card-header fw-bold">Files</div><div class="list-group list-group-flush overflow-auto" style="max-height:30rem"><?php foreach($tree as$f):?><?php if($f['directory']):?><span class="list-group-item">Directory: <?=$e($f['path'])?></span><?php else:?><a class="list-group-item list-group-item-action" href="/admin/module_builder?project=<?=rawurlencode($selected)?>&amp;path=<?=rawurlencode($f['path'])?>"><?=$e($f['path'])?></a><?php endif?><?php endforeach?></div><div class="card-body border-top"><form method="post" class="mb-2"><?=$csrf_field?><input type="hidden" name="project" value="<?=$e($selected)?>"><input type="hidden" name="action" value="create_file"><div class="input-group"><input class="form-control" name="path" placeholder="views/about.php"><button class="btn btn-outline-secondary">New file</button></div></form><form method="post"><?=$csrf_field?><input type="hidden" name="project" value="<?=$e($selected)?>"><input type="hidden" name="action" value="create_directory"><div class="input-group"><input class="form-control" name="path" placeholder="assets/css"><button class="btn btn-outline-secondary">New folder</button></div></form></div></div></div>
  <div class="col-lg-8"><div class="card h-100"><div class="card-header fw-bold">Editor<?=$path?': '.$e($path):''?></div><div class="card-body"><?php if($content!==null):?><form method="post"><?=$csrf_field?><input type="hidden" name="action" value="save_file"><input type="hidden" name="project" value="<?=$e($selected)?>"><input type="hidden" name="path" value="<?=$e($path)?>"><textarea class="form-control font-monospace mb-3" rows="20" name="content"><?=$e($content)?></textarea><button class="btn btn-primary">Save</button></form><div class="row g-2 mt-2"><div class="col-8"><form method="post"><?=$csrf_field?><input type="hidden" name="action" value="rename_path"><input type="hidden" name="project" value="<?=$e($selected)?>"><input type="hidden" name="path" value="<?=$e($path)?>"><div class="input-group"><input class="form-control" name="new_path" value="<?=$e($path)?>"><button class="btn btn-outline-secondary">Rename</button></div></form></div><div class="col-4"><form method="post" onsubmit="return confirm('Delete path?')"><?=$csrf_field?><input type="hidden" name="action" value="delete_path"><input type="hidden" name="project" value="<?=$e($selected)?>"><input type="hidden" name="path" value="<?=$e($path)?>"><button class="btn btn-outline-danger w-100">Delete</button></form></div></div><?php else:?><p class="text-muted">Choose a text file.</p><?php endif?></div></div></div></div>
  <div class="row g-4 mt-1">
   <div class="col-lg-5"><div class="card h-100"><div class="card-header fw-bold">Validation</div><div class="card-body"><?php if($validation['valid']):?><p class="text-success fw-bold">Project valid.</p><?php else:?><ul class="text-danger"><?php foreach($validation['errors']as$x):?><li><?=$e($x)?></li><?php endforeach?></ul><?php endif?></div></div></div>
   <div class="col-lg-7"><div class="card h-100"><div class="card-header fw-bold">Artifacts</div><div class="card-body">
    <?php if(!$hasSigning):?><div class="alert alert-info">New modules start with blank signing fields. Generate a keypair, then copy <code>sha256</code>, <code>key_id</code>, and <code>public_key</code> from <code>key-metadata.json</code> into Project settings and mark the module certified.</div><?php endif?>
    <form method="post" enctype="multipart/form-data" class="mb-3">
     <?=$csrf_field?><input type="hidden" name="action" value="build_and_sign"><input type="hidden" name="project" value="<?=$e($selected)?>">
     <label class="form-label">Encrypted private-key.pem<input type="file" class="form-control" name="private_key" accept=".pem,application/x-pem-file,text/plain" required></label>
     <label class="form-label">Private-key passphrase<input type="password" class="form-control" name="private_key_passphrase" autocomplete="current-password" required></label>
     <button class="btn btn-success mt-2" <?=(!$validation['valid']||!$hasSigning)?'disabled':''?>>Build &amp; Sign Release</button>
    </form>
    <ul class="list-group"><?php foreach($artifacts as$a):?><li class="list-group-item d-flex justify-content-between"><span><?=$e($a['name'])?></span><small><?=number_format($a['size'])?> bytes</small></li><?php endforeach?><?php if(!$artifacts):?><li class="list-group-item text-muted">No artifacts.</li><?php endif?></ul>
   </div></div></div>
  </div>
  <form method="post" class="mt-4" onsubmit="return confirm('Permanently delete live project?')"><?=$csrf_field?><input type="hidden" name="action" value="delete_project"><input type="hidden" name="project" value="<?=$e($selected)?>"><button class="btn btn-danger">Delete project</button></form>
 <?php else:?><div class="card"><div class="card-body py-5 text-center text-muted">Create or choose a project.</div></div><?php endif?></section></div>
</main>
<?php
require APPROOT . '/views/inc/foot.php';
?>
