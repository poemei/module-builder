<?php
/* [AI:GPT-5.6 Sol | 2026-08-29 02:45:00 UTC] */
require dirname(__DIR__) . '/lib/module_package_builder.php';
$base = sys_get_temp_dir() . '/chaos-builder-test-' . bin2hex(random_bytes(5));
$modules = $base . '/user/modules';
$releases = $base . '/releases';
$appRoot = $base . '/app';
mkdir($appRoot . '/views/inc', 0775, true);
file_put_contents($appRoot . '/views/inc/head.php', '');
file_put_contents($appRoot . '/views/inc/foot.php', '');
if (!defined('APPROOT')) define('APPROOT', $appRoot);
$builder = new module_package_builder($modules, $releases);
$fail = static function (string $message): never {
    fwrite(STDERR, $message . PHP_EOL);
    exit(1);
};
$readStoredZip = static function (string $zip) use ($fail): array {
    $entries = [];
    $offset = 0;
    $length = strlen($zip);

    while ($offset + 4 <= $length) {
        $signature = unpack('Vsignature', substr($zip, $offset, 4));
        if (($signature['signature'] ?? 0) !== 0x04034b50) {
            break;
        }
        if ($offset + 30 > $length) $fail('truncated ZIP local header');
        $header = unpack(
            'vversion/vflags/vmethod/vtime/vdate/Vcrc/Vcompressed/Vuncompressed/vname_length/vextra_length',
            substr($zip, $offset + 4, 26)
        );
        if (($header['method'] ?? -1) !== 0) $fail('key ZIP must use in-memory stored entries');
        $nameStart = $offset + 30;
        $name = substr($zip, $nameStart, $header['name_length']);
        $dataStart = $nameStart + $header['name_length'] + $header['extra_length'];
        $data = substr($zip, $dataStart, $header['compressed']);
        if (strlen($data) !== $header['uncompressed']) $fail('key ZIP entry size mismatch');
        if (strtolower(hash('crc32b', $data)) !== sprintf('%08x', $header['crc'])) $fail('key ZIP CRC mismatch');
        $entries[$name] = $data;
        $offset = $dataStart + $header['compressed'];
    }

    return $entries;
};
try {
    $builder->createProject([
        'slug'=>'weather','name'=>'Weather','version'=>'1.0.0',
        'description'=>'Weather module for ChAoS MVC',
        'update_url'=>'https://example.com/updates/weather.json',
        'creator'=>'Test Developer','domain'=>'example.com',
    ]);
    $validation = $builder->validateProject('weather');
    if (!$validation['valid']) $fail('Generated project invalid: ' . implode('; ', $validation['errors']));
    $metadata = json_decode((string) file_get_contents($modules . '/weather/module.json'), true);
    if (!in_array('index', $metadata['routes'], true)) $fail('index route missing');
    $expectedKeys = ['name','module','version','description','update_url','creator','domain','certified','signing','files','routes'];
    if (array_keys($metadata) !== $expectedKeys) $fail('module metadata shape or order invalid');
    if ($metadata['certified'] !== 'No'
        || !preg_match('/^[a-f0-9]{64}$/', $metadata['signing']['sha256'] ?? '')
        || ($metadata['signing']['key_id'] ?? null) !== ''
        || ($metadata['signing']['public_key'] ?? null) !== '') $fail('server-generated project SHA-256 invalid');
    $firstProjectSha = $metadata['signing']['sha256'];
    $builder->deleteProject('weather');
    $builder->createProject([
        'slug'=>'weather','name'=>'Weather','version'=>'1.0.0',
        'description'=>'Weather module for ChAoS MVC',
        'update_url'=>'https://example.com/updates/weather.json',
        'creator'=>'Test Developer','domain'=>'example.com','certified'=>'Yes',
        'signing_sha256'=>str_repeat('a',64),'signing_key_id'=>'forced-key',
        'signing_public_key'=>base64_encode("-----BEGIN PUBLIC KEY-----\ninvalid\n-----END PUBLIC KEY-----\n"),
    ]);
    $metadata = json_decode((string) file_get_contents($modules . '/weather/module.json'), true);
    if ($metadata['certified'] !== 'No'
        || !preg_match('/^[a-f0-9]{64}$/', $metadata['signing']['sha256'] ?? '')
        || $metadata['signing']['sha256'] === str_repeat('a',64)
        || $metadata['signing']['sha256'] === $firstProjectSha
        || ($metadata['signing']['key_id'] ?? null) !== ''
        || ($metadata['signing']['public_key'] ?? null) !== '') $fail('project creation did not generate its own SHA-256');
    $listedProject = array_values(array_filter($builder->listProjects(), static fn(array $project): bool => $project['slug'] === 'weather'))[0] ?? null;
    if (($listedProject['signing']['sha256'] ?? '') !== $metadata['signing']['sha256']) $fail('generated SHA-256 is not available to Project Settings');
    if (!in_array('views/index.php', $metadata['files'], true)) $fail('index view manifest entry missing');
    if (!in_array('views/admin/weather.php', $metadata['files'], true) || !is_file($modules . '/weather/views/admin/weather.php')) $fail('slug admin view missing');
    if (!in_array('docs/CHANGELOG.md', $metadata['files'], true) || !is_file($modules . '/weather/docs/CHANGELOG.md')) $fail('generated changelog missing');
    $changelog = (string) file_get_contents($modules . '/weather/docs/CHANGELOG.md');
    if (!str_contains($changelog, '## 1.0.0 — ' . gmdate('Y-m-d')) || !str_contains($changelog, '- Initial module implementation.')) $fail('initial changelog content invalid');
    $view = (string) file_get_contents($modules . '/weather/views/index.php');
    if (!str_contains($view, "APPROOT . '/views/inc/head.php'") || !str_contains($view, "APPROOT . '/views/inc/foot.php'")) $fail('wrappers missing');
    $builder->createFile('weather', 'views/extra.php');
    $builder->writeFile('weather', 'views/extra.php', '<?php echo "safe";');
    if (!str_contains($builder->readFile('weather', 'views/extra.php'), 'safe')) $fail('editor failed');
    try { $builder->readFile('weather', '../../app/core/router.php'); $fail('traversal accepted'); }
    catch (InvalidArgumentException|RuntimeException $expected) {}
    $builder->editProject('weather', [
        'name'=>'Weather Two','version'=>'1.0.1','description'=>'Edited weather module',
        'update_url'=>'https://example.com/updates/weather.json',
        'creator'=>'Test Developer','domain'=>'example.com','certified'=>'No',
        'signing_sha256'=>'','signing_key_id'=>'','signing_public_key'=>'',
    ]);
    foreach (['', 'too-short'] as $invalidPassphrase) {
        try {
            $builder->generateSigningKeypair($invalidPassphrase);
            $fail('short key passphrase accepted');
        } catch (InvalidArgumentException $expected) {
        }
    }
    $pair = $builder->generateSigningKeypair('correct-horse-battery-staple', 'testdev');
    if (($pair['algorithm'] ?? '') !== 'RSA-SHA256' || ($pair['rsa_bits'] ?? 0) < 3072) $fail('RSA keypair shape invalid');
    if (!preg_match('/^[a-f0-9]{64}$/', $pair['sha256']) || !str_starts_with($pair['key_id'], 'testdev-')) $fail('module signing identity invalid');
    if (base64_decode($pair['public_key'], true) !== $pair['public_key_pem']) $fail('base64 public key invalid');

    foreach ([$pair['public_key_pem'], preg_replace('/-----BEGIN PUBLIC KEY-----|-----END PUBLIC KEY-----|\s+/', '', $pair['public_key_pem'])] as $publicKeyInput) {
        $builder->editProject('weather', [
            'name'=>'Weather Two','version'=>'1.0.1','description'=>'Edited weather module',
            'update_url'=>'https://example.com/updates/weather.json',
            'creator'=>'Test Developer','domain'=>'example.com','certified'=>'No',
            'signing_sha256'=>$pair['sha256'],'signing_key_id'=>$pair['key_id'],
            'signing_public_key'=>$publicKeyInput,
        ]);
        $normalizedMetadata = json_decode((string) file_get_contents($modules . '/weather/module.json'), true);
        if (base64_decode($normalizedMetadata['signing']['public_key'], true) !== $pair['public_key_pem']) $fail('public-key input was not normalized to base64 PEM');
    }

    $builder->editProject('weather', [
        'name'=>'Weather Two','version'=>'1.0.1','description'=>'Edited weather module',
        'update_url'=>'https://example.com/updates/weather.json',
        'creator'=>'Test Developer','domain'=>'example.com','certified'=>'Yes',
        'signing_sha256'=>$pair['sha256'],'signing_key_id'=>$pair['key_id'],
        'signing_public_key'=>$pair['public_key'],
    ]);
    $signedMetadata = json_decode((string) file_get_contents($modules . '/weather/module.json'), true);
    if ($signedMetadata['signing'] !== ['sha256'=>$pair['sha256'],'key_id'=>$pair['key_id'],'public_key'=>$pair['public_key']]) $fail('certified signing metadata mismatch');
    if (!$builder->validateProject('weather')['valid']) $fail('certified project validation failed');
    $projects = $builder->listProjects();
    $selected = 'weather';
    $tree = $builder->fileTree('weather');
    $path = '';
    $content = null;
    $validation = $builder->validateProject('weather');
    $artifacts = $builder->listArtifacts('weather');
    $certification = ['signing'=>true, 'message'=>'Test certification status'];
    $message = null;
    $error = null;
    $csrf_field = '';
    set_error_handler(static function (int $severity, string $message, string $file, int $line): never {
        throw new ErrorException($message, 0, $severity, $file, $line);
    });
    ob_start();
    try {
        require dirname(__DIR__) . '/views/admin/index.php';
        $renderedAdmin = (string) ob_get_clean();
    } catch (Throwable $renderError) {
        ob_end_clean();
        throw $renderError;
    } finally {
        restore_error_handler();
    }
    if (!str_contains($renderedAdmin, 'Build &amp; Sign Release')) $fail('admin signing controls failed to render');
    $zip = $builder->buildSigningKeypairZip($pair);
    $entries = $readStoredZip($zip);
    $names = array_keys($entries);
    sort($names);
    if ($names !== ['key-metadata.json', 'private-key.pem', 'public-key.pem']) $fail('key ZIP entry list invalid');

    $private = openssl_pkey_get_private($entries['private-key.pem'], 'correct-horse-battery-staple');
    if ($private === false) $fail('encrypted private key could not be reopened');
    if (@openssl_pkey_get_private($entries['private-key.pem'], 'wrong-passphrase') !== false) $fail('wrong passphrase opened private key');

    $signature = '';
    if (!openssl_sign('module-builder-test', $signature, $private, OPENSSL_ALGO_SHA256)) $fail('RSA-SHA256 signing failed');
    if (openssl_verify('module-builder-test', $signature, $entries['public-key.pem'], OPENSSL_ALGO_SHA256) !== 1) $fail('RSA-SHA256 verification failed');

    $keyMetadata = json_decode($entries['key-metadata.json'], true);
    if (!is_array($keyMetadata)) $fail('key metadata invalid');
    if (($keyMetadata['fingerprint_sha256'] ?? '') !== $pair['fingerprint_sha256']) $fail('key fingerprint mismatch');
    if (array_key_exists('private_key_pem', $keyMetadata) || str_contains($entries['key-metadata.json'], 'PRIVATE KEY')) $fail('private key leaked into metadata');
    if (is_dir($releases) && (glob($releases . '/*') ?: []) !== []) $fail('key generation wrote release artifacts');

    if (class_exists('ZipArchive')) {
        $artifact = $builder->buildAndSignReleaseWithPem(
            'weather',
            $entries['private-key.pem'],
            'correct-horse-battery-staple'
        );
        if (!is_file($artifact) || !is_file($artifact . '.sig')) $fail('signed artifacts missing');
        $releaseManifest = json_decode(
            (string) file_get_contents(dirname($artifact) . '/' . basename($artifact, '.zip') . '.manifest.json'),
            true
        );
        if (($releaseManifest['signed'] ?? false) !== true
            || ($releaseManifest['signature_algorithm'] ?? '') !== 'RSA-SHA256'
            || ($releaseManifest['key_id'] ?? '') !== $pair['key_id']) $fail('signed manifest invalid');
        $releaseSignature = base64_decode(trim((string) file_get_contents($artifact . '.sig')), true);
        if ($releaseSignature === false
            || openssl_verify(hash_file('sha256', $artifact), $releaseSignature, $entries['public-key.pem'], OPENSSL_ALGO_SHA256) !== 1) $fail('release signature verification failed');
    }

    $pair = [];
    $zip = null;
    $entries = [];
    $builder->deleteProject('weather');
    if (is_dir($modules . '/weather')) $fail('deleteProject failed');
    echo "Module Builder behavior tests passed." . PHP_EOL;
} finally {
    if (is_dir($base)) {
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($it as $item) { $item->isDir() && !$item->isLink() ? rmdir($item->getPathname()) : unlink($item->getPathname()); }
        rmdir($base);
    }
}
/* [End AI:GPT-5.6 Sol] */
