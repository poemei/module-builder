<?php
/**
 * Module Builder Controller
 *
 * Admin-only entry point for the ChAoS MVC module workspace.
 */
/* [AI:GPT-5.6 Sol | 2026-08-29 03:35:00 UTC] */
class module_builder extends controller
{
    public function admin($params = []): void
    {
        $this->require_admin(9);
        require_once dirname(__DIR__) . '/lib/module_package_builder.php';

        $builder = new module_package_builder();
        $message = null;
        $error = null;

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $this->require_csrf();
            try {
                $message = $this->performAction($builder);
            } catch (Throwable $exception) {
                http_response_code(400);
                $error = $exception->getMessage();
            }
        }

        $selected = trim((string) ($_REQUEST['project'] ?? ''));
        $selected = $builder->isValidSlug($selected) ? $selected : '';
        $downloadArtifact = trim((string) ($_GET['download_artifact'] ?? ''));

        if ($selected !== '' && $downloadArtifact !== '') {
            try {
                $this->downloadArtifact(
                    $builder->artifactFile($selected, $downloadArtifact)
                );
            } catch (Throwable $exception) {
                http_response_code(404);
                $error = $exception->getMessage();
            }
        }

        $path = trim((string) ($_REQUEST['path'] ?? ''));
        $content = null;

        if ($selected !== '' && $path !== '') {
            try {
                $content = $builder->readFile($selected, $path);
            } catch (Throwable $exception) {
                $error = $exception->getMessage();
            }
        }

        $this->view('admin/index', [
            'projects' => $builder->listProjects(),
            'selected' => $selected,
            'tree' => $selected === '' ? [] : $builder->fileTree($selected),
            'path' => $path,
            'content' => $content,
            'validation' => $selected === '' ? null : $builder->validateProject($selected),
            'artifacts' => $selected === '' ? [] : $builder->listArtifacts($selected),
            'certification' => $builder->certificationStatus($selected),
            'builder_config' => $builder->builderConfig(),
            'config_required' => $builder->configRequired(),
            'message' => $message,
            'error' => $error,
            'csrf_field' => $this->csrf_field(),
        ]);
    }

    private function performAction(module_package_builder $builder): string
    {
        $action = (string) ($_POST['action'] ?? '');
        $project = (string) ($_POST['project'] ?? '');

        switch ($action) {
            case 'save_builder_config':
                $builder->saveBuilderConfig($_POST);
                return 'Builder configuration saved. Certification status is shown separately.';
            case 'create_project':
                $builder->createProject($_POST);
                return 'Project created.';
            case 'edit_project':
                $builder->editProject($project, $_POST);
                return 'Project updated.';
            case 'delete_project':
                $builder->deleteProject($project);
                $_REQUEST['project'] = '';
                return 'Project deleted.';
            case 'save_file':
                $builder->writeFile($project, (string) ($_POST['path'] ?? ''), (string) ($_POST['content'] ?? ''));
                return 'File saved.';
            case 'create_file':
                $builder->createFile($project, (string) ($_POST['path'] ?? ''));
                return 'File created.';
            case 'create_directory':
                $builder->createDirectory($project, (string) ($_POST['path'] ?? ''));
                return 'Directory created.';
            case 'rename_path':
                $builder->renamePath($project, (string) ($_POST['path'] ?? ''), (string) ($_POST['new_path'] ?? ''));
                return 'Path renamed.';
            case 'delete_path':
                $builder->deletePath($project, (string) ($_POST['path'] ?? ''));
                return 'Path deleted.';
            case 'build_and_sign':
                $artifact = $builder->buildAndSignRelease(
                    $project,
                    $_FILES['private_key'] ?? [],
                    (string) ($_POST['private_key_passphrase'] ?? ''),
                    (string) ($_POST['download_url'] ?? '')
                );
                return 'Release built and signed; saved files and local release-data copies verified: ' . basename($artifact) . '. Developer-domain publication is still required; the domain has not been checked.';
            case 'generate_keypair':
                $keypair = $builder->generateSigningKeypair(
                    (string) ($_POST['key_passphrase'] ?? ''),
                    (string) ($_POST['key_id_prefix'] ?? 'developer')
                );
                $this->downloadKeypairZip(
                    $builder->buildSigningKeypairZip($keypair)
                );
        }

        throw new InvalidArgumentException('Unknown Module Builder action.');
    }

    /**
     * Download an already constructed keypair archive.
     *
     * @return never
     */
    private function downloadKeypairZip(string $zip): never
    {
        $filename = 'chaos-rsa-signing-keypair-' . gmdate('Ymd-His') . '.zip';

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($zip));
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('X-Content-Type-Options: nosniff');
        echo $zip;

        $zip = null;
        exit;
    }

    private function downloadArtifact(string $file): never
    {
        $filename = basename($file);
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . (string) filesize($file));
        header('Cache-Control: private, no-store');
        header('X-Content-Type-Options: nosniff');
        readfile($file);
        exit;
    }
}
/* [End AI:GPT-5.6 Sol] */
