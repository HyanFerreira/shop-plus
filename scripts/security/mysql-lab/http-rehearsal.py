"""Real loopback HTTP + CSRF + Livewire rehearsal. No browser/desktop control and no private payload output."""
import copy
import html
from html.parser import HTMLParser
import http.cookiejar
import json
from pathlib import Path
import secrets
import subprocess
import sys
import time
import urllib.error
import urllib.parse
import urllib.request

lab = json.loads(Path(sys.argv[1]).read_text(encoding='utf-8'))
root, app_root = Path(lab['root']), Path(lab['app_root'])
if root.resolve() != (Path.home() / ('.shopplus-sds7c-' + lab['owner'])).resolve() or lab['http_url'] != 'http://127.0.0.1:8001':
    raise RuntimeError('HTTP lab identity rejected')
(app_root / 'public/hot').unlink(missing_ok=True)
server = subprocess.Popen([sys.argv[2], '-S', '127.0.0.1:8001', '-t', str(app_root / 'public'),
    str(Path(lab['repo']) / 'vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php')],
    cwd=app_root / 'public', stdout=(root / 'http.stdout').open('wb'), stderr=(root / 'http.stderr').open('wb'),
    creationflags=getattr(subprocess, 'CREATE_NO_WINDOW', 0))
(root / 'http.pid').write_text(str(server.pid))

class Page(HTMLParser):
    def __init__(self, source):
        super().__init__(convert_charrefs=True)
        self.components, self.csrf, self.assets = {}, '', []
        self.feed(source)
    def handle_starttag(self, tag, attrs):
        attrs = dict(attrs)
        if 'wire:snapshot' in attrs:
            snapshot = json.loads(attrs['wire:snapshot'])
            self.components[snapshot['memo']['name']] = attrs['wire:snapshot']
        if tag == 'meta' and attrs.get('name') == 'csrf-token':
            self.csrf = attrs.get('content', '')
        if tag == 'input' and attrs.get('name') == '_token':
            self.csrf = attrs.get('value', '')
        if tag == 'script' and urllib.parse.urlparse(attrs.get('src', '')).path.startswith('/build/'):
            self.assets.append(attrs['src'])
        if tag == 'link' and urllib.parse.urlparse(attrs.get('href', '')).path.startswith('/build/'):
            self.assets.append(attrs['href'])

