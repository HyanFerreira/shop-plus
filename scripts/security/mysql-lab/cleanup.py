"""Stop ONLY resources bearing this private lab's identity. Private files are retained, no recursive deletion."""
import json
import os
from pathlib import Path
import re
import subprocess
import sys

path = Path(sys.argv[1]).resolve()
lab = json.loads(path.read_text(encoding='utf-8'))
assert re.fullmatch('[a-f0-9]{12}', lab['owner'])
root = Path.home() / ('.shopplus-sds7c-' + lab['owner'])
assert path.parent == root.resolve() and Path(lab['root']).resolve() == root.resolve()
assert lab['container'] == 'shopplus-sds7c-' + lab['owner']
assert lab['network'] == lab['container'] + '-internal'
metadata = json.loads(subprocess.check_output(['docker', 'inspect', lab['container_id']], text=True))[0]
assert metadata['Config']['Labels']['shopplus.sds7c'] == lab['owner']
assert metadata['Id'] == lab['container_id']
for filename in ['http.pid', 'relay.pid']:
    pid_file = root / filename
    if not pid_file.exists():
        continue
    pid = int(pid_file.read_text())
    if os.name == 'nt':
        command = f"Get-CimInstance Win32_Process -Filter 'ProcessId={pid}' | Select-Object ProcessId,CommandLine | ConvertTo-Json"
        info = subprocess.check_output(['powershell', '-NoProfile', '-Command', command], text=True).strip()
        if not info:
            continue
        info = json.loads(info)
        assert str(root).lower() in info['CommandLine'].lower(), 'PID does not belong to this lab'
        subprocess.run(['taskkill', '/PID', str(pid), '/F'], check=True, capture_output=True)
    else:
        cmdline = Path(f'/proc/{pid}/cmdline')
        if cmdline.exists():
            assert str(root).encode() in cmdline.read_bytes()
            os.kill(pid, 15)
subprocess.run(['docker', 'rm', '-f', lab['container_id']], check=True, capture_output=True)
network = json.loads(subprocess.check_output(['docker', 'network', 'inspect', lab['network']], text=True))[0]
assert network['Labels']['shopplus.sds7c'] == lab['owner'] and network['Internal']
subprocess.run(['docker', 'network', 'rm', lab['network']], check=True, capture_output=True)
print(json.dumps({'status': 'removed owned container/network and stopped lab processes',
    'private_files_retained': str(root), 'original_mysql_touched': False}))
