"""Package the CURRENT uncommitted application by allowlist, excluding credentials and runtime data."""
import hashlib
import json
from pathlib import Path
import sys
import zipfile

repo = Path(__file__).resolve().parents[3]
target = Path(sys.argv[1]).resolve()
directories = ['app', 'config', 'resources', 'routes', 'public', 'database/migrations',
               'database/seeders', 'database/factories', 'scripts/security', 'docs/security/7c']
files = ['artisan', 'composer.json', 'composer.lock', 'package.json', 'package-lock.json',
         'vite.config.js', 'tailwind.config.js', 'postcss.config.js', '.env.vm.example',
         'bootstrap/app.php', 'bootstrap/providers.php', 'docs/security/nginx-vm.conf.example',
         'docs/security/7b/php-sds.ini.example']
manifest = {}
def allowed(path):
    relative = path.relative_to(repo).as_posix()
    if any(p.is_symlink() or getattr(p, 'is_junction', lambda: False)() for p in [path, *path.parents] if p != repo.parent):
        return False
    if relative.startswith(('public/storage/', 'docs/security/7c/evidence/')) or relative == 'public/hot':
        return False
    if (path.name.startswith('.env') and relative != '.env.vm.example') or '__pycache__' in path.parts:
        return False
    if path.suffix.lower() in ['.log', '.bak', '.zip', '.pyc', '.sqlite']:
        return False
    if path.suffix.lower() == '.sql' and relative != 'docs/security/7c/workbench.sql':
        return False
    if relative.startswith('public/') and path.suffix == '.php' and relative != 'public/index.php':
        return False
    return path.is_file()

paths = {repo / name for name in files}
for directory in directories:
    paths.update((repo / directory).rglob('*'))
with zipfile.ZipFile(target, 'x', compression=zipfile.ZIP_DEFLATED) as archive:
    for path in sorted(paths):
        if allowed(path):
            name = path.relative_to(repo).as_posix()
            content = path.read_bytes()
            archive.writestr(name, content)
            manifest[name] = hashlib.sha256(content).hexdigest()
    for folder in ['bootstrap/cache/', 'storage/framework/cache/data/', 'storage/framework/sessions/',
                   'storage/framework/views/', 'storage/logs/', 'storage/app/private/', 'storage/app/public/']:
        archive.writestr(folder, '')
    archive.writestr('SDS-PACKAGE-MANIFEST.json', json.dumps({'files': manifest,
        'excluded': ['.env', '.env.testing', '.git', 'vendor', 'node_modules', 'public/hot', 'public/storage', 'logs', 'database dumps', 'lab credentials']}, indent=2))
with zipfile.ZipFile(target) as archive:
    assert archive.testzip() is None
    names = archive.namelist()
    assert '.env' not in names and 'public/hot' not in names and 'public/build/manifest.json' in names
print(json.dumps({'package': str(target), 'sha256': hashlib.sha256(target.read_bytes()).hexdigest(),
    'files': len(manifest), 'bytes': target.stat().st_size, 'archive_verified': True,
    'contains_current_uncommitted_source': True, 'credentials_or_runtime_data_included': False}, indent=2))
