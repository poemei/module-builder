<?php
/* [AI:GPT-5.6 Sol | 2026-08-29 02:45:00 UTC] */
require dirname(__DIR__) . '/lib/module_package_builder.php';
$base = sys_get_temp_dir() . '/chaos-builder-test-' . bin2hex(random_bytes(5));
$modules = $base . '/user/modules';
$releases = $base . '/releases';
$builder = new module_package_builder($modules, $releases);
$fail = static function (string $message): never {
    fwrite(STDERR, $message . PHP_EOL);
    exit(1);
};
try {
    $builder->createProject(['slug'=>'weather','name'=>'Weather','version'=>'1.0.0','description'=>'Test']);
    $validation = $builder->validateProject('weather');
    if (!$validation['valid']) $fail('Generated project invalid: ' . implode('; ', $validation['errors']));
    $metadata = json_decode((string) file_get_contents($modules . '/weather/module.json'), true);
    if (!in_array('index', $metadata['routes'], true)) $fail('index route missing');
    if (!in_array('views/index.php', $metadata['files'], true)) $fail('index view manifest entry missing');
    $view = (string) file_get_contents($modules . '/weather/views/index.php');
    if (!str_contains($view, "APPROOT . '/views/inc/head.php'") || !str_contains($view, "APPROOT . '/views/inc/foot.php'")) $fail('wrappers missing');
    $builder->createFile('weather', 'views/extra.php');
    $builder->writeFile('weather', 'views/extra.php', '<?php echo "safe";');
    if (!str_contains($builder->readFile('weather', 'views/extra.php'), 'safe')) $fail('editor failed');
    try { $builder->readFile('weather', '../../app/core/router.php'); $fail('traversal accepted'); }
    catch (InvalidArgumentException|RuntimeException $expected) {}
    $builder->editProject('weather', ['name'=>'Weather Two','version'=>'1.0.1','description'=>'Edited']);
    if (class_exists('ZipArchive')) {
        $artifact = $builder->buildRelease('weather');
        if (!is_file($artifact)) $fail('artifact missing');
    }
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
