"""Loopback-only byte relay over docker exec; the database has NO published ports or external network."""
import json
import os
from pathlib import Path
import socket
import subprocess
import sys
import threading

descriptor = json.loads(Path(sys.argv[1]).read_text(encoding='utf-8'))
container = descriptor['container']
if container != 'shopplus-sds7c-' + descriptor['owner'] or descriptor['port'] != 33077:
    raise RuntimeError('Lab relay target rejected')
server = socket.socket()
server.bind(('127.0.0.1', 33077))
server.listen(16)
Path(descriptor['root'], 'relay.ready').write_text(str(os.getpid()))

def connection(client):
    process = subprocess.Popen(['docker', 'exec', '-i', container, 'bash', '-c',
        'exec 3<>/dev/tcp/127.0.0.1/3306; cat <&3 & cat >&3; wait'],
        stdin=subprocess.PIPE, stdout=subprocess.PIPE, stderr=subprocess.DEVNULL,
        creationflags=getattr(subprocess, 'CREATE_NO_WINDOW', 0))
    def upstream():
        try:
            while chunk := client.recv(65536):
                process.stdin.write(chunk)
                process.stdin.flush()
        except (OSError, BrokenPipeError):
            pass
        finally:
            try:
                process.stdin.close()
            except OSError:
                pass
    threading.Thread(target=upstream, daemon=True).start()
    try:
        while chunk := os.read(process.stdout.fileno(), 65536):
            client.sendall(chunk)
    except OSError:
        pass
    finally:
        client.close()
        process.terminate()
        process.wait(timeout=5)

while True:
    client, _ = server.accept()
    threading.Thread(target=connection, args=(client,), daemon=True).start()
