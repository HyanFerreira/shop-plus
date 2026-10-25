"""Create a NEW disposable lab. Never import the development .env or attach host data."""
import base64
import json
import os
from pathlib import Path
import secrets
import shutil
import socket
import subprocess
import sys
import time

repo = Path(__file__).resolve().parents[3]
suffix = secrets.token_hex(6)
root = Path.home() / ('.shopplus-sds7c-' + suffix)
root.mkdir(mode=0o700)
if os.name == 'nt':
    owner = os.environ['USERDOMAIN'] + '\\' + os.environ['USERNAME']
    subprocess.run(['icacls', str(root), '/inheritance:r', '/grant:r', owner + ':(OI)(CI)F', '*S-1-5-18:(OI)(CI)F'], check=True, capture_output=True)
with socket.socket() as probe:
    probe.bind(('127.0.0.1', 33077))
container = 'shopplus-sds7c-' + suffix
network = container + '-internal'
database = 'shopplus_sds7c_' + suffix
root_password, password = secrets.token_hex(24), secrets.token_hex(24)
env_file = root / 'mysql.env'
env_file.write_text(f'MYSQL_ROOT_PASSWORD={root_password}\nMYSQL_DATABASE={database}\nMYSQL_USER=shopplus_lab\nMYSQL_PASSWORD={password}\n', encoding='utf-8')

def docker(*args):
    return subprocess.run(['docker', *args], check=True, text=True, capture_output=True).stdout.strip()

image = docker('image', 'inspect', 'mysql:8.4', '--format', '{{.Id}}')
docker('network', 'create', '--internal', '--label', 'shopplus.sds7c=' + suffix, network)
container_id = docker('run', '-d', '--name', container, '--label', 'shopplus.sds7c=' + suffix,
    '--network', network, '--env-file', str(env_file),
    '--mount', 'type=tmpfs,destination=/var/lib/mysql,tmpfs-size=536870912',
    '--memory', '1g', '--cpus', '2', '--pids-limit', '256', image,
    '--performance-schema=ON', '--innodb-buffer-pool-size=67108864', '--skip-log-bin',
    '--local-infile=OFF', '--secure-file-priv=NULL', '--mysqlx=OFF')
deadline = time.monotonic() + 90
server_uuid = ''
while time.monotonic() < deadline:
    try:
        server_uuid = docker('exec', container, 'sh', '-c',
            'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" mysql -uroot -Nse "SELECT @@server_uuid"')
        if server_uuid:
            break
    except subprocess.CalledProcessError:
        time.sleep(1)
if not server_uuid:
    raise RuntimeError('Lab MySQL did not become ready; no host database was contacted')
app_root = root / 'app'
app_root.mkdir()
for directory in ['app', 'config', 'resources', 'routes', 'public']:
    shutil.copytree(repo / directory, app_root / directory)
(app_root / 'public/hot').unlink(missing_ok=True)  # Always exercise built assets, never the original dev server.
for directory in ['factories', 'migrations', 'seeders']:
    shutil.copytree(repo / 'database' / directory, app_root / 'database' / directory)
for directory in ['bootstrap/cache', 'storage/framework/cache/data', 'storage/framework/sessions',
                  'storage/framework/views', 'storage/logs', 'storage/app/private', 'storage/app/public']:
    (app_root / directory).mkdir(parents=True, exist_ok=True)
shutil.copy2(repo / 'bootstrap/app.php', app_root / 'bootstrap/shopplus.php')
shutil.copy2(repo / 'bootstrap/providers.php', app_root / 'bootstrap/providers.php')
shutil.copy2(repo / 'composer.json', app_root / 'composer.json')
if os.name == 'nt':
    subprocess.run(['cmd', '/c', 'mklink', '/J', str(app_root / 'vendor'), str(repo / 'vendor')], check=True, capture_output=True)
else:
    (app_root / 'vendor').symlink_to(repo / 'vendor', target_is_directory=True)
descriptor = {'owner': suffix, 'repo': str(repo), 'root': str(root), 'app_root': str(app_root),
    'container': container, 'container_id': container_id, 'network': network, 'image_id': image,
    'host': '127.0.0.1', 'port': 33077, 'database': database, 'username': 'shopplus_lab',
    'password': password, 'server_uuid': server_uuid,
    'app_key': 'base64:' + base64.b64encode(secrets.token_bytes(32)).decode(),
    'blind_key': secrets.token_hex(32), 'demo_password': secrets.token_hex(20),
    'http_url': 'http://127.0.0.1:8001'}
descriptor_file = root / 'descriptor.json'
descriptor_file.write_text(json.dumps(descriptor), encoding='utf-8')
relay = subprocess.Popen([sys.executable, str(repo / 'scripts/security/mysql-lab/relay.py'), str(descriptor_file)],
    stdout=(root / 'relay.stdout').open('wb'), stderr=(root / 'relay.stderr').open('wb'),
    creationflags=getattr(subprocess, 'CREATE_NO_WINDOW', 0))
(root / 'relay.pid').write_text(str(relay.pid))
deadline = time.monotonic() + 10
while not (root / 'relay.ready').is_file() and time.monotonic() < deadline:
    time.sleep(0.1)
if not (root / 'relay.ready').is_file():
    raise RuntimeError('Local lab relay did not start')
def php_quote(value):
    return "'" + str(value).replace('\\', '/').replace("'", "\\'") + "'"
(app_root / 'bootstrap/app.php').write_text('<?php\nrequire_once ' + php_quote(repo / 'scripts/security/mysql-lab/LabBootstrap.php') + ';\nreturn sdsLabApplication(' + php_quote(descriptor_file) + ');\n', encoding='utf-8')
# Nothing from the original .env is copied. The wrapper sets ONLY the lab environment.
(app_root / '.env').write_text('APP_ENV=sds_lab\nAPP_DEBUG=false\n', encoding='utf-8')
print(json.dumps({'descriptor_file': str(descriptor_file), 'container': container,
    'container_id': container_id, 'network_internal': True, 'published_ports': [], 'local_exec_relay': '127.0.0.1:33077',
    'database': database, 'server_uuid': server_uuid, 'image_id': image, 'app_root': str(app_root),
    'host_data_mounts': False}, indent=2))