class Client:
    def __init__(self):
        self.opener = urllib.request.build_opener(urllib.request.ProxyHandler({}), urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
        self.csrf = ''
    def request(self, path, data=None):
        url = urllib.parse.urljoin(lab['http_url'], path)
        if urllib.parse.urlparse(url).netloc != '127.0.0.1:8001':
            raise RuntimeError('Cross-host lab request rejected')
        headers = {'Accept': 'text/html'}
        body = None
        if data is not None:
            headers.update({'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': self.csrf})
            if path == '/livewire/update':
                headers['X-Livewire'] = 'true'
            body = json.dumps(data).encode()
        try:
            response = self.opener.open(urllib.request.Request(url, data=body, headers=headers), timeout=20)
            status, text = response.status, response.read().decode('utf-8')
        except urllib.error.HTTPError as response:
            status, text = response.code, response.read().decode('utf-8')
        if data is None and status == 200:
            page = Page(text)
            if page.csrf:
                self.csrf = page.csrf
        return status, text
    def page(self, path):
        status, source = self.request(path)
        assert status == 200, f'Page failed: {path}: {status}'
        return Page(source), source
    def call(self, snapshot, updates, method, params=None):
        status, source = self.request('/livewire/update', {'components': [{'snapshot': snapshot,
            'updates': updates, 'calls': [{'method': method, 'params': params or [], 'path': ''}]}]})
        assert status == 200, f'Livewire action failed: {method}: {status}'
        result = json.loads(source)['components'][0]
        result['errors'] = json.loads(result['snapshot'])['memo'].get('errors', {})
        return result
    def register(self, suffix):
        self.page('/register')
        email = f'lab-{suffix}-{secrets.token_hex(4)}@example.test'
        password = secrets.token_hex(16) + '-A!'
        status, _ = self.request('/register', {'name': 'Cliente Fictício de Ensaio', 'email': email,
            'password': password, 'password_confirmation': password})
        assert status in [200, 201], f'Registration failed: {status}'
        self.page('/dashboard')
        return email, password

try:
    a = Client()
    deadline = time.monotonic() + 20
    while True:
        try:
            if a.request('/up')[0] == 200:
                break
        except (urllib.error.URLError, ConnectionError):
            pass
        if time.monotonic() > deadline:
            raise RuntimeError('Lab HTTP did not start')
        time.sleep(0.2)
    _, home = a.page('/')
    built_assets = Page(home).assets
    assert built_assets and '127.0.0.1:5173' not in home, 'Clone must use production assets'
    for asset in built_assets:
        assert a.request(asset)[0] == 200, 'Built asset unavailable'
    email, password = a.register('buyer')
    assert a.request('/logout', {})[0] in [200, 204]
    a.page('/login')
    assert a.request('/login', {'email': email, 'password': password})[0] == 200
    a.page('/dashboard')
    personal, _ = a.page('/meus-dados')
    component = a.call(personal.components['personal-data.manager'], {'cpf': '123.456.789-09', 'birthDate': '1990-01-01'}, 'saveProfile')
    assert not component['errors'], 'Personal data validation'
    component = a.call(component['snapshot'], {'phoneNumber': '(31) 98888-4321'}, 'savePhone')
    assert not component['errors'], 'Phone validation'
    address = {'addressLabel': 'Casa de Ensaio', 'addressRecipient': 'Cliente Fictício',
        'addressPostalCode': '01001-000', 'addressStreet': 'Rua Fictícia de Ensaio', 'addressNumber': '100',
        'addressDistrict': 'Centro Fictício', 'addressCity': 'São Paulo', 'addressState': 'SP'}
    component = a.call(component['snapshot'], address, 'saveAddress')
    assert not component['errors'], 'Address validation'
    a.page('/catalogo')
    product, _ = a.page('/catalogo/aurora-phone-x')
    result = a.call(product.components['product-add-to-cart'], {'quantity': 2}, 'add')
    assert not result['errors'], 'Cart validation'
    a.page('/carrinho')
    checkout, _ = a.page('/checkout')
    result = a.call(checkout.components['checkout-manager'], {'shippingMethod': 'economy'}, 'confirm')
    assert not result['errors'], 'Checkout validation'
    order_path = urllib.parse.urlparse(result['effects']['redirect']).path
    order_page, _ = a.page(order_path)
    payment_snapshot = order_page.components['payment-form']
    payment_input = {'cardholderName': 'Cliente Fictício', 'cardNumber': '4111111111111111', 'cvv': '123'}
    result = a.call(payment_snapshot, payment_input, 'pay')
    assert not result['errors'], 'Payment validation'
    _, paid_page = a.page(order_path)
    assert 'Pago' in paid_page, 'Paid order not shown'
    assert 'Aguardando' in paid_page or 'Preparação' in paid_page or 'processamento' in paid_page, 'Delivery information missing'
    replay = a.call(payment_snapshot, payment_input, 'pay')
    assert not replay['errors'], 'Payment key replay should be idempotent'

    b = Client()
    b.register('other')
    assert b.request('/admin')[0] == 403, 'Customer admin access'
    assert b.request(order_path)[0] == 404, 'Foreign order access'
    # The first address is owned by the seeder's customer. Use a known existing foreign ID.
    other_personal, _ = b.page('/meus-dados')
    foreign = b.call(other_personal.components['personal-data.manager'], {}, 'deleteAddress', [1])
    assert foreign['errors'], 'Foreign address ID must be rejected'
    oversized = b.call(foreign['snapshot'], dict(address, addressLabel='a'*1001), 'saveAddress')
    assert oversized['errors'], 'Oversized input must be rejected'
    payload = '<img src=x onerror=alert(1)>'
    xss = b.call(oversized['snapshot'], dict(address, addressLabel=payload), 'saveAddress')
    assert not xss['errors'], 'Valid text must remain usable'
    rendered = xss['effects'].get('html', '')
    assert payload not in rendered and html.escape(payload) in rendered, 'Stored XSS must be escaped'
    _, search = b.page('/catalogo?q=' + urllib.parse.quote("' OR 1=1 --"))
    assert 'Nenhum produto' in search, 'SQL-shaped search is not literal'
    product, _ = b.page('/catalogo/aurora-phone-x')
    excessive = b.call(product.components['product-add-to-cart'], {'quantity': 99}, 'add')
    assert excessive['errors'], 'Excessive stock purchase must be rejected'
    private = {'email': email, 'public_number': order_path.rsplit('/', 1)[-1]}
    (root / 'rehearsal.json').write_text(json.dumps(private), encoding='utf-8')
    print(json.dumps({'status': 'PASS', 'http': lab['http_url'], 'server_pid': server.pid,
        'flow': ['home', 'register', 'logout', 'login', 'cpf', 'phone', 'address', 'catalog', 'cart', 'checkout', 'approved_payment', 'owned_order', 'delivery_information'],
        'csrf': 'real tokens and cookie sessions; no test bypass', 'email_transport': 'array, no external delivery',
        'production_assets_http_200': len(built_assets), 'vite_dev_dependency': False,
        'security': {'admin_403': True, 'foreign_order_404': True, 'foreign_address_rejected': True,
            'oversized_input_rejected': True, 'stored_xss_escaped': True, 'sql_search_literal': True,
            'payment_replay_accepted_idempotently': True, 'excess_stock_rejected': True},
        'order_public_number': private['public_number'], 'workbench_gui': 'not automated; persisted verification performed separately'}, indent=2))
except Exception as error:
    # Do not print response bodies, snapshots, tokens, credentials or card input.
    print(json.dumps({'status': 'FAIL', 'class': type(error).__name__, 'check': str(error) if isinstance(error, AssertionError) else None}))
    sys.exit(1)
